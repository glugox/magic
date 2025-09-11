<?php

use Glugox\Magic\Support\Config\Builder\ConfigBuilder;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;

it('builds a Config from SDL', function () {

    $graphQl = getFixtureConfigStringInventoryGraphQl();

    $config = (ConfigBuilder::make())
        ->withGraphQl($graphQl)
        ->build();

    // Assert Config instance
    expect($config)->toBeInstanceOf(Config::class);

    // Assert 'User' entity exists
    $user = $config->getEntityByName('User');
    expect($user)->toBeInstanceOf(Entity::class);

    // Assert fields exist and are correctly typed
    $fields = $user->getFields();
    expect($fields)->toHaveCount(7); // id, name, email, password, settings, active, image

    $idField = collect($fields)->first(fn (Field $f) => $f->name === 'id');
    expect($idField)->not()->toBeNull();
    expect($idField->type)->toBe(FieldType::ID);

    $nameField = collect($fields)->first(fn (Field $f) => $f->name === 'name');
    expect($nameField->searchable)->toBeTrue();
    expect($nameField->sortable)->toBeTrue();

    $passwordField = collect($fields)->first(fn (Field $f) => $f->name === 'password');
    expect($passwordField->type)->toBe(FieldType::PASSWORD);

    // Assert relations
    $relations = $user->getRelations();
    expect($relations)->toHaveCount(4);

    $ordersRelation = collect($relations)->first(fn (Relation $r) => $r->getRelationName() === 'orders');
    expect($ordersRelation->isHasMany())->toBeTrue();

    $rolesRelation = collect($relations)->first(fn (Relation $r) => $r->getRelationName() === 'roles');
    expect($rolesRelation->isBelongsToMany())->toBeTrue();
});

it('SchemaReader creates same toJson output as regular Config toJson', function () {
    $graphQl = getFixtureConfigStringInventoryGraphQl();
    $jsonStringConfig = getFixtureConfigInventory();

    $configFromGraphQl = (ConfigBuilder::make())
        ->withGraphQl($graphQl)
        ->build();

    $jsonFromGraphQl = $configFromGraphQl->toJson();
    $jsonFromConfig = $jsonStringConfig->toJson();

    expect($jsonFromGraphQl)->toBe($jsonFromConfig);
});
