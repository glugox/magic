<?php

namespace Glugox\ModelMeta\Fields;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\FieldType;

class Id extends Field
{
    public function __construct(string $name = 'id', ?string $label = 'ID')
    {
        parent::__construct(FieldType::ID, $name, $label);
    }

    public static function make(string $name = 'id', ?string $label = 'ID'): static
    {
        return new static($name, $label);
    }
}