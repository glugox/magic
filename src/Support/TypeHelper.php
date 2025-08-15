<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Support\Config\FieldType;

class TypeHelper
{
    /**
     * Convert migration type to TypeScript type.
     */
    public static function migrationTypeToTsType(FieldType $migrationType): string
    {
        return match ($migrationType) {
            FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::MEDIUM_TEXT, FieldType::LONG_TEXT => 'string',
            FieldType::INTEGER, FieldType::BIG_INTEGER, FieldType::BIG_INCREMENTS, FieldType::UNSIGNED_BIG_INTEGER => 'number',
            FieldType::BOOLEAN => 'boolean',
            FieldType::DATE, FieldType::DATETIME => 'Date',
            FieldType::JSON, FieldType::JSONB => 'object',
            default => 'any',
        };
    }
}
