<?php

use Glugox\Orchestrator\ModuleManager;
use Mockery;

uses(Tests\TestCase::class);

afterEach(function (): void {
    Mockery::close();
});

it('registers enabled modules after the application boots', function () {
    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('setApplication')->once()->with($this->app);
    $manager->shouldReceive('registerEnabledModules')->once()->with($this->app);

    $this->app->instance(ModuleManager::class, $manager);

    $this->artisan('list')->assertExitCode(0);
});
