<?php

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Readers\SchemaReader;

beforeEach(function () {
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

/*it('can load graphql schema into config', function () {
    $reader = new SchemaReader();

    // Parse GraphQL DSL into old JSON format
    $jsonConfig = $reader->load($this->schema);

    // Load Config using existing class
    $config = Config::fromJson($jsonConfig);

    expect($config->isValid())->toBeTrue()
        ->and($config->entities)->not()->toBeEmpty();

    $user = $config->getEntityByName('User');

    expect($user)->not()->toBeNull()
        ->and($user->getName())->toBe('User')
        ->and($user->getTableName())->toBe('users');

    // Example: assert directives converted into settings
    expect($user->settings->searchable)->toBeTrue()
        ->and($user->settings->sortable)->toBeTrue();
});*/
