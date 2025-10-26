<?php

use Symfony\Component\Console\Command\Command;

it('validates a correct configuration file', function () {
    $configPath = __DIR__.'/../../data/inventory.json';

    $this->artisan('magic:validate', [
        '--config' => $configPath,
    ])->expectsOutput('Configuration file is valid.')
        ->assertExitCode(Command::SUCCESS);
});

it('fails when required sections are missing', function () {
    $configPath = __DIR__.'/../../data/invalid-missing-app.json';

    $this->artisan('magic:validate', [
        '--config' => $configPath,
    ])->expectsOutputToContain('The configuration must include an "app" object.')
        ->assertExitCode(Command::FAILURE);
});
