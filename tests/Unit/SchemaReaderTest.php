<?php

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Readers\SchemaReader;

beforeEach(function (): void {
    $this->schema = <<<'GQL'
    type User {
      id: ID!
      name: String! @search @sort
      email: String! @unique @search @sort
      password: Password! @hidden
      settings: JSON
      active: Boolean! @default(true)

      orders: [Order!]! @hasMany
      shipments: [Shipment!]! @hasMany
      roles: [Role!]! @belongsToMany
    }
    GQL;
});

it('can load graphql schema into config', function () {
    $reader = app(SchemaReader::class);

    // Parse GraphQL DSL into old JSON format
    $reader->load($this->schema);

    $jsonConfig = $reader->toJson();

    // Load Config using existing class
    $config = Config::fromJson($jsonConfig);

    expect($config->isValid())->toBeTrue()
        ->and($config->entities)->not()->toBeEmpty();

    $user = $config->getEntityByName('User');

    expect($user)->not()->toBeNull()
        ->and($user->getName())->toBe('User')
        ->and($user->getTableName())->toBe('users');

    // Example: assert directives converted into settings
    expect($user->getFieldByName('email')->searchable)->toBeTrue()
        ->and($user->getFieldByName('name')->sortable)->toBeTrue();
});
