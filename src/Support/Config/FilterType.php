<?php

namespace Glugox\Magic\Support\Config;

enum FilterType: string
{
    case TEXT = 'text';            // Single-line text input
    case BOOLEAN = 'boolean';      // Checkbox or toggle
    case MULTI_SELECT = 'multi_select'; // Multi-select dropdown
    case RANGE = 'range';          // Numeric range input (min/max)
    //case DATE = 'date';            // Single date picker
    //case DATE_TIME = 'date_time';      // Single date-time picker // Only date range is supported for now, use DATETIME_RANGE instead
    case DATE_RANGE = 'date_range'; // Date range picker
    //case DATETIME = 'datetime';    // Single datetime picker
    //case TIMESTAMP = 'timestamp'; // Timestamp picker
    case DATETIME_RANGE = 'datetime_range'; // Datetime range picker
    case ENUM = 'enum';            // Enum dropdown
    case BELONGS_TO = 'belongs_to';      // Relation filter
    case BELONGS_TO_MANY = 'belongs_to_many';    // Relation filter
    case HAS_ONE = 'has_one';        // Relation filter
    case HAS_MANY = 'has_many';      // Relation filter

}
