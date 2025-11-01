<?php

use Glugox\Orchestrator\ModuleManager;
use Tests\Fixtures\FakeModuleServiceProvider;

it('registers service providers for enabled modules on boot', function () {
    FakeModuleServiceProvider::reset();
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox, [
            'composer' => [
                'extra' => [
                    'laravel' => [
                        'providers' => [FakeModuleServiceProvider::class],
                    ],
                ],
            ],
            'module_json' => null,
        ]);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $manager = $this->app->make(ModuleManager::class);

        if (! $this->app->isBooted()) {
            $this->app->boot();
        }

        expect(FakeModuleServiceProvider::$events)->toContain('register')
            ->and(FakeModuleServiceProvider::$events)->toContain('boot')
            ->and($manager->isEnabled('vendor/foo-module'))->toBeTrue();
    } finally {
        cleanupSandbox($sandbox);
        FakeModuleServiceProvider::reset();
    }
});

it('registers providers when enabling modules at runtime', function () {
    FakeModuleServiceProvider::reset();
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox, [
            'composer' => [
                'extra' => [
                    'laravel' => [
                        'providers' => [FakeModuleServiceProvider::class],
                    ],
                ],
            ],
            'module_json' => null,
        ]);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox, [
            'auto_enable' => false,
        ]));

        $manager = $this->app->make(ModuleManager::class);

        if (! $this->app->isBooted()) {
            $this->app->boot();
        }

        expect(FakeModuleServiceProvider::$events)->toBeEmpty();

        $manager->enable('vendor/foo-module');

        expect(FakeModuleServiceProvider::$events)->toContain('register')
            ->and(FakeModuleServiceProvider::$events)->toContain('boot');
    } finally {
        cleanupSandbox($sandbox);
        FakeModuleServiceProvider::reset();
    }
});
