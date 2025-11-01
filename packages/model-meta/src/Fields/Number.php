<?php

namespace Glugox\ModelMeta\Fields;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\FieldType;

class Number extends Field
{
    public function __construct(
        string $name,
        ?string $label = null,
        ?int $default = null,
        ?int $min = null,
        ?int $max = null
    ) {
        parent::__construct(
            FieldType::NUMBER,
            $name,
            $label,
            rules: [],
            nullable: false,
            default: $default,
            min: $min,
            max: $max
        );
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    public function min(int|float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(int|float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }
}
