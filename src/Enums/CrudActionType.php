<?php

namespace Glugox\Magic\Enums;

/**
 * Enum for CRUD action types.
 * Used in validation rules to specify the context of the action.
 * Also used to create different pages for each action type, etc.
 */
enum CrudActionType
{
    case CREATE;
    case READ;
    case UPDATE;
    case DELETE;

}
