<?php

use Glugox\Magic\Support\Config\Action;
use Glugox\Magic\Support\Config\Config;

it('parses entity actions from json config', function () {
    $configJson = file_get_contents(__DIR__.'/../../stubs/samples/orchestrator.json');
    expect($configJson)->not()->toBeFalse();

    $config = Config::fromJson($configJson);
    $entity = $config->getEntityByName('AppInstance');

    expect($entity)->not()->toBeNull();

    $actions = $entity->getActions();

    expect($actions)
        ->toHaveCount(7)
        ->each->toBeInstanceOf(Action::class);

    $rebuildMatches = array_filter($actions, fn (Action $action) => $action->name === 'rebuild');
    $rebuild = $rebuildMatches ? reset($rebuildMatches) : null;
    expect($rebuild)->not()->toBeNull()
        ->and($rebuild->command)->toBe('app:rebuild')
        ->and($rebuild->type)->toBe('command');

    $previewMatches = array_filter($actions, fn (Action $action) => $action->name === 'preview');
    $preview = $previewMatches ? reset($previewMatches) : null;
    expect($preview)->not()->toBeNull()
        ->and($preview->type)->toBe('link')
        ->and($preview->field)->toBe('url');

    $entityArray = $entity->toArray();
    expect($entityArray)
        ->toHaveKey('actions')
        ->and($entityArray['actions'])
            ->toBeArray()
            ->toHaveCount(7);
    expect($entityArray['actions'][0])
        ->toBeArray()
        ->toHaveKey('name');
});
