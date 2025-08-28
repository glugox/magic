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
    public function migrationTypeToTsType(FieldType|string $migrationType): TsType
    {
        if (is_string($migrationType)) {
            $migrationType = FieldType::tryFrom($migrationType) ?? FieldType::STRING;
        }

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
    public function relationTypeToFieldType(RelationType $relationType): FieldType
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

    /**
     * Return empty value for a given FieldType
     */
    public function emptyValueForFieldType(FieldType $fieldType): mixed
    {
        return match ($fieldType) {
            FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::EMAIL, FieldType::MEDIUM_TEXT, FieldType::LONG_TEXT => '',
            FieldType::INTEGER, FieldType::BIG_INTEGER, FieldType::BIG_INCREMENTS, FieldType::UNSIGNED_BIG_INTEGER => 0,
            FieldType::BOOLEAN => false,
            FieldType::JSON, FieldType::JSONB, FieldType::HAS_MANY => [],
            default => null,
        };
    }
}
