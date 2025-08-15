<?php

namespace Glugox\Magic\Support\Config;

enum FieldType: string
{

    case DATE = 'date';
    case DATETIME = 'dateTime';
    case TIME = 'time';
    case TIMESTAMP = 'timestamp';
    case BIG_INCREMENTS = 'bigIncrements';
    case UNSIGNED_BIG_INTEGER = 'unsignedBigInteger';
    case STRING = 'string';
    case TEXT = 'text';
    case LONG_TEXT = 'longText';
    case INTEGER = 'integer';
    case UNSIGNED_INTEGER = 'unsignedInteger';
    case SMALL_INTEGER = 'smallInteger';
    case UNSIGNED_SMALL_INTEGER = 'unsignedSmallInteger';
    case TINY_INTEGER = 'tinyInteger';
    case UNSIGNED_TINY_INTEGER = 'unsignedTinyInteger';
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case DECIMAL = 'decimal';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case JSONB = 'jsonb';
    case BINARY = 'binary';
    case UUID = 'uuid';
    case FILE = 'file';
    case ENUM = 'enum';
    case FOREIGN_ID = 'foreignId';
    case IMAGE = 'image';
    case EMAIL = 'email';
    case CHAR = 'char';
    case MEDIUM_TEXT = 'mediumText';
    case BIG_INTEGER = 'bigInteger';
}
