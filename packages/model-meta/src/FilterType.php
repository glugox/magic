<?php

namespace Glugox\ModelMeta;

enum FilterType: string
{
    case STRING = 'string';
    case NUMBER = 'number';

    case ENUM = 'enum';
    case MANY = 'many';
    case RANGE = 'range';
    case DATE_RANGE = 'date_range';
    case TIMESTAMP = 'timestamp'; // Timestamp picker
    case BOOLEAN = 'boolean';

    case BELONGS_TO = 'belongs_to';      // Relation filter
    case BELONGS_TO_MANY = 'belongs_to_many';    // Relation filter
    case HAS_ONE = 'has_one';        // Relation filter
    case HAS_MANY = 'has_many';      // Relation filter
}