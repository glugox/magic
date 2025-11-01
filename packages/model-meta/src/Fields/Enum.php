<?php

namespace Glugox\ModelMeta\Fields;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\FieldType;

class Enum extends Field
{
    /**
     * Allowed enum values.
     * @var array<string>
     */
    public array $values;

    /**
     * @param string $name
     * @param array<string> $values
     * @param string|null $label
     */
    public function __construct(string $name, array $values, ?string $label = null)
    {
        parent::__construct(
            FieldType::ENUM,
            $name,
            $label
        );

        $this->values = $values;
    }

    /**
     * Fluent constructor
     * @param string $name
     * @param array<string> $enumValues
     * @param string|null $label
     * @return Enum
     */
    public static function make(string $name, ?array $enumValues=[], ?string $label = null): static
    {
        return new static($name, $enumValues, $label);
    }

    /**
     * Set allowed enum values.
     * @param array<string> $values
     * @return $this
     */
    public function values(array $values): static
    {
        $this->values = $values;
        return $this;
    }
}
