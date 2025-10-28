<?php

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use InvalidArgumentException;

it('validates missing entity names through the config validator', function () {
    Config::fromJson([
        'app' => ['name' => 'Invalid'],
        'entities' => [
            [
                'fields' => [
                    ['name' => 'id', 'type' => 'id'],
                ],
            ],
        ],
    ]);
})->throws(InvalidArgumentException::class, 'Entity name is required');

it('trims entity names when loading from config', function () {
    $entity = Entity::fromConfig([
        'name' => '  User  ',
        'fields' => [
            ['name' => 'id', 'type' => 'id'],
        ],
    ]);

    expect($entity->getName())->toBe('User');
});
