<?php

it('summarises orchestrator status', function () {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $this->artisan('orchestrator:doctor')
            ->expectsOutputToContain('Orchestrator configuration')
            ->expectsOutputToContain('Base path')
            ->expectsOutputToContain('Manifest file')
            ->expectsOutputToContain('WARNING')
            ->expectsOutput('No module issues detected.')
            ->assertSuccessful();
    } finally {
        cleanupSandbox($sandbox);
    }
});

it('reports module level issues', function () {
    $sandbox = createSandbox();

    try {
        populateSandbox($sandbox);

        $modulePath = $sandbox.'/vendor/foo-module';
        if (is_dir($modulePath)) {
            rmdir($modulePath);
        }

        $this->app['config']->set('orchestrator', orchestratorConfig($sandbox));

        $this->artisan('orchestrator:doctor')
            ->expectsOutputToContain('Potential issues detected:')
            ->expectsOutputToContain('Module [vendor/foo-module] base path does not exist')
            ->assertSuccessful();
    } finally {
        cleanupSandbox($sandbox);
    }
});
