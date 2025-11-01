<?php

namespace Glugox\ModelMeta;

enum RelationType: string
{
    case HAS_ONE = 'hasOne';
    case BELONGS_TO = 'belongsTo';
    case HAS_MANY = 'hasMany';
    case BELONGS_TO_MANY = 'belongsToMany';
    case MORPH_ONE = 'morphOne';
    case MORPH_MANY = 'morphMany';
    case MORPH_TO = 'morphTo';
    case MORPH_TO_MANY = 'morphToMany';
    case MORPHED_BY_MANY = 'morphedByMany';
}