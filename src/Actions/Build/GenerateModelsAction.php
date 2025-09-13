<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_models_for_config',
    description: 'Generates Eloquent model classes for all entities defined in the given Config.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateModelsAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected string $modelPath;

    private string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = __DIR__.'/../../../stubs';
        $this->modelPath = app_path('Models');
        if (! File::exists($this->modelPath)) {
            File::makeDirectory($this->modelPath, 0755, true);
        }
    }

    /**
     * Build all models based on the configuration.
     */
    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        foreach ($context->getConfig()->entities as $entity) {
            $this->generateModel($entity);
        }

        return $context;
    }

    /**
     * Generate a model class for the given entity.
     */
    protected function generateModel(Entity $entity): void
    {
        $entityName = $entity->getName();
        $className = Str::studly(Str::singular($entityName));

        // Load model presets
        $modelPresets = config('magic.model_presets', []);

        $extends = '\Illuminate\Database\Eloquent\Model';
        $uses = [];
        $traits = [];
        $appends = [];
        $fields = $entity->getFields();

        $fillable = $entity->getFillableFieldsNames();
        $hidden = $entity->getHiddenFieldsNames();
        $casts = $entity->getCasts();
        $nameFields = $entity->getNameFieldsNames();

        // Apply model presets
        if (isset($modelPresets[$entityName])) {
            $preset = $modelPresets[$entityName];
            $extends = $preset['extends'] ?? $extends;
            $traits = $preset['traits'] ?? $traits;

            foreach ($preset['default_fields'] ?? [] as $defaultField) {
                $name = $defaultField['name'];
                if (! $entity->hasField($name)) {
                    $entity->addField(Field::fromConfig($defaultField));
                }
            }

            foreach ($preset['fillable'] ?? [] as $item) {
                if (! in_array($item, $fillable)) {
                    $fillable[] = $item;
                }
            }

            foreach ($preset['hidden'] ?? [] as $item) {
                if (! in_array($item, $hidden)) {
                    $hidden[] = $item;
                }
            }

            foreach ($preset['casts'] ?? [] as $key => $value) {
                if (! isset($casts[$key])) {
                    $casts[$key] = $value;
                }
            }
        }

        // Always ensure HasFactory
        if (! in_array(HasFactory::class, $traits)) {
            $traits[] = HasFactory::class;
        }

        // Automatically add HasName trait if entity has no "name" field
        if (! $entity->hasField('name')) {
            $traits[] = 'App\Traits\HasName';
            $appends[] = 'name';
        }

        // Automatically add HasImages trait if entity supports images
        if ($entity->hasImages() ?? false) {
            $traits[] = 'App\Traits\HasImages';
        }

        // Imports for traits
        foreach ($traits as $t) {
            $uses[] = $t;
        }

        // Infer casts if empty
        if (empty($casts)) {
            foreach ($fields as $field) {
                $cast = $this->mapFieldTypeToCast($field);
                if ($cast) {
                    $casts[$field->name] = $cast;
                }
            }
        }

        // Relations
        $relationsCode = '';
        foreach ($entity->getRelations() as $relation) {
            $relationsCode .= $this->buildRelationMethod($relation)."\n\n";
        }

        // Prepare stub replacements
        $replacements = [
            '{{namespace}}' => 'App\Models',
            '{{uses}}' => implode("\n", array_map(fn ($t) => "use $t;", array_unique($uses))),
            '{{modelClass}}' => $className,
            '{{extends}}' => $extends,
            '{{traits}}' => ! empty($traits) ? 'use '.implode(', ', array_map(fn ($t) => class_basename($t), $traits)).';' : '',
            '{{fillable}}' => implode(",\n        ", array_map(fn ($f) => "'$f'", $fillable)),
            '{{hidden}}' => implode(",\n        ", array_map(fn ($h) => "'$h'", $hidden)),
            '{{casts}}' => implode(",\n        ", array_map(
                function ($k, $v) {
                    // If it ends with "::class" treat it as raw (no quotes)
                    if (str_ends_with($v, '::class')) {
                        return "'$k' => $v";
                    }

                    // Otherwise wrap in quotes (normal string casts)
                    return "'$k' => '$v'";
                },
                array_keys($casts),
                $casts
            )),
            '{{appends}}' => ! empty($appends) ? "protected \$appends = [\n        '".implode("',\n        '", $appends)."'\n    ];" : '',
            '{{nameFields}}' => ! empty($nameFields) ? "protected \$nameFields = [\n        '".implode("',\n        '", $nameFields)."'\n    ];" : '',
            '{{relations}}' => $relationsCode,
        ];

        // Load stub
        $stubPath = $this->stubsPath.'/models/model.stub';
        $template = File::get($stubPath);

        // Replace placeholders
        $template = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Write file
        $filePath = $this->modelPath.'/'.$className.'.php';
        app(GenerateFileAction::class)($filePath, $template);

        $filePathRelative = str_replace(app_path('Models/'), '', $filePath);
        Log::channel('magic')->info("Model created: $filePathRelative");
    }

    /**
     * Map a field type to an Eloquent cast type, as Laravel understands it in model $casts.
     */
    protected function mapFieldTypeToCast(Field $field): ?string
    {
        $type = $field->type;

        // If enum → reference generated enum class
        if ($type === FieldType::ENUM && ! empty($field->values)) {
            return '\\App\\Enums\\'.Str::studly($field->getEntity()->getName()).Str::studly($field->name).'Enum::class';
        }

        return match ($type) {
            FieldType::DATE => 'date',
            FieldType::DATETIME, FieldType::TIMESTAMP => 'datetime',
            FieldType::BOOLEAN => 'boolean',
            FieldType::INTEGER => 'integer',
            FieldType::FLOAT, FieldType::DOUBLE, FieldType::DECIMAL => 'float',
            default => null,
        };
    }

    protected function buildRelationMethod(Relation $relation): string
    {
        $methodName = $relation->getRelationName();
        $relatedClass = $relation->getRelatedEntityName();
        $foreignKey = $relation->getForeignKey() ? "'{$relation->getForeignKey()}'" : '';

        $relationCall = match ($relation->getType()) {
            RelationType::HAS_ONE => "return \$this->hasOne($relatedClass::class, $foreignKey);",
            RelationType::HAS_MANY => "return \$this->hasMany($relatedClass::class, $foreignKey);",
            RelationType::BELONGS_TO => "return \$this->belongsTo($relatedClass::class, $foreignKey);",
            RelationType::BELONGS_TO_MANY => "return \$this->belongsToMany($relatedClass::class);",
            RelationType::MORPH_ONE => "return \$this->morphOne($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_MANY => "return \$this->morphMany($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_TO => 'return $this->morphTo();',
            RelationType::MORPH_TO_MANY => "return \$this->morphToMany($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPHED_BY_MANY => "return \$this->morphedByMany($relatedClass::class, '{$relation->getMorphName()}');",
            // default => "// Unknown relation type '{$relation->getType()->name}' for {$relation->getRelatedEntityName()}",
        };

        return <<<PHP
    /**
     * Relation method for {$relation->getType()->name} to {$relation->getRelatedEntityName()}.
     */
    public function $methodName()
    {
        $relationCall
    }
PHP;
    }
}
