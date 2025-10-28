<?php

use Glugox\Magic\Actions\Build\InitializePackageAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;

it('scaffolds composer manifest and service provider for package builds', function () {
    $tempDir = base_path('package-init-'.uniqid());
    File::deleteDirectory($tempDir);

    $context = (new BuildContext(
        destinationPath: $tempDir,
        baseNamespace: 'Vendor\\Package',
        packageName: 'vendor/package',
    ));

    $config = Config::fromJson([
        'app' => [
            'name' => 'Billing',
            'description' => 'Invoices and payments',
            'capabilities' => ['http:web', 'http:api'],
        ],
        'entities' => [],
    ]);

    $context->setConfig($config);

    MagicNamespaces::use('Vendor\\Package');

    app(InitializePackageAction::class)($context);

    $composerPath = $tempDir.'/composer.json';
    expect(File::exists($composerPath))->toBeTrue();

    $composer = json_decode(File::get($composerPath), true);
    expect($composer)
        ->toBeArray()
        ->and($composer['name'] ?? null)->toBe('vendor/package')
        ->and($composer['autoload']['psr-4']['Vendor\\Package\\'] ?? null)->toBe('src/')
        ->and($composer['require']['glugox/module'] ?? null)->toBe('dev-main')
        ->and($composer['extra']['laravel']['providers'] ?? [])
        ->toContain('Vendor\\Package\\Providers\\PackageServiceProvider');

    $providerPath = $tempDir.'/src/Providers/PackageServiceProvider.php';
    expect(File::exists($providerPath))->toBeTrue();

    $providerContents = File::get($providerPath);
    expect($providerContents)
        ->toContain('namespace Vendor\\Package\\Providers;')
        ->and($providerContents)->toContain('loadRoutesFrom(')
        ->and($providerContents)->toContain('loadViewsFrom(')
        ->and($providerContents)->toContain('loadMigrationsFrom(');

    $modulePath = $tempDir.'/module.json';
    expect(File::exists($modulePath))->toBeTrue();

    $module = json_decode(File::get($modulePath), true);
    expect($module)
        ->toBeArray()
        ->and($module['module'] ?? null)
        ->toMatchArray([
            'id' => 'vendor/package',
            'name' => 'Billing',
            'namespace' => 'Vendor\\Package',
            'description' => 'Invoices and payments',
            'capabilities' => ['http:web', 'http:api'],
        ]);

    MagicPaths::clearPackage();
    MagicNamespaces::clear();
    File::deleteDirectory($tempDir);
});

it('configures local module repository when in dev mode', function () {
    $tempDir = base_path('package-init-'.uniqid());
    File::deleteDirectory($tempDir);

    $context = new BuildContext(
        destinationPath: $tempDir,
        baseNamespace: 'Vendor\\Package',
        packageName: 'vendor/package',
    );

    $config = Config::fromJson([
        'app' => [
            'name' => 'Billing',
            'description' => 'Invoices and payments',
            'capabilities' => ['http:web', 'http:api'],
            'devMode' => true,
        ],
        'entities' => [],
    ]);

    $context->setConfig($config);

    MagicNamespaces::use('Vendor\\Package');

    app(InitializePackageAction::class)($context);

    $composerPath = $tempDir.'/composer.json';
    expect(File::exists($composerPath))->toBeTrue();

    $composer = json_decode(File::get($composerPath), true);
    $expectedPath = str_replace('\\', '/', dirname($tempDir).'/module');

    $repositories = collect($composer['repositories'] ?? []);

    expect($repositories->contains(function ($repo) use ($expectedPath) {
        return is_array($repo)
            && ($repo['type'] ?? null) === 'path'
            && ($repo['url'] ?? null) === $expectedPath;
    }))->toBeTrue();

    expect($composer['minimum-stability'] ?? null)->toBe('dev');

    MagicPaths::clearPackage();
    MagicNamespaces::clear();
    File::deleteDirectory($tempDir);
});
