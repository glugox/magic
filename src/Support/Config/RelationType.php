<?php

namespace Glugox\Magic\Support\Config;

enum RelationType: string
{
    case HAS_MANY = 'hasMany';
    case BELONGS_TO = 'belongsTo';
    case HAS_ONE = 'hasOne';
    case BELONGS_TO_MANY = 'belongsToMany';
    case MORPH_ONE = 'morphOne';
    case MORPH_MANY = 'morphMany';
    case MORPH_TO = 'morphTo';
}
