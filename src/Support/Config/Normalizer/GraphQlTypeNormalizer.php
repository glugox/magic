<?php

declare(strict_types=1);

namespace Glugox\Magic\Support\Config\Normalizer;

use Glugox\Magic\Enums\GraphQlType;
use Glugox\Magic\Support\Config\FieldType;
use InvalidArgumentException;

final class GraphQlTypeNormalizer
{
    /**
     * Normalize SDL raw string to FieldType enum.
     * Handles scalars and relations.
     *
     * @param  string  $raw
     * @param  array<string,mixed>  $directives
     */
    public static function normalize(string|GraphQlType $raw, array $directives = []): FieldType
    {
        if ($raw instanceof GraphQlType) {
            return self::toFieldType($raw);
        }

        $baseType = mb_trim($raw, '[]!');

        // Check if scalar type
        foreach (GraphQlType::cases() as $case) {
            if ($case->value === $baseType) {
                return self::toFieldType($case);
            }
        }

        // Otherwise, relation type (based on directives)
        return match (true) {
            isset($directives['hasMany']) => FieldType::HAS_MANY,
            isset($directives['hasOne']) => FieldType::HAS_ONE,
            isset($directives['belongsToMany']) => FieldType::BELONGS_TO_MANY,
            default => FieldType::BELONGS_TO,
        };
    }

    /**
     * Map GraphQlType to FieldType enum
     */
    public static function toFieldType(GraphQlType $type): FieldType
    {
        return match ($type) {
            GraphQlType::ID => FieldType::ID,
            GraphQlType::STRING => FieldType::STRING,
            GraphQlType::BOOLEAN => FieldType::BOOLEAN,
            GraphQlType::INTEGER => FieldType::INTEGER,
            GraphQlType::FLOAT => FieldType::FLOAT,
            GraphQlType::DECIMAL => FieldType::DECIMAL,
            GraphQlType::DATE => FieldType::DATE,
            GraphQlType::DATETIME => FieldType::DATETIME,
            GraphQlType::JSON => FieldType::JSON,
            GraphQlType::PASSWORD => FieldType::PASSWORD,
            GraphQlType::EMAIL => FieldType::EMAIL,
            GraphQlType::UUID => FieldType::UUID,
            GraphQlType::TEXT => FieldType::TEXT,
            GraphQlType::LONG_TEXT => FieldType::LONG_TEXT,
            GraphQlType::URL => FieldType::URL,
            GraphQlType::TIME => FieldType::TIME,
            GraphQlType::YEAR => FieldType::YEAR,
            GraphQlType::SECRET => FieldType::SECRET,
            GraphQlType::TOKEN => FieldType::TOKEN,
            GraphQlType::PHONE => FieldType::PHONE,
            GraphQlType::USERNAME => FieldType::USERNAME,
            GraphQlType::SLUG => FieldType::SLUG,
            GraphQlType::FILE => FieldType::FILE,
            GraphQlType::IMAGE => FieldType::IMAGE,
            GraphQlType::IP_ADDRESS => FieldType::IP_ADDRESS,
            GraphQlType::CHAR => FieldType::CHAR,
            GraphQlType::SMALL_INTEGER => FieldType::SMALL_INTEGER,
            GraphQlType::TINY_INTEGER => FieldType::TINY_INTEGER,
            GraphQlType::UNSIGNED_INTEGER => FieldType::UNSIGNED_INTEGER,
            GraphQlType::UNSIGNED_SMALL_INTEGER => FieldType::UNSIGNED_SMALL_INTEGER,
            GraphQlType::UNSIGNED_TINY_INTEGER => FieldType::UNSIGNED_TINY_INTEGER,
            GraphQlType::BIG_INTEGER => FieldType::BIG_INTEGER,
            GraphQlType::UNSIGNED_BIG_INTEGER => FieldType::UNSIGNED_BIG_INTEGER,
            GraphQlType::BIG_INCREMENTS => FieldType::BIG_INCREMENTS,
            GraphQlType::FOREIGN_ID => FieldType::FOREIGN_ID,
            GraphQlType::DOUBLE => FieldType::DOUBLE,
            GraphQlType::MEDIUM_TEXT => FieldType::MEDIUM_TEXT,
            GraphQlType::BINARY => FieldType::BINARY,
            GraphQlType::TIMESTAMP => FieldType::TIMESTAMP,
            GraphQlType::ENUM => FieldType::ENUM,
        };
    }

    /**
     * Extract GraphQlType enum from SDL string
     *
     * @throws InvalidArgumentException
     */
    public static function extractGraphQlType(string $raw): GraphQlType
    {
        $baseType = mb_trim($raw, '[]!');

        foreach (GraphQlType::cases() as $case) {
            if ($case->value === $baseType) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unknown GraphQL type: {$raw}");
    }

    /**
     * Determine if SDL field is nullable
     */
    public static function isNullable(string $raw): bool
    {
        return ! str_ends_with(mb_trim($raw), '!');
    }

    /**
     * Determine if SDL field is a list
     */
    public static function isList(string $raw): bool
    {
        $raw = mb_trim($raw);

        return str_starts_with($raw, '[') && str_ends_with($raw, ']');
    }
}
