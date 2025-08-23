<?php

namespace Glugox\Magic\Support\Config;

enum FieldType: string
{
    case BIG_INCREMENTS = 'bigIncrements';
    case BIG_INTEGER = 'bigInteger';
    case BINARY = 'binary';
    case BOOLEAN = 'boolean';
    case CHAR = 'char';
    case DATE = 'date';
    case DATETIME = 'dateTime';
    case DECIMAL = 'decimal';
    case DOUBLE = 'double';
    case EMAIL = 'email';
    case ENUM = 'enum';
    case FILE = 'file';
    case FLOAT = 'float';
    case FOREIGN_ID = 'foreignId';
    case IMAGE = 'image';
    case INTEGER = 'integer';
    case IP_ADDRESS = 'ipAddress';
    case JSON = 'json';
    case JSONB = 'jsonb';
    case LONG_TEXT = 'longText';
    case MEDIUM_TEXT = 'mediumText';
    case PASSWORD = 'password';
    case SMALL_INTEGER = 'smallInteger';
    case STRING = 'string';
    case TEXT = 'text';
    case TIME = 'time';
    case TIMESTAMP = 'timestamp';
    case TINY_INTEGER = 'tinyInteger';
    case UNSIGNED_BIG_INTEGER = 'unsignedBigInteger';
    case UNSIGNED_INTEGER = 'unsignedInteger';
    case UNSIGNED_SMALL_INTEGER = 'unsignedSmallInteger';
    case UNSIGNED_TINY_INTEGER = 'unsignedTinyInteger';
    case UUID = 'uuid';
    case URL = 'url';
    case YEAR = 'year';
    case SECRET = 'secret';
    case TOKEN = 'token';

    // Relation types
    case BELONGS_TO = 'belongsTo';
    case HAS_MANY = 'hasMany';
    case HAS_ONE = 'hasOne';
    case BELONGS_TO_MANY = 'belongsToMany';

}
