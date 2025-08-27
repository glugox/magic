<?php

use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\Relation;

it('parses a JSON file path into Config object', function () {

    $action = new ResolveAppConfigAction;
    $config = $action([
        'config' => __DIR__.'/../fixtures/config/edu.json',
    ]);

    expect($config)->toBeInstanceOf(Config::class)
        ->and($config->entities[0])->toBeInstanceOf(Entity::class)
        ->and(count($config->entities[0]->fields))->toBe(5)
        ->and($config->entities[0]->fields[0])->toBeInstanceOf(Field::class)
        ->and($config->entities[0]->relations[0])->toBeInstanceOf(Relation::class)
        ->and($config->entities[0]->relations[0]->getRelationName())->toBe('enrollments');
});
