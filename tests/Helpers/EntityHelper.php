<?php

namespace Glugox\Magic\Tests\Helpers;

use Glugox\Magic\Support\Config\Entity;

if (!function_exists('makeDummyEntity')) {
    function makeDummyUserEntityConfig(): Entity
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


/**
 * A more complex entity configuration for testing. (Products with various field types and relations)
 */
if (!function_exists('makeDummyProductEntityConfig')) {
    function makeDummyProductEntityConfig(): Entity
    {
        return Entity::fromConfig(
            '{
            "name": "Product",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "title", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "description", "type": "text", "nullable": true },
                { "name": "price", "type": "decimal", "nullable": false, "sortable": true, "precision": 10, "scale": 2 },
                { "name": "stock", "type": "integer", "nullable": false, "default": 0 },
                { "name": "sku", "type": "uuid", "nullable": false, "unique": true },
                { "name": "is_active", "type": "boolean", "nullable": false, "default": true },
                { "name": "tags", "type": "json", "nullable": true },
                { "name": "category", "type": "enum", "values": ["electronics", "books", "clothing", "home"], "nullable": false },
                { "name": "released_at", "type": "dateTime", "nullable": true },
                { "name": "image", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "User", "foreign_key": "created_by" },
                { "type": "hasMany", "entity": "Review", "foreign_key": "product_id" },
                { "type": "belongsToMany", "entity": "Tag", "pivot": "product_tag", "foreign_key": "product_id", "related_key": "tag_id" }
            ],
            "settings": {
                "searchable": true,
                "has_media": true
            }
        }'
        );
    }
}

