<?php

namespace Glugox\Magic\Validation;

class EntityRuleSet
{
    /**
     * @var ValidationRuleSet[] Rules for create action
     */
    protected array $create = [];

    /**
     * @var ValidationRuleSet[] Rules for update action
     */
    protected array $update = [];

    /**
     * Add rules for create action
     */
    public function setCreateRuleSetForField(string $fieldName, ValidationRuleSet $ruleSet): self
    {
        $this->create[$fieldName] = $ruleSet;

        return $this;
    }

    /**
     * Add rules for update action
     */
    public function setUpdateRuleSetForField(string $fieldName, ValidationRuleSet $ruleSet): self
    {
        $this->update[$fieldName] = $ruleSet;

        return $this;
    }

    /**
     * Get rules for create action
     *
     * @return array <string, ValidationRuleSet>
     */
    public function getCreateRules(): array
    {
        return $this->create;
    }

    /**
     * Get rules for update action
     *
     * @return array <string, ValidationRuleSet>
     */
    public function getUpdateRules(): array
    {
        return $this->update;
    }

    /**
     * Get ruleset for a specific field for create action
     *
     * @return ValidationRuleSet|null
     */
    public function getCreateRuleSetForField(string $name)
    {
        return $this->create[$name] ?? null;
    }

    /**
     * Get ruleset for a specific field for update action
     *
     * @return ValidationRuleSet|null
     */
    public function getUpdateRuleSetForField(string $name): ?ValidationRuleSet
    {
        return $this->update[$name] ?? null;
    }
}
