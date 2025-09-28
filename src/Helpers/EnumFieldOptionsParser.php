<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Field\EnumFieldOption;
use Illuminate\Support\Str;

class EnumFieldOptionsParser
{
    /**
     * If receives:
     * ["active", "inactive", "discontinued"]
     * Than these values will be used as both name and label will transformed to "Active", "Inactive", "Discontinued"
     *
     * If receives:
     * [{"name": "active", "label": "Is Active"}, "inactive", "discontinued"]
     * Than it will check if we explicitly have a label, if not it will transform the name to "Inactive", "Discontinued"
     *
     * @param array{
     *     string|array<string, string>
     * } $input
     * @return EnumFieldOption[]
     */
    public static function parse(array $input): array
    {
        $options = [];
        foreach ($input as $option) {
            if (is_string($option)) {
                $name = mb_trim($option);
                $label = Str::title(str_replace('_', ' ', $name));
                $options[] = new EnumFieldOption(name: $name, label: $label);
                // @phpstan-ignore-next-line
            } elseif (is_array($option) && isset($option['name'])) {
                $name = mb_trim($option['name']);
                $label = isset($option['label']) ? mb_trim($option['label']) : Str::title(str_replace('_', ' ', $name));
                $options[] = new EnumFieldOption(name: $name, label: $label);
            }
        }

        return $options;

    }
}
