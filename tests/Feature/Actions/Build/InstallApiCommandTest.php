<?php

use Glugox\Magic\Actions\Build\InstallApiCommand;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Fake the logging and Artisan calls
    $this->context = getFixtureBuildContext();
});

it('skips install if API routes are already registered in router', function () {

    // Run the InstallApiCommand
    $action = new InstallApiCommand();
    $action($this->context);
})->throwsNoExceptions();
