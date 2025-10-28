<?php

use Symfony\Component\Console\Command\Command;

it('fails when an entity is missing a name', function () {
    $configPath = __DIR__.'/../../data/invalid-missing-entity-name.json';

    $this->artisan('magic:build', [
        '--config' => $configPath,
    ])->expectsOutputToContain('Entity name is required')
        ->assertExitCode(Command::FAILURE);
});
