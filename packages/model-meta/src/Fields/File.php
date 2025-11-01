<?php

namespace Glugox\ModelMeta\Fields;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\FieldType;

class File extends Field
{
    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct(
            FieldType::FILE,
            $name,
            $label
        );
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }
}