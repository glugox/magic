<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Type\TsType;

class TypeHelper
{
    /**
     * Convert migration type to TypeScript type.
     */
    public static function migrationTypeToTsType(FieldType $migrationType): TsType
    {
        return match ($migrationType) {
            FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::MEDIUM_TEXT, FieldType::LONG_TEXT => TsType::STRING,
            FieldType::INTEGER, FieldType::BIG_INTEGER, FieldType::BIG_INCREMENTS, FieldType::UNSIGNED_BIG_INTEGER => TsType::NUMBER,
            FieldType::BOOLEAN => TsType::BOOLEAN,
            FieldType::DATE, FieldType::DATETIME => TsType::DATE,
            FieldType::JSON, FieldType::JSONB => TsType::OBJECT,
            default => TsType::ANY,
        };
    }

    /**
     * Convert RelationType to FieldType
     */
    public static function relationTypeToFieldType(RelationType $relationType): FieldType
    {
        return match ($relationType) {
            RelationType::BELONGS_TO => FieldType::BELONGS_TO,
            RelationType::HAS_MANY, RelationType::BELONGS_TO_MANY => FieldType::HAS_MANY,
            RelationType::HAS_ONE => FieldType::HAS_ONE,
            RelationType::MORPH_ONE => FieldType::HAS_ONE,
            RelationType::MORPH_MANY => FieldType::HAS_MANY,
            RelationType::MORPH_TO => FieldType::BELONGS_TO,
            default => FieldType::STRING,
        };
    }
}
