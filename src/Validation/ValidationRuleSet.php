<?php

namespace Glugox\Magic\Validation;

class ValidationRuleSet
{
    /**
     * Constructor
     */
    public function __construct(
        /**
         * Field name
         */
        protected string $fieldName,
        /**
         * Rules for create action
         *
         * @var ValidationRule[]
         */
        protected $rules = [],
    ) {}

    /**
     * String representation of the rules
     */
    public function __toString(): string
    {
        return implode('|', array_map(fn ($r) => (string) $r, $this->rules));
    }

    /**
     * @return ValidationRule[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Add rules for create
     */
    public function addRule(ValidationRule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }
}
