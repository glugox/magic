<?php

declare(strict_types=1);

namespace Glugox\Magic\Enums;

enum GraphQlType: string
{
    case ID = 'ID';
    case STRING = 'String';
    case BOOLEAN = 'Boolean';
    case INTEGER = 'Int';
    case FLOAT = 'Float';
    case DECIMAL = 'Decimal'; // custom scalar if needed
    case DATE = 'Date';
    case DATETIME = 'DateTime';
    case JSON = 'JSON';
    case PASSWORD = 'Password';
    case EMAIL = 'Email';
    case UUID = 'UUID';
    case TEXT = 'Text';
    case LONG_TEXT = 'LongText';
    case URL = 'URL';
    case TIME = 'Time';
    case YEAR = 'Year';
    case SECRET = 'Secret';
    case TOKEN = 'Token';
    case PHONE = 'Phone';
    case USERNAME = 'Username';
    case SLUG = 'Slug';
    case FILE = 'File';
    case IMAGE = 'Image';
    case IP_ADDRESS = 'IpAddress';
    case CHAR = 'Char';
    case SMALL_INTEGER = 'SmallInt';
    case TINY_INTEGER = 'TinyInt';
    case UNSIGNED_INTEGER = 'UnsignedInt';
    case UNSIGNED_SMALL_INTEGER = 'UnsignedSmallInt';
    case UNSIGNED_TINY_INTEGER = 'UnsignedTinyInt';
    case BIG_INTEGER = 'BigInt';
    case UNSIGNED_BIG_INTEGER = 'UnsignedBigInt';
    case BIG_INCREMENTS = 'BigIncrements';
    case FOREIGN_ID = 'ForeignId';
    case DOUBLE = 'Double';
    case MEDIUM_TEXT = 'MediumText';
    case BINARY = 'Binary';
    case TIMESTAMP = 'Timestamp';
    case ENUM = 'Enum';
}
