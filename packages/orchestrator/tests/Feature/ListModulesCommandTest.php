<?php

it('lists modules with detailed status information', function (): void {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $customPath = realpath($sandbox.'/modules/custom') ?: $sandbox.'/modules/custom';
        $vendorPath = realpath($sandbox.'/vendor/foo-module') ?: $sandbox.'/vendor/foo-module';

        $this->artisan('orchestrator:list')
            ->expectsTable(
                ['ID', 'Name', 'Version', 'Installed', 'Enabled', 'Health', 'Path', 'Providers', 'Capabilities'],
                [
                    [
                        'custom/module-json',
                        'Custom Module',
                        '0.1.0',
                        'yes',
                        'yes',
                        'healthy',
                        $customPath,
                        'Custom\\Module\\Provider',
                        '—',
                    ],
                    [
                        'vendor/foo-module',
                        'Vendor Foo Module',
                        '1.2.3',
                        'yes',
                        'yes',
                        'healthy',
                        $vendorPath,
                        'Vendor\\Foo\\ServiceProvider',
                        'api',
                    ],
                ]
            )
            ->assertSuccessful();
    } finally {
        cleanupSandbox($sandbox);
    }
});

it('marks modules without a base path as unhealthy', function (): void {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $missingPath = $sandbox.'/vendor/foo-module';

        if (is_dir($missingPath)) {
            rmdir($missingPath);
        }

        $customPath = realpath($sandbox.'/modules/custom') ?: $sandbox.'/modules/custom';
        $vendorPath = realpath($missingPath) ?: $missingPath;

        $this->artisan('orchestrator:list')
            ->expectsTable(
                ['ID', 'Name', 'Version', 'Installed', 'Enabled', 'Health', 'Path', 'Providers', 'Capabilities'],
                [
                    [
                        'custom/module-json',
                        'Custom Module',
                        '0.1.0',
                        'yes',
                        'yes',
                        'healthy',
                        $customPath,
                        'Custom\\Module\\Provider',
                        '—',
                    ],
                    [
                        'vendor/foo-module',
                        'Vendor Foo Module',
                        '1.2.3',
                        'yes',
                        'yes',
                        'missing files',
                        $vendorPath,
                        'Vendor\\Foo\\ServiceProvider',
                        'api',
                    ],
                ]
            )
            ->assertSuccessful();
    } finally {
        cleanupSandbox($sandbox);
    }
});
