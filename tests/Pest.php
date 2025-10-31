<?php

use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

uses(TestCase::class)->in('Browser');

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
    $configFilePath = 'fixtures/config.json';
    prepareConfigInFile($configFilePath);

    return [$configFilePath];
}

/**
 * @throws JsonException
 */
function getFixtureBuildContext(?string $sample = null): BuildContext
{
    $buildContext = new BuildContext;
    $buildContext->setConfig(getFixtureConfig($sample));

    return $buildContext;
}

function getFixtureConfig(?string $sample = null): Config
{
    return match ($sample) {
        'resume' => getFixtureConfigResume(),
        default => getFixtureConfigInventory()
    };
}
function getFixtureConfigResume(): Config
{
    return Config::fromJson('
    {
    "app": {
        "name": "UNO",
        "seedEnabled": true,
        "seedCount": 20
    },
    "entities": [
        {
            "name": "User",
            "icon": "Users",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "email", "type": "string", "nullable": false, "unique": true, "sortable": true, "searchable": true },
                { "name": "password", "type": "password", "nullable": false, "hidden": true }
            ],
            "relations": [
                { "type": "hasMany", "relatedEntityName": "Address", "foreignKey": "user_id" },
                { "type": "hasMany", "relatedEntityName": "Resume", "foreignKey": "user_id" },
                { "type": "belongsToMany", "relatedEntityName": "Role", "pivot": "role_user", "foreignKey": "user_id", "relatedKey": "role_id" }
            ],
            "casts": {
                "email_verified_at": "datetime"
            }
        },
        {
            "name": "Address",
            "icon": "MapPin",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "user_id", "type": "foreignId", "nullable": false },
                { "name": "street", "type": "string", "nullable": false, "searchable": true },
                { "name": "city", "type": "string", "nullable": false, "searchable": true },
                { "name": "country", "type": "string", "nullable": false, "searchable": true },
                { "name": "postal_code", "type": "string", "nullable": true, "searchable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "User", "foreignKey": "user_id" }
            ]
        },
        {
            "name": "Resume",
            "icon": "FileText",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "user_id", "type": "foreignId", "nullable": false },
                { "name": "title", "type": "string", "nullable": false, "searchable": true },
                { "name": "summary", "type": "text", "nullable": true, "searchable": true, "showInTable": false }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "User", "foreignKey": "user_id" },
                { "type": "hasMany", "relatedEntityName": "WorkExperience", "foreignKey": "resume_id" },
                { "type": "hasMany", "relatedEntityName": "Education", "foreignKey": "resume_id" },
                { "type": "hasMany", "relatedEntityName": "Skill", "foreignKey": "resume_id" },
                { "type": "hasMany", "relatedEntityName": "Certification", "foreignKey": "resume_id" },
                { "type": "hasMany", "relatedEntityName": "Project", "foreignKey": "resume_id" },
                { "type": "hasMany", "relatedEntityName": "Language", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "WorkExperience",
            "icon": "Briefcase",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "company", "type": "string", "searchable": true },
                { "name": "position", "type": "string", "searchable": true },
                { "name": "start_date", "type": "date" },
                { "name": "end_date", "type": "date", "nullable": true },
                { "name": "description", "type": "text", "nullable": true, "showInTable": false }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Education",
            "icon": "GraduationCap",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "institution", "type": "string", "searchable": true },
                { "name": "degree", "type": "string", "searchable": true },
                { "name": "field_of_study", "type": "string", "nullable": true },
                { "name": "start_date", "type": "date" },
                { "name": "end_date", "type": "date", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Skill",
            "icon": "Sparkles",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "name", "type": "string", "searchable": true },
                { "name": "level", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Certification",
            "icon": "Award",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "name", "type": "string", "searchable": true },
                { "name": "organization", "type": "string", "nullable": true },
                { "name": "date_received", "type": "date", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Project",
            "icon": "FolderKanban",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "title", "type": "string", "searchable": true },
                { "name": "description", "type": "longText", "nullable": true, "showInTable": false },
                { "name": "url", "type": "url", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Language",
            "icon": "Languages",
            "fields": [
                { "name": "id", "type": "id" },
                { "name": "resume_id", "type": "foreignId" },
                { "name": "name", "type": "string" },
                { "name": "proficiency", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Resume", "foreignKey": "resume_id" }
            ]
        },
        {
            "name": "Role",
            "icon": "Shield",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "unique": true, "searchable": true }
            ],
            "relations": [
                { "type": "belongsToMany", "relatedEntityName": "User", "pivot": "role_user", "foreignKey": "role_id", "relatedKey": "user_id" }
            ]
        }
    ]
}
');
}

