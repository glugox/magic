<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\RuleSet;
use Glugox\Magic\Validation\RuleSet\RuleSetCategoryType;

class ValidationHelper
{
    public function make(
        Entity $entity,
        ?RuleSetCategoryType $categoryType = RuleSetCategoryType::CREATE
    ): array
    {
        $rules = [];
        foreach ($entity->getFields() as $field) {
            $fieldRules = $this->rulesForField($field, $categoryType);
            if (!empty($fieldRules)) {
                $rules[$field->name] = $fieldRules;
            }
        }
        return $rules;
    }

    /**
     * Make rules for creating an entity
     */
    public function makeCreate(Entity $entity): array
    {
        return $this->make($entity, RuleSetCategoryType::CREATE);
    }

    /**
     * Make rules for updating an entity
     */
    public function makeUpdate(Entity $entity): array
    {
        return $this->make($entity, RuleSetCategoryType::UPDATE);
    }

    protected function rulesForField(
        Field $field,
        ?RuleSetCategoryType $categoryType = RuleSetCategoryType::CREATE
    ): array
    {
        $rules = RuleSet::rulesFor($field, $categoryType);

        return array_values($rules);
    }
}

