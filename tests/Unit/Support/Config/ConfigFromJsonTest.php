<?php

use Glugox\Magic\Support\Config\Config;
use InvalidArgumentException;

it('throws before hydration when the app section is missing', function () {
    expect(fn () => Config::fromJson([
        'entities' => [
            [
                'name' => 'User',
                'fields' => [
                    ['name' => 'id', 'type' => 'id'],
                ],
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'The configuration must include an "app" object.');
});

it('hydrates and validates a minimal config after raw validation passes', function () {
    $config = Config::fromJson([
        'app' => [
            'name' => 'Test App',
        ],
        'entities' => [
            [
                'name' => 'User',
                'fields' => [
                    ['name' => 'id', 'type' => 'id'],
                ],
            ],
        ],
    ]);

    expect($config->app->name)->toBe('Test App');
    expect($config->entities)->toHaveCount(1);
    expect($config->entities[0]->name)->toBe('User');
});
