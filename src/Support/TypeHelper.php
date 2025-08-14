<?php

namespace Glugox\Magic\Support;

class TypeHelper
{
    /**
     * Convert migtation type to TypeScript type.
     */
    public static function migrationTypeToTsType(string $migrationType): string
    {
        switch ($migrationType) {
            case 'string':
            case 'text':
            case 'char':
            case 'mediumText':
            case 'longText':
                return 'string';
            case 'integer':
            case 'bigInteger':
            case 'bigIncrements':
            case 'unsignedBigInteger':
                return 'number';
            case 'boolean':
                return 'boolean';
            case 'date':
            case 'dateTime':
                return 'Date';
            case 'json':
                return 'object';
            default:
                return 'any'; // Fallback for unsupported types
        }
    }
}
