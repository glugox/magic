<?php

declare(strict_types=1);

use Glugox\Core\ModuleRegistry;
use Illuminate\Support\Facades\File;

it('loads API routes from configured modules', function (): void {
    $modulePath = base_path('modules/demo-module');
    $routeDir = $modulePath.'/routes';

    File::ensureDirectoryExists($routeDir);

    $configPath = __DIR__.'/../../data/builder-simple.json';

    $this->artisan('builder:generate', [
        '--config' => $configPath,
        '--package-path' => $modulePath,
    ])->assertSuccessful();

    config()->set('core.modules', [
        'demo' => [
            'path' => $modulePath,
            'routes' => [
                'routes/api.php',
            ],
        ],
    ]);

    app(ModuleRegistry::class)->refresh();

    $this->getJson('/contacts')
        ->assertOk()
        ->assertJsonFragment([
            'entity' => 'Contact',
        ]);
});