/**
 * @throws JsonException
 */
function getFixtureConfigInventory(): Config
{
    return Config::fromJson('{
    "app": {
        "name": "InventoryHub",
        "seedEnabled": true,
        "seedCount": 50
    },
    "entities": [
        {
            "name": "User",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "email", "type": "string", "nullable": false, "unique": true, "sortable": true, "searchable": true },
                { "name": "password", "type": "password", "nullable": false, "hidden": true },
                { "name": "settings", "type": "json", "nullable": true },
                { "name": "active", "type": "boolean", "nullable": false, "default": true },
                { "name": "image", "type": "image", "nullable": true }
            ],
            "relations": [
                { "type": "hasMany", "relatedEntityName": "Order", "foreignKey": "user_id" },
                { "type": "hasMany", "relatedEntityName": "Shipment", "foreignKey": "user_id" },
                { "type": "belongsToMany", "relatedEntityName": "Role", "pivot": "role_user", "foreignKey": "user_id", "relatedKey": "role_id" }
            ]
        },
        {
            "name": "Role",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "unique": true }
            ],
            "relations": [
                { "type": "belongsToMany", "relatedEntityName": "User", "pivot": "role_user", "foreignKey": "role_id", "relatedKey": "user_id" }
            ]
        },
        {
            "name": "Warehouse",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true },
                { "name": "location", "type": "string", "nullable": false },
                { "name": "capacity", "type": "integer", "nullable": false, "min": 0, "max": 200 },
                { "name": "metadata", "type": "json", "nullable": true }
            ],
            "relations": [
                { "type": "hasMany", "relatedEntityName": "Product", "foreignKey": "warehouse_id" },
                { "type": "hasOne", "relatedEntityName": "User", "foreignKey": "manager_id" }
            ]
        },
        {
            "name": "Category",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true },
                { "name": "description", "type": "text", "nullable": true }
            ],
            "relations": [
                { "type": "belongsToMany", "relatedEntityName": "Product", "pivot": "category_product", "foreignKey": "category_id", "relatedKey": "product_id" }
            ]
        },
        {
            "name": "Product",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "warehouse_id", "type": "foreignId", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "searchable": true, "sortable": true },
                { "name": "sku", "type": "string", "nullable": false, "unique": true },
                { "name": "price", "type": "decimal", "nullable": false, "min": 0, "max": 10000, "sortable": true },
                { "name": "weight", "type": "float", "nullable": true, "min": 0, "max": 100, "sortable": true },
                { "name": "available_from", "type": "date", "nullable": true, "sortable": true },
                { "name": "expires_at", "type": "dateTime", "nullable": true, "sortable": true },
                { "name": "image", "type": "image", "nullable": true },
                { "name": "status", "type": "enum", "nullable": false, "options": ["active", "inactive", "discontinued"], "sortable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Warehouse", "foreignKey": "warehouse_id" },
                { "type": "belongsToMany", "relatedEntityName": "Category", "pivot": "category_product", "foreignKey": "product_id", "relatedKey": "category_id" },
                { "type": "morphMany", "relatedEntityName": "Review", "relationName": "reviews" }
            ],
            "filters": [
                { "field": "name", "type": "text" },
                { "field": "status", "type": "enum" },
                { "field": "price", "type": "range" },
                { "field": "available_from", "type": "date_range" }
            ],
            "actions": [
                { "name": "rebuildInventory", "command": "inventory:product-rebuild", "description": "Rebuild the product inventory cache" },
                { "name": "openProduct", "type": "link", "field": "id" }
            ]
        },
        {
            "name": "Order",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "user_id", "type": "foreignId", "nullable": false },
                { "name": "order_number", "type": "string", "nullable": false, "unique": true },
                { "name": "status", "type": "enum", "nullable": false, "options": ["pending", "processing", "shipped", "delivered"] },
                { "name": "total", "type": "decimal", "nullable": false, "min": 0, "max": 10000 },
                { "name": "placed_at", "type": "dateTime", "nullable": false }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "User", "foreignKey": "user_id" },
                { "type": "hasMany", "relatedEntityName": "OrderItem", "foreignKey": "order_id" },
                { "type": "hasOne", "relatedEntityName": "Shipment", "foreignKey": "order_id" }
            ]
        },
        {
            "name": "OrderItem",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "order_id", "type": "foreignId", "nullable": false },
                { "name": "product_id", "type": "foreignId", "nullable": false },
                { "name": "quantity", "type": "integer", "nullable": false, "min": 1, "max": 10 },
                { "name": "unit_price", "type": "decimal", "nullable": false, "min": 0, "max": 10000 }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Order", "foreignKey": "order_id" },
                { "type": "belongsTo", "relatedEntityName": "Product", "foreignKey": "product_id" }
            ]
        },
        {
            "name": "Shipment",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "order_id", "type": "foreignId", "nullable": false },
                { "name": "shipped_at", "type": "dateTime", "nullable": true },
                { "name": "delivered_at", "type": "dateTime", "nullable": true },
                { "name": "carrier", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Order", "foreignKey": "order_id" },
                { "type": "hasOne", "relatedEntityName": "CarrierDetail", "foreignKey": "shipment_id" }
            ]
        },
        {
            "name": "CarrierDetail",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "shipment_id", "type": "foreignId", "nullable": false },
                { "name": "tracking_number", "type": "string", "nullable": true },
                { "name": "service_level", "type": "string", "nullable": true }
            ],
            "relations": [
                { "type": "belongsTo", "relatedEntityName": "Shipment", "foreignKey": "shipment_id" }
            ]
        },
        {
            "name": "Review",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "reviewable_id", "type": "foreignId", "nullable": false },
                { "name": "reviewable_type", "type": "string", "nullable": false },
                { "name": "rating", "type": "integer", "nullable": false, "min": 0, "max": 10 },
                { "name": "comment", "type": "text", "nullable": true }
            ],
            "relations": [
                { "type": "morphTo", "relationName": "reviewable" }
            ]
        }
    ]
}
');

}

