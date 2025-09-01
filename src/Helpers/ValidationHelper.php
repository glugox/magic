<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\RuleSet;
use Glugox\Magic\Enums\CrudActionType;

class ValidationHelper
{
    public function make(
        Entity $entity,
        ?CrudActionType $categoryType = CrudActionType::CREATE
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
        return $this->make($entity, CrudActionType::CREATE);
    }

    /**
     * Make rules for updating an entity
     */
    public function makeUpdate(Entity $entity): array
    {
        return $this->make($entity, CrudActionType::UPDATE);
    }

    protected function rulesForField(
        Field $field,
        ?CrudActionType $categoryType = CrudActionType::CREATE
    ): array
    {
        $rules = RuleSet::rulesFor($field, $categoryType);

        return array_values($rules);
    }
}

