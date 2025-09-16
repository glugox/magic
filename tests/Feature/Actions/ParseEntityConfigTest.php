<?php

use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\Relation;

it('parses a JSON file path into Config object', function (): void {

    $tmpFilePath = 'fixtures/config.json';
    prepareConfigInFile($tmpFilePath);

    $action = new ResolveAppConfigAction;
    $config = $action([
        'config' => base_path($tmpFilePath),
    ]);

    expect($config)->toBeInstanceOf(Config::class)
        ->and($config->entities[0])->toBeInstanceOf(Entity::class)
        ->and(count($config->entities[0]->fields))->toBe(8)
        ->and($config->entities[0]->fields[0])->toBeInstanceOf(Field::class)
        ->and($config->entities[0]->relations[0])->toBeInstanceOf(Relation::class)
        ->and($config->entities[0]->relations[0]->getRelationName())->toBe('orders');
});
