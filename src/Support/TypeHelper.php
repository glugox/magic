<?php

namespace Glugox\Magic\Support;

class TypeHelper
{
    /**
     * Convert migration type to TypeScript type.
     */
    public static function migrationTypeToTsType(string $migrationType): string
    {
        return match ($migrationType) {
            'string', 'text', 'char', 'mediumText', 'longText' => 'string',
            'integer', 'bigInteger', 'bigIncrements', 'unsignedBigInteger' => 'number',
            'boolean' => 'boolean',
            'date', 'dateTime' => 'Date',
            'json' => 'object',
            default => 'any',
        };
    }
}
