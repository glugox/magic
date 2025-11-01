<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('generates an API route for the first entity', function (): void {
    $configPath = __DIR__.'/../../data/builder-simple.json';
    $packagePath = base_path('packages/builder-demo');

    $result = Artisan::call('builder:generate', [
        '--config' => $configPath,
        '--package-path' => $packagePath,
    ]);

    expect($result)->toBe(0);

    $routePath = $packagePath.'/routes/api.php';
    expect($routePath)->toBeFile();

    $content = file_get_contents($routePath);
    expect($content)
        ->toContain("Route::get('contacts'")
        ->toContain("'entity' => 'Contact'")
        ->toContain("'fields' => [\n            'id',\n            'name',\n            'email',\n        ],");
});
