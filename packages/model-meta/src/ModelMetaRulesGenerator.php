<?php

namespace Glugox\ModelMeta;

use Glugox\ModelMeta\Fields\Enum;
use Illuminate\Support\Str;

class ModelMetaRulesGenerator
{
    protected ModelMeta $meta;

    public function __construct(ModelMeta $meta)
    {
        $this->meta = $meta;
    }

    /**
     * Generate rules for both store and update actions.
     *
     * @return array{store: array<string, string[]>, update: array<string, string[]>}
     */
    public function generate(?string $recordId = null): array
    {
        return [
            'store'  => $this->rulesForAllFields('store', $recordId),
            'update' => $this->rulesForAllFields('update', $recordId),
            'update-selection' => [
                'added'   => 'array',
                'added.*' => 'integer|exists:' . $this->meta->tableName() . ',id',
                'removed'   => 'array',
                'removed.*' => 'integer|exists:' . $this->meta->tableName() . ',id',
            ],
            'bulk-destroy' => [
                'ids'   => 'required|array',
                'ids.*' => 'integer|exists:' . $this->meta->tableName() . ',id',
            ],
        ];
    }

    /**
     * Generate rules for all fields for a given action.
     *
     * @param string $action 'store' or 'update'
     * @return array<string, string[]> Array of field names to their validation rules.
     */
    protected function rulesForAllFields(string $action, ?string $recordId = null): array
    {
        $rules = [];

        foreach ($this->meta->fields() as $field) {
            $rules[$field->name] = $this->rulesForField($field, $action, $recordId);
            $nestedRules = $this->rulesForNestedField($field, $action);
            $rules = array_merge($rules, $nestedRules);
        }

        return $rules;
    }

    /**
     * Generate rules for nested fields (e.g. relations or JSON sub-fields).
     *
     * @param Field $field
     * @param string $action
     * @return array<string, string[]> Rules for nested fields (e.g. relations or JSON sub-fields).
     */
    protected function rulesForNestedField(Field $field, string $action): array
    {
        $rules = [];

        // TODO: Needs implementation for nested relations and JSON sub-fields
        /*if ($field->relation instanceof ModelMeta) {
            $nestedRules = (new self($field->relation))->generate()[$action] ?? [];
            foreach ($nestedRules as $nestedName => $nestedRuleSet) {
                $rules["{$field->name}.{$nestedName}"] = $nestedRuleSet;
            }
        }*/

        /*if ($field->type === 'json' && !empty($field->subFields)) {
            foreach ($field->subFields as $subField) {
                $rules["{$field->name}.{$subField->name}"] = $this->rulesForField($subField, $action);
            }
        }*/

        return $rules;
    }

    /**
     * Generate validation rules for a single field based on its properties.
     *
     * @param Field $field The field metadata.
     * @param string $action 'store' or 'update' to determine context.
     * @return array<string> Array of validation rules.
     */
    protected function rulesForField(Field $field, string $action, ?string $recordId = null): array
    {
        $rules = [];

        // Required / optional
        if ($action === 'store' && $field->required) {
            $rules[] = 'required';
        } elseif ($action === 'update' && $field->required) {
            $rules[] = 'sometimes';
        } else {
            $rules[] = 'nullable';
        }

        // Type-based rules
        switch ($field->type) {
            case 'integer':
                $rules[] = 'integer';
                if (isset($field->min)) $rules[] = 'min:' . $field->min;
                if (isset($field->max)) $rules[] = 'max:' . $field->max;
                break;

            case 'string':
                $rules[] = 'string';
                if (isset($field->max)) $rules[] = 'max:' . $field->max;
                break;
                
            case 'email':   
                $rules[] = 'email';
                if (isset($field->max)) $rules[] = 'max:' . $field->max;
                break;

            case 'float':
            case 'decimal':
                $rules[] = 'numeric';
                if (isset($field->min)) $rules[] = 'min:' . $field->min;
                if (isset($field->max)) $rules[] = 'max:' . $field->max;
                break;

            case 'enum':
                if (!empty($field->values) && $field instanceof Enum) {
                    /** @var string[] $values */
                    $values =  $field->values;
                    $rules[] = 'in:' . implode(',', $values);
                }
                break;

            case 'json':
                $rules[] = 'array';
                break;
        }

        // Unique constraint
        if ($field->unique) {

            $recordId = $recordId ?? ':id'; // Placeholder for current record ID in update context
            $table = $field->table ?? $this->meta->tableName();
            $column = $field->name;

            if ($action === 'update') {
                // Assume the current record ID is passed in a standard 'id' field
                $rules[] = "unique:{$table},{$column},{$recordId},id";
            } else {
                $rules[] = "unique:{$table},{$column}";
            }
        }

        // Name field must be required
        if ($field->main && $action === 'store' && !in_array('required', $rules)) {
            $rules[] = 'required';
        }

        return $rules;
    }
}
