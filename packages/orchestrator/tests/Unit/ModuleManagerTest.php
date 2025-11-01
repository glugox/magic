<?php

use Glugox\Orchestrator\ModuleManager;

it('discovers modules from composer metadata and module json files', function () {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $manager = new ModuleManager(orchestratorConfig($sandbox));

        expect($manager->all())->toHaveCount(2);

        $composerModule = $manager->module('vendor/foo-module');
        expect($composerModule->name())->toBe('Vendor Foo Module')
            ->and($composerModule->version())->toBe('1.2.3')
            ->and($composerModule->basePath())->toBe($sandbox . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'foo-module')
            ->and($composerModule->isInstalled())->toBeTrue()
            ->and($composerModule->isEnabled())->toBeTrue()
            ->and($composerModule->providers())->toBe(['Vendor\\Foo\\ServiceProvider'])
            ->and($composerModule->paths())
            ->toMatchArray([
                'routes' => 'routes/web.php',
                'migrations' => 'database/migrations',
            ]);

        $moduleJson = $manager->module('custom/module-json');
        expect($moduleJson->name())->toBe('Custom Module')
            ->and($moduleJson->basePath())->toBe($sandbox . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'custom')
            ->and($moduleJson->paths()['routes'])->toBe([
                'routes/api.php',
                'routes/web.php',
            ])
            ->and($moduleJson->providers())->toBe(['Custom\\Module\\Provider']);
    } finally {
        cleanupSandbox($sandbox);
    }
});

it('persists module state changes to the manifest', function () {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $manager = new ModuleManager(orchestratorConfig($sandbox));

        $manager->disable('vendor/foo-module');
        expect($manager->isEnabled('vendor/foo-module'))->toBeFalse()
            ->and(is_file($sandbox . '/bootstrap/cache/modules.php'))->toBeTrue();

        $reloaded = new ModuleManager(orchestratorConfig($sandbox));
        expect($reloaded->isCached())->toBeTrue()
            ->and($reloaded->module('vendor/foo-module')->isEnabled())->toBeFalse()
            ->and($reloaded->module('vendor/foo-module')->isInstalled())->toBeTrue();
    } finally {
        cleanupSandbox($sandbox);
    }
});
