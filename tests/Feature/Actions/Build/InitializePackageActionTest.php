<?php

use Glugox\Magic\Actions\Build\InitializePackageAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;

it('scaffolds composer manifest and service provider for package builds', function () {
    $tempDir = base_path('package-init-'.uniqid());
    File::deleteDirectory($tempDir);

    $context = new BuildContext(
        destinationPath: $tempDir,
        baseNamespace: 'Vendor\\Package',
        packageName: 'vendor/package',
    );

    MagicNamespaces::use('Vendor\\Package');

    app(InitializePackageAction::class)($context);

    $composerPath = $tempDir.'/composer.json';
    expect(File::exists($composerPath))->toBeTrue();

    $composer = json_decode(File::get($composerPath), true);
    expect($composer)
        ->toBeArray()
        ->and($composer['name'] ?? null)->toBe('vendor/package')
        ->and($composer['autoload']['psr-4']['Vendor\\Package\\'] ?? null)->toBe('src/')
        ->and($composer['extra']['laravel']['providers'] ?? [])
        ->toContain('Vendor\\Package\\Providers\\MagicPackageServiceProvider');

    $providerPath = $tempDir.'/src/Providers/MagicPackageServiceProvider.php';
    expect(File::exists($providerPath))->toBeTrue();

    $providerContents = File::get($providerPath);
    expect($providerContents)
        ->toContain('namespace Vendor\\Package\\Providers;')
        ->and($providerContents)->toContain('loadRoutesFrom(')
        ->and($providerContents)->toContain('loadViewsFrom(')
        ->and($providerContents)->toContain('loadMigrationsFrom(');

    MagicPaths::clearPackage();
    MagicNamespaces::clear();
    File::deleteDirectory($tempDir);
});
