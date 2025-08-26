<?php

namespace Tests\Helpers;

use Glugox\Magic\Support\Config\Entity;

if (!function_exists('makeDummyEntity')) {
    function makeDummyEntity(): Entity
    {
        return Entity::fromConfig(
            '{
            "name": "User",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "email", "type": "email", "nullable": false, "unique": true, "sortable": true, "searchable": true },
                { "name": "password", "type": "password", "nullable": false, "hidden": true },
                { "name": "is_active", "type": "boolean", "nullable": false, "default": false, "sortable": true }
            ],
            "relations": [
                { "type": "hasMany", "entity": "Project", "foreign_key": "owner_id" },
                { "type": "hasMany", "entity": "Task", "foreign_key": "assigned_to" },
                { "type": "belongsToMany", "entity": "Team", "pivot": "team_user", "foreign_key": "user_id", "related_key": "team_id" }
            ],
            "settings": {
                "searchable": true,
                "has_avatar": true
            }
        }'
        );
    }
}
