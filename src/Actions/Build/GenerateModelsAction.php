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

    public function __construct()
    {
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
        $traits = [];
        $appends = [];
        $fields = $entity->getFields();

        $fillable = $entity->getFillableFieldsNames();
        $hidden = $entity->getHiddenFieldsNames();
        $casts = $entity->getCasts();
        $nameFields = $entity->getNameFieldsNames();

        if (isset($modelPresets[$entityName])) {
            $preset = $modelPresets[$entityName];

            $extends = $preset['extends'] ?? $extends;
            $traits = $preset['traits'] ?? $traits;

            // Default fields
            $defaultFields = $preset['default_fields'] ?? [];
            foreach ($defaultFields as $defaultField) {
                $name = $defaultField['name'];
                if (! $entity->hasField($name)) {
                    $entity->addField(Field::fromConfig($defaultField));
                }
            }

            // Merge preset fillable
            foreach ($preset['fillable'] ?? [] as $item) {
                if (! in_array($item, $fillable)) {
                    $fillable[] = $item;
                }
            }

            // Merge preset hidden
            foreach ($preset['hidden'] ?? [] as $item) {
                if (! in_array($item, $hidden)) {
                    $hidden[] = $item;
                }
            }

            // Merge preset casts
            foreach ($preset['casts'] ?? [] as $key => $value) {
                if (! isset($casts[$key])) {
                    $casts[$key] = $value;
                }
            }
        }

        // Ensure HasFactory
        if (! in_array(HasFactory::class, $traits)) {
            $traits[] = HasFactory::class;
        }

        // Ensure "name" field
        if (! $entity->hasField('name')) {
            $traits[] = 'App\Traits\HasName';
            $appends[] = 'name';
        }

        // Infer casts if empty
        if (empty($casts)) {
            foreach ($fields as $field) {
                $type = $field->type;
                $cast = $this->mapFieldTypeToCast($type);
                if ($cast) {
                    $name = $field instanceof Field ? $field->name : $field['name'];
                    $casts[$name] = $cast;
                }
            }
        }

        // Relations
        $relationsCode = '';
        foreach ($entity->getRelations() as $relation) {
            $relationsCode .= $this->buildRelationMethod($relation)."\n\n";
        }

        // Format arrays
        $fillableStr = implode(",\n        ", array_map(fn ($f) => "'$f'", $fillable));
        $hiddenStr = implode(",\n        ", array_map(fn ($h) => "'$h'", $hidden));
        $castsStr = implode(",\n        ", array_map(fn ($k, $v) => "'$k' => '$v'", array_keys($casts), $casts));

        // Traits
        $traitsUseStr = '';
        if (! empty($traits)) {
            $traitsUseStr = 'use '.implode(', ', array_map(fn ($t) => class_basename($t), $traits)).';';
        }

        // Appends
        $appendsStr = '';
        if (! empty($appends)) {
            $appendsFieldsStr = implode(",\n        ", array_map(fn ($a) => "'$a'", $appends));
            $appendsStr .= "\n    protected \$appends = [\n        $appendsFieldsStr\n    ];";
        }

        if (! empty($nameFields)) {
            $nameFieldsStr = implode(', ', array_map(fn ($n) => "'$n'", $nameFields));
            $appendsStr .= "\n    protected \$nameFields = [\n        $nameFieldsStr\n    ];";
        }



        // Use statements for traits
        $useStatements = [];
        foreach ($traits as $trait) {
            $useStatements[] = "use {$trait};";
        }
        $useStatementsStr = implode("\n", array_unique($useStatements));

        // Template
        $template = <<<PHP
<?php

namespace App\Models;
$useStatementsStr
/**
 * $className model class.
 */
class $className extends {$extends}
{
    $traitsUseStr

    // Fillable fields, e.g. for mass assignment
    protected \$fillable = [
        $fillableStr
    ];
    // Hidden fields, e.g. for password
    protected \$hidden = [
        $hiddenStr
    ];
    // Casts
    protected \$casts = [
        $castsStr
    ];
    $appendsStr
$relationsCode
}
PHP;

        $filePath = $this->modelPath.'/'.$className.'.php';
        app(GenerateFileAction::class)($filePath, $template);

        $filePathRelative = str_replace(app_path('Models/'), '', $filePath);
        Log::channel('magic')->info("Model created: $filePathRelative");
    }

    protected function mapFieldTypeToCast(FieldType $type): ?string
    {
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
        $localKey = $relation->getLocalKey() ? ", '{$relation->getLocalKey()}'" : '';

        $relationCall = match ($relation->getType()) {
            RelationType::HAS_ONE => "return \$this->hasOne($relatedClass::class, $foreignKey);",
            RelationType::HAS_MANY => "return \$this->hasMany($relatedClass::class, $foreignKey);",
            RelationType::BELONGS_TO => "return \$this->belongsTo($relatedClass::class, $foreignKey);",
            RelationType::BELONGS_TO_MANY => "return \$this->belongsToMany($relatedClass::class);",
            RelationType::MORPH_ONE => "return \$this->morphOne($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_MANY => "return \$this->morphMany($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_TO => 'return $this->morphTo();',
            default => "// Unknown relation type '{$relation->getType()->name}' for {$relation->getRelatedEntityName()}",
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
