<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModelBuilderService
{
    protected string $modelPath;

    public function __construct(
        protected Config $config
    ) {
        $this->modelPath = app_path('Models');
        if (! File::exists($this->modelPath)) {
            File::makeDirectory($this->modelPath, 0755, true);
        }
    }

    /**
     * Build all models based on the configuration.
     */
    public function build(): void
    {
        foreach ($this->config->entities as $entity) {
            $this->generateModel($entity);
        }
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
        $fields = $entity->getFields();

        // Fillable fields
        // Start from the defined fields
        $fillable = $entity->getFillableFieldsNames();
        $hidden = $entity->getHiddenFieldsNames();
        $casts = $entity->getCasts();

        if (isset($modelPresets[$entityName])) {
            $preset = $modelPresets[$entityName];

            $extends = $preset['extends'] ?? $extends;
            $traits = $preset['traits'] ?? $traits;

            // If preset has default fields, use them.
            // Add them to the entity fields if they are not already present.
            $defaultFields = $preset['default_fields'] ?? [];
            foreach ($defaultFields as $defaultField) {
                $name = $defaultField['name'];
                if (! $entity->hasField($name)) {
                    $entity->addField(Field::fromConfig($defaultField));
                }
            }

            // Merge preset fillable
            $presetFillable = $preset['fillable'] ?? [];
            foreach ($presetFillable as $item) {
                if (! in_array($item, $fillable)) {
                    $fillable[] = $item;
                }
            }

            // Merge preset hidden
            $presetHidden = $preset['hidden'] ?? [];
            foreach ($presetHidden as $item) {
                if (! in_array($item, $hidden)) {
                    $hidden[] = $item;
                }
            }

            // Merge preset casts
            $presetCasts = $preset['casts'] ?? [];
            foreach ($presetCasts as $key => $value) {
                if (! isset($casts[$key])) {
                    $casts[$key] = $value;
                }
            }
        }

        // Determine if we should have factory for this model.
        // TODO: Get from configuration if we should use HasFactory trait. In other words,
        // if we want to generate factories for this model.
        if (! in_array(HasFactory::class, $traits)) {
            $traits[] = HasFactory::class;
        }

        // If no casts given in preset, infer from fields
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

        // Namespace use statements
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
 *
 * @Description
 */
class $className extends {$extends}
{
    $traitsUseStr
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected \$fillable = [
        $fillableStr
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected \$hidden = [
        $hiddenStr
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected \$casts = [
        $castsStr
    ];

$relationsCode
}
PHP;

        $filePath = $this->modelPath.'/'.$className.'.php';
        File::put($filePath, $template);

        $filePathRelative = str_replace(app_path('Models/'), '', $filePath);
        Log::channel('magic')->info("Model created: $filePathRelative");
    }

    protected function shortClassName(string $fqcn): string
    {
        return class_basename($fqcn);
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

    /**
     * Build the relation method code based on the relation type.
     */
    protected function buildRelationMethod(Relation $relation): string
    {
        $methodName = $relation->getRelationName();
        $relatedClass = $relation->getEntityName();
        $foreignKey = $relation->getForeignKey() ? "'{$relation->getForeignKey()}'" : '';
        $localKey = $relation->getLocalKey() ? ", '{$relation->getLocalKey()}'" : '';

        $relationCall = match ($relation->getType()) {
            RelationType::HAS_ONE => "return \$this->hasOne($relatedClass::class, $foreignKey$localKey);",
            RelationType::HAS_MANY => "return \$this->hasMany($relatedClass::class, $foreignKey$localKey);",
            RelationType::BELONGS_TO => "return \$this->belongsTo($relatedClass::class, $foreignKey$localKey);",
            RelationType::BELONGS_TO_MANY => "return \$this->belongsToMany($relatedClass::class);",
            RelationType::MORPH_ONE => "return \$this->morphOne($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_MANY => "return \$this->morphMany($relatedClass::class, '{$relation->getMorphName()}');",
            RelationType::MORPH_TO => 'return $this->morphTo();',
            default => "// Unknown relation type '{$relation->getType()->name}' for {$relation->getEntityName()}",
        };

        return <<<PHP
    /**
     * Relation method for {$relation->getType()->name} to {$relation->getEntityName()}.
     */
    public function $methodName()
    {
        $relationCall
    }
PHP;
    }
}
