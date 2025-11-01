<?php

use Glugox\Orchestrator\ModuleManager;

it('provides artisan commands to manage modules', function () {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $manager = $this->app->make(ModuleManager::class);

        if (! $this->app->isBooted()) {
            $this->app->boot();
        }

        $this->artisan('modules:list')
            ->expectsOutputToContain('vendor/foo-module')
            ->assertSuccessful();

        $this->artisan('modules:disable', ['module' => 'vendor/foo-module'])
            ->expectsOutput('Module [vendor/foo-module] disabled.')
            ->assertSuccessful();

        expect($manager->isEnabled('vendor/foo-module'))->toBeFalse();

        $this->artisan('modules:enable', ['module' => 'vendor/foo-module'])
            ->expectsOutput('Module [vendor/foo-module] enabled.')
            ->assertSuccessful();

        expect($manager->isEnabled('vendor/foo-module'))->toBeTrue();

        $this->artisan('modules:reload', ['--no-cache' => true])
            ->expectsOutput('Discovered 2 modules.')
            ->assertSuccessful();
    } finally {
        cleanupSandbox($sandbox);
    }
});
