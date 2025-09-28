<?php

namespace Glugox\Magic\Support\Config\Field;

use Illuminate\Support\Str;

class EnumFieldOption
{
    public function __construct(
        public string $name,
        public ?string $label
    ) {}

    /**
     * To string representation of the enum option.
     */
    public function __toString(): string
    {
        $name = $this->name;
        $label = $this->label === null ? Str::title(str_replace('_', ' ', $name)) : $this->label;

        return '{ "name": "'.$name.'", "label": "'.$label.'" }';
    }
}
