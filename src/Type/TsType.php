<?php

namespace Glugox\Magic\Type;

use Glugox\Magic\Support\Config\FieldType;

enum TsType: string
{
    case STRING = 'string';       // TS primitive
    case NUMBER = 'number';       // TS primitive
    case BOOLEAN = 'boolean';     // TS primitive
    case DATE = 'Date';           // TS class, must be uppercase
    case OBJECT = 'object';       // TS primitive
    case ANY = 'any';             // TS primitive
    case VOID = 'void';           // TS primitive
    case NEVER = 'never';         // TS primitive
    case UNKNOWN = 'unknown';     // TS primitive
    case NULL = 'null';           // TS primitive
    case UNDEFINED = 'undefined'; // TS primitive
    case SYMBOL = 'symbol';       // TS primitive

    public static function fromFieldType(FieldType $fieldType): TsType
    {
        return match ($fieldType) {
            FieldType::ID,
            FieldType::BIG_INCREMENTS,
            FieldType::BIG_INTEGER,
            FieldType::DECIMAL,
            FieldType::DOUBLE,
            FieldType::FLOAT,
            FieldType::INTEGER,
            FieldType::SMALL_INTEGER,
            FieldType::TINY_INTEGER,
            FieldType::UNSIGNED_BIG_INTEGER,
            FieldType::UNSIGNED_INTEGER,
            FieldType::UNSIGNED_SMALL_INTEGER,
            FieldType::UNSIGNED_TINY_INTEGER,
            FieldType::YEAR => TsType::NUMBER,

            FieldType::BOOLEAN => TsType::BOOLEAN,

            FieldType::DATE,
            FieldType::DATETIME,
            FieldType::TIME,
            FieldType::TIMESTAMP => TsType::DATE,

            FieldType::CHAR,
            FieldType::STRING,
            FieldType::TEXT,
            FieldType::LONG_TEXT,
            FieldType::MEDIUM_TEXT,
            FieldType::EMAIL,
            FieldType::PASSWORD,
            FieldType::URL,
            FieldType::SECRET,
            FieldType::TOKEN,
            FieldType::USERNAME,
            FieldType::PHONE,
            FieldType::SLUG,
            FieldType::UUID,
            FieldType::ENUM => TsType::STRING,

            FieldType::BINARY,
            FieldType::FILE,
            FieldType::IMAGE,
            FieldType::JSON,
            FieldType::JSONB,
            FieldType::FOREIGN_ID,
            FieldType::BELONGS_TO,
            FieldType::HAS_MANY,
            FieldType::HAS_ONE,
            FieldType::BELONGS_TO_MANY => TsType::OBJECT,

            default => TsType::ANY,
        };
    }
}
