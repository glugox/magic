<?php

namespace Glugox\Magic\Validation;

readonly class ValidationRule
{
    public function __construct(

        /**
         * The validation rule signature, e.g. "max:255"
         */
        protected string $signature
    ){}

    /**
     * String representation of the rule
     */
    public function __toString(): string
    {
        return $this->signature;
    }
}
