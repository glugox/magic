<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Enums\CrudActionType;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\EntityRuleSet;
use Glugox\Magic\Validation\RuleSetHelper;
use Glugox\Magic\Validation\ValidationRule;
use Glugox\Magic\Validation\ValidationRuleSet;

class ValidationHelper
{
    /**
     * @return EntityRuleSet Aggregated validation rules for the entity like ['field1' => ['required', 'string', 'max:255'], 'field2' => ['nullable', 'integer', 'min:0'] ... ]
     */
    public function make(
        Entity $entity
    ): EntityRuleSet {
        $entityRuleSet = new EntityRuleSet();
        foreach ($entity->getFields() as $field) {
            $rulesCreate = new ValidationRuleSet(
                fieldName: $field->name,
                rules: $this->rulesForField($field, CrudActionType::CREATE)
            );
            $rulesUpdate = new ValidationRuleSet(
                fieldName: $field->name,
                rules: $this->rulesForField($field, CrudActionType::UPDATE)
            );
            $entityRuleSet->setCreateRuleSetForField($field->name, $rulesCreate);
            $entityRuleSet->setUpdateRuleSetForField($field->name, $rulesUpdate);
        }

        return $entityRuleSet;
    }

    /**
     * @return ValidationRule[] Aggregated validation rules like ['required', 'string', 'max:255']
     */
    protected function rulesForField(
        Field $field,
        ?CrudActionType $categoryType = CrudActionType::CREATE
    ): array {
        return RuleSetHelper::rulesFor($field, $categoryType);

    }
}
