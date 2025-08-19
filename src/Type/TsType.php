<?php

namespace Glugox\Magic\Type;

use Glugox\Magic\Support\Config\FieldType;

/**
 * TypeScript types for entities and fields.
 * This enum is used to define the TypeScript types for various field types.
 */
enum TsType: string
{
    case STRING = 'string';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case DATE = 'Date';
    case OBJECT = 'object';
    case ANY = 'any';

    /**
     * Convert migration type to TypeScript type.
     */
    public static function fromMigrationType(string $migrationType): self
    {
        $type = FieldType::tryFrom($migrationType);

        return match ($type) {
            // String types
            FieldType::STRING,
            FieldType::TEXT,
            FieldType::CHAR,
            FieldType::MEDIUM_TEXT,
            FieldType::LONG_TEXT => self::STRING,

            // Numeric types
            FieldType::INTEGER,
            FieldType::BIG_INTEGER,
            FieldType::BIG_INCREMENTS,
            FieldType::UNSIGNED_BIG_INTEGER,
            FieldType::UNSIGNED_INTEGER,
            FieldType::SMALL_INTEGER,
            FieldType::UNSIGNED_SMALL_INTEGER,
            FieldType::TINY_INTEGER,
            FieldType::UNSIGNED_TINY_INTEGER,
            FieldType::FLOAT,
            FieldType::DOUBLE,
            FieldType::DECIMAL => self::NUMBER,

            // Boolean types
            FieldType::BOOLEAN => self::BOOLEAN,

            // Date and time types
            FieldType::DATE,
            FieldType::DATETIME,
            FieldType::TIME,
            FieldType::TIMESTAMP,
            FieldType::YEAR => self::DATE,

            // Object types
            FieldType::JSON,
            FieldType::JSONB => self::OBJECT,

            // Default case for any other type
            default => self::ANY,
        };
    }
}
