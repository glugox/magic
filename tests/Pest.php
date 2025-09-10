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
        "name": "UNO"
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
                { "type": "hasMany", "entity": "Address", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "Resume", "foreign_key": "user_id" },
                { "type": "belongsToMany", "entity": "Role", "pivot": "role_user", "foreign_key": "user_id", "related_key": "role_id" }
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
                { "type": "belongsTo", "entity": "User", "foreign_key": "user_id" }
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
                { "type": "belongsTo", "entity": "User", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "WorkExperience", "foreign_key": "resume_id" },
                { "type": "hasMany", "entity": "Education", "foreign_key": "resume_id" },
                { "type": "hasMany", "entity": "Skill", "foreign_key": "resume_id" },
                { "type": "hasMany", "entity": "Certification", "foreign_key": "resume_id" },
                { "type": "hasMany", "entity": "Project", "foreign_key": "resume_id" },
                { "type": "hasMany", "entity": "Language", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsTo", "entity": "Resume", "foreign_key": "resume_id" }
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
                { "type": "belongsToMany", "entity": "User", "pivot": "role_user", "foreign_key": "role_id", "related_key": "user_id" }
            ]
        }
    ],
    "dev": {
        "seedEnabled": true,
        "seedCount": 20
    }
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
        "name": "InventoryHub"
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
                { "type": "hasMany", "entity": "Order", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "Shipment", "foreign_key": "user_id" },
                { "type": "belongsToMany", "entity": "Role", "pivot": "role_user", "foreign_key": "user_id", "related_key": "role_id" },
                { "type": "morphMany", "entity": "Attachment", "name": "attachments" }
            ]
        },
        {
            "name": "Role",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "unique": true }
            ],
            "relations": [
                { "type": "belongsToMany", "entity": "User", "pivot": "role_user", "foreign_key": "role_id", "related_key": "user_id" }
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
                { "type": "hasMany", "entity": "Product", "foreign_key": "warehouse_id" },
                { "type": "hasOne", "entity": "User", "foreign_key": "manager_id" }
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
                { "type": "belongsToMany", "entity": "Product", "pivot": "category_product", "foreign_key": "category_id", "related_key": "product_id" }
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
                { "name": "status", "type": "enum", "nullable": false, "values": ["active", "inactive", "discontinued"], "sortable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "Warehouse", "foreign_key": "warehouse_id" },
                { "type": "belongsToMany", "entity": "Category", "pivot": "category_product", "foreign_key": "product_id", "related_key": "category_id" },
                { "type": "morphMany", "entity": "Attachment"},
                { "type": "morphMany", "entity": "Review"}
            ]
        },
        {
            "name": "Order",
            "fields": [
                { "name": "id", "type": "id", "nullable": false },
                { "name": "user_id", "type": "foreignId", "nullable": false },
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
                { "name": "id", "type": "id", "nullable": false },
                { "name": "order_id", "type": "foreignId", "nullable": false },
                { "name": "product_id", "type": "foreignId", "nullable": false },
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
                { "name": "id", "type": "id", "nullable": false },
                { "name": "order_id", "type": "foreignId", "nullable": false },
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
                { "name": "id", "type": "id", "nullable": false },
                { "name": "shipment_id", "type": "foreignId", "nullable": false },
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
                { "name": "id", "type": "id", "nullable": false },
                { "name": "attachable_id", "type": "foreignId", "nullable": false },
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
                { "name": "id", "type": "id", "nullable": false },
                { "name": "reviewable_id", "type": "foreignId", "nullable": false },
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

function getFixtureConfigStringInventoryGraphQl(): string
{
    return '# App configuration
type App @config {
  name: String! @default("InventoryHub GraphQl")
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
  attachments: Attachment! @morphMany
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
  status: Enum! @enum(values: ["active","inactive","discontinued"]) @sort
  warehouse: Warehouse! @belongsTo
  categories: Category! @belongsToMany
  attachments: Attachment! @morphMany
  reviews: Review! @morphMany
}

type Order {
  id: ID!
  user_id: ForeignId!
  order_number: String! @unique
  status: String! @enum(values:["pending","processing","shipped","delivered"])
  total: Float!
  placed_at: DateTime!
  user: User! @belongsTo
  items: OrderItem! @hasMany
  shipment: Shipment! @hasOne
}

type OrderItem {
  id: ID!
  order_id: ForeignId!
  product_id: ForeignId!
  quantity: Int!
  unit_price: Float!
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
  carrier_detail: CarrierDetail! @hasOne
}

type CarrierDetail {
  id: ID!
  shipment_id: ForeignId!
  tracking_number: String
  service_level: String
  shipment: Shipment! @belongsTo
}

type Attachment {
  id: ID!
  attachable_id: ForeignId!
  attachable_type: String!
  file_path: String!
  file_type: String!
  attachable: @morphTo
}

type Review {
  id: ID!
  reviewable_id: ForeignId!
  reviewable_type: String!
  rating: Int!
  comment: String
  reviewable: @morphTo
}
';
}
