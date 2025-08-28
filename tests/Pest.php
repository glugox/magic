<?php

use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * If we pass "fixtures/config.json",
 * then this method will assure we have sample config stored there.
 */
function prepareConfigInFile(string $string): void
{
    $tmpFilePath = base_path($string);
    $fixtureConfig = getFixtureConfig();
    $fixtureConfig->saveToFile($tmpFilePath);
}

/**
 * Return array of relative config paths that are prepared
 * to have sample json configs data.
 */
function sampleConfigsFilePaths($max = 3): array
{
    $configFilePath = "fixtures/config.json";
    prepareConfigInFile($configFilePath);

    return [$configFilePath];
}

/**
 * @throws JsonException
 */
function getFixtureBuildContext(): BuildContext
{
    $buildContext = new BuildContext();
    $buildContext->setConfig(getFixtureConfig());

    return $buildContext;
}

/**
 * @throws JsonException
 */
function getFixtureConfig(): Config
{
    return Config::fromJson('{
    "app": {
        "name": "InventoryHub"
    },
    "entities": [
        {
            "name": "User",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "email", "type": "string", "nullable": false, "unique": true, "sortable": true, "searchable": true },
                { "name": "password", "type": "password", "nullable": false, "hidden": true },
                { "name": "settings", "type": "json", "nullable": true },
                { "name": "active", "type": "boolean", "nullable": false, "default": true },
                { "name": "image", "type": "image", "nullable": true }
            ],
            "relations": [
                { "type": "hasMany", "entity": "Order", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "Shipment", "foreign_key": "user_id" },
                { "type": "belongsToMany", "entity": "Role", "pivot": "role_user", "foreign_key": "user_id", "related_key": "role_id" },
                { "type": "morphMany", "entity": "Attachment", "name": "attachments" }
            ]
        },
        {
            "name": "Role",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "unique": true }
            ],
            "relations": [
                { "type": "belongsToMany", "entity": "User", "pivot": "role_user", "foreign_key": "role_id", "related_key": "user_id" }
            ]
        },
        {
            "name": "Warehouse",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true },
                { "name": "location", "type": "string", "nullable": false },
                { "name": "capacity", "type": "integer", "nullable": false, "min": 0, "max": 200 },
                { "name": "metadata", "type": "json", "nullable": true }
            ],
            "relations": [
                { "type": "hasMany", "entity": "Product", "foreign_key": "warehouse_id" },
                { "type": "hasOne", "entity": "User", "foreign_key": "manager_id" }
            ]
        },
        {
            "name": "Category",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true },
                { "name": "description", "type": "text", "nullable": true }
            ],
            "relations": [
                { "type": "belongsToMany", "entity": "Product", "pivot": "category_product", "foreign_key": "category_id", "related_key": "product_id" }
            ]
        },
        {
            "name": "Product",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "warehouse_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true, "sortable": true },
                { "name": "sku", "type": "string", "nullable": false, "unique": true },
                { "name": "price", "type": "decimal", "nullable": false, "min": 0, "max": 10000, "sortable": true },
                { "name": "weight", "type": "float", "nullable": true, "min": 0, "max": 100, "sortable": true },
                { "name": "available_from", "type": "date", "nullable": true, "sortable": true },
                { "name": "expires_at", "type": "dateTime", "nullable": true, "sortable": true },
                { "name": "image", "type": "image", "nullable": true },
                { "name": "status", "type": "enum", "nullable": false, "values": ["active", "inactive", "discontinued"], "sortable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "Warehouse", "foreign_key": "warehouse_id" },
                { "type": "belongsToMany", "entity": "Category", "pivot": "category_product", "foreign_key": "product_id", "related_key": "category_id" },
                { "type": "morphMany", "entity": "Attachment", "name": "attachments" },
                { "type": "morphMany", "entity": "Review", "name": "reviews" }
            ]
        },
        {
            "name": "Order",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "user_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "order_number", "type": "string", "nullable": false, "unique": true },
                { "name": "status", "type": "enum", "nullable": false, "values": ["pending", "processing", "shipped", "delivered"] },
                { "name": "total", "type": "decimal", "nullable": false, "min": 0, "max": 10000 },
                { "name": "placed_at", "type": "dateTime", "nullable": false }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "User", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "OrderItem", "foreign_key": "order_id" },
                { "type": "hasOne", "entity": "Shipment", "foreign_key": "order_id" }
            ]
        },
        {
            "name": "OrderItem",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "order_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "product_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "quantity", "type": "integer", "nullable": false, "min": 1, "max": 10 },
                { "name": "unit_price", "type": "decimal", "nullable": false, "min": 0, "max": 10000 }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "Order", "foreign_key": "order_id" },
                { "type": "belongsTo", "entity": "Product", "foreign_key": "product_id" }
            ]
        },
        {
            "name": "Shipment",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "order_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "shipped_at", "type": "dateTime", "nullable": true },
                { "name": "delivered_at", "type": "dateTime", "nullable": true },
                { "name": "carrier", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "Order", "foreign_key": "order_id" },
                { "type": "hasOne", "entity": "CarrierDetail", "foreign_key": "shipment_id" }
            ]
        },
        {
            "name": "CarrierDetail",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "shipment_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "tracking_number", "type": "string", "nullable": true },
                { "name": "service_level", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "Shipment", "foreign_key": "shipment_id" }
            ]
        },
        {
            "name": "Attachment",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "attachable_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "attachable_type", "type": "string", "nullable": false },
                { "name": "file_path", "type": "string", "nullable": false },
                { "name": "file_type", "type": "string", "nullable": false }
            ],
            "relations": [
                { "type": "morphTo", "name": "attachable" }
            ]
        },
        {
            "name": "Review",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "reviewable_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "reviewable_type", "type": "string", "nullable": false },
                { "name": "rating", "type": "integer", "nullable": false, "min": 0, "max": 10 },
                { "name": "comment", "type": "text", "nullable": true }
            ],
            "relations": [
                { "type": "morphTo", "name": "reviewable" }
            ]
        }
    ],
    "dev": {
        "seedEnabled": true,
        "seedCount": 50,
        "fakerMappings": {
            "carrier": "randomElement([\"FedEx\", \"UPS\", \"DHL\", \"USPS\"])"
        }
    }
}
');

}