function getFixtureConfigStringInventoryGraphQl(): string
{
    return '# App configuration
type App @config {
  name: String! @default("InventoryHub")
  seedEnabled: Boolean! @default(true)
  seedCount: Int! @default(50)
  defaultLocale: String! @default("en")
  enableAdvancedLogging: Boolean! @default(false)
  defaultPerPage: Int! @default(50)
}

# Entities

type User {
  id: ID!
  name: String! @search @sort
  email: String! @unique @search @sort
  password: Password!
  settings: JSON
  active: Boolean! @default(true)
  image: Image
  orders: Order! @hasMany
  shipments: Shipment! @hasMany
  roles: Role! @belongsToMany @pivot("role_user") @foreignKey("user_id") @relatedKey("role_id")
}

type Role {
  id: ID!
  name: String! @unique
  users: User! @belongsToMany @pivot("role_user") @foreignKey("role_id") @relatedKey("user_id")
}

type Warehouse {
  id: ID!
  name: String! @search
  location: String!
  capacity: Int! @min(0) @max(200)
  metadata: JSON
  products: Product! @hasMany
  manager: User! @hasOne @name("user") @foreignKey("manager_id")
}

type Category {
  id: ID!
  name: String! @search
  description: Text
  products: Product! @belongsToMany @pivot("category_product") @foreignKey("category_id") @relatedKey("product_id")
}

enum Status {
  active
  inactive
  discontinued
}

type Product {
  id: ID!
  warehouse_id: ForeignId!
  name: String! @search @sort
  sku: String! @unique
  price: Decimal! @sort @min(0) @max(10000)
  weight: Float @sort @min(0) @max(100)
  available_from: Date @sort
  expires_at: DateTime @sort
  image: Image
  status: Status! @sort
  warehouse: Warehouse! @belongsTo
  categories: Category! @belongsToMany @pivot("category_product") @foreignKey("product_id") @relatedKey("category_id")
  reviews: Review! @morphMany
}

enum OrderStatus {
  pending
  processing
  shipped
  delivered
}

type Order {
  id: ID!
  user_id: ForeignId!
  order_number: String! @unique
  status: OrderStatus!
  total: Decimal! @min(0) @max(10000)
  placed_at: DateTime!
  user: User! @belongsTo
  orderItems: OrderItem! @hasMany
  shipment: Shipment! @hasOne
}

type OrderItem {
  id: ID!
  order_id: ForeignId!
  product_id: ForeignId!
  quantity: Int! @min(1) @max(10)
  unit_price: Decimal! @min(0) @max(10000)
  order: Order! @belongsTo
  product: Product! @belongsTo
}

type Shipment {
  id: ID!
  order_id: ForeignId!
  shipped_at: DateTime
  delivered_at: DateTime
  carrier: String
  order: Order! @belongsTo
  carrierDetail: CarrierDetail! @hasOne
}

type CarrierDetail {
  id: ID!
  shipment_id: ForeignId!
  tracking_number: String
  service_level: String
  shipment: Shipment! @belongsTo
}

type Review {
  id: ID!
  reviewable_id: ForeignId!
  reviewable_type: String!
  rating: Int! @min(0) @max(10)
  comment: Text
  reviewable: @morphTo
}
';
}
