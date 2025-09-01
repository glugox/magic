<?php

namespace Glugox\Magic\Validation;

use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Enums\CrudActionType;

class RuleSet
{
    public static function userRulesForField(Field $field, $categoryType) : array
    {
        $rules = [];
        if ($field->required) {
            $rules[] = 'required';
        } elseif ($field->nullable) {
            $rules[] = 'nullable';
        } elseif ($field->sometimes) {
            $rules[] = 'sometimes';
        }

        // Min / Max
        if (isset($field->min)) {
            $rules[] = 'min:' . $field->min;
        }
        if (isset($field->max)) {
            $rules[] = 'max:' . $field->max;
        }

        return $rules;
    }

    public static function rulesFor(Field $field, ?CrudActionType $ruleSetCategory = null): array
    {
        $userRules = self::userRulesForField($field, $ruleSetCategory);

        $presetRules = match ($field->type) {

            // ---------------------------
            // IDs / Primary Keys
            // ---------------------------
            FieldType::ID       => ['integer', 'min:1'],
            FieldType::UUID     => ['string', 'size:36', 'regex:/^[0-9a-fA-F-]{36}$/'],
            FieldType::BIG_INCREMENTS => ['integer','min:1'],
            FieldType::BIG_INTEGER => ['integer'],
            FieldType::SMALL_INTEGER => ['integer'],
            FieldType::TINY_INTEGER => ['integer'],
            FieldType::UNSIGNED_BIG_INTEGER => ['integer','min:0'],
            FieldType::UNSIGNED_INTEGER => ['integer','min:0'],
            FieldType::UNSIGNED_SMALL_INTEGER => ['integer','min:0'],
            FieldType::UNSIGNED_TINY_INTEGER => ['integer','min:0'],

            // ---------------------------
            // Strings / Text
            // ---------------------------
            FieldType::STRING   => ['string', 'max:255'],
            FieldType::TEXT     => ['string'],
            FieldType::LONG_TEXT => ['string'],
            FieldType::MEDIUM_TEXT => ['string'],
            FieldType::CHAR => ['string','size:1'],
            FieldType::SLUG     => ['string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            FieldType::USERNAME => ['string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],

            // ---------------------------
            // Email / Password
            // ---------------------------
            FieldType::EMAIL    => ['string', 'email', 'max:255'],
            FieldType::PASSWORD => ['nullable', 'string', 'min:8', 'confirmed'],

            // ---------------------------
            // Boolean / Numeric / Decimal
            // ---------------------------
            FieldType::BOOLEAN  => ['boolean'],
            FieldType::INTEGER  => ['integer'],
            FieldType::FLOAT    => ['numeric'],
            FieldType::DECIMAL => ['numeric'],
            FieldType::DOUBLE => ['numeric'],

            // ---------------------------
            // Date / Time / Year
            // ---------------------------
            FieldType::DATE     => ['date'],
            FieldType::DATETIME => ['date_format:Y-m-d H:i:s'],
            FieldType::TIME     => ['date_format:H:i:s'],
            FieldType::TIMESTAMP => ['date'],
            FieldType::YEAR     => ['digits:4'],

            // ---------------------------
            // Enum / JSON
            // ---------------------------
            FieldType::ENUM     => ['in:' . implode(',', $field->values ?? [])],
            FieldType::JSON     => ['array'],
            FieldType::JSONB    => ['array'],

            // ---------------------------
            // Files / Images
            // ---------------------------
            FieldType::FILE     => ['file'],
            FieldType::IMAGE    => ['image','max:10240'],

            // ---------------------------
            // URL / Phone
            // ---------------------------
            FieldType::URL      => ['url'],
            FieldType::PHONE    => ['string','regex:/^\+?[0-9]{7,15}$/'],

            // ---------------------------
            // Binary / Secret / Token
            // ---------------------------
            FieldType::BINARY   => ['string'],
            FieldType::SECRET   => ['string'],
            FieldType::TOKEN    => ['string'],

            // ---------------------------
            // IP
            // ---------------------------
            FieldType::IP_ADDRESS => ['ip'],

            // ---------------------------
            // Relationships
            // ---------------------------
            FieldType::FOREIGN_ID => ['integer','exists:table,id'],
            FieldType::BELONGS_TO => ['integer','exists:table,id'],
            FieldType::HAS_ONE => [], // validate on related
            FieldType::HAS_MANY => [], // validate on related
            FieldType::BELONGS_TO_MANY => [], // validate as array of ids if needed
        };

        // Merge preset rules and user-defined rules
        $rules = array_merge($presetRules, $userRules);
        $rules = self::validateRules($rules, $field, $ruleSetCategory);

        $hasRequiredRule = in_array('required', $rules);
        $hasSometimesRule = in_array('sometimes', $rules);
        $hasNullableRule = in_array('nullable', $rules);

        $hasAnyAppearRule = $hasRequiredRule || $hasSometimesRule || $hasNullableRule;

        // ---------------------------
        // Apply default required / nullable logic
        // ---------------------------


        $rulesFromField = $field->rules ?? [];
        $rules = array_merge($rules, $rulesFromField);

        // Manage required / nullable / sometimes based on rule set category
        if ($ruleSetCategory === CrudActionType::CREATE && !$field->isId()) {
           $appearRule = 'required';
        } elseif ($ruleSetCategory === CrudActionType::UPDATE) {
           $appearRule = 'nullable';
        }

        // Add the appear rule if none of the appear rules are present
        if (!$hasAnyAppearRule && isset($appearRule)) {
            array_unshift($rules, $appearRule);
        }

        // Return rules
        return $rules;
    }

    /**
     * Validate or adjust rules if needed
     */
    private static function validateRules(array $rules, Field $field, ?CrudActionType $ruleSetCategory)
    {
        // TODO: Implement any additional validation or adjustment of rules if needed
        return $rules;
    }
}

