<?php

use Glugox\Magic\Actions\Build\InitializePackageAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

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

it('registers routes from generated package files', function () {
    $tempDir = base_path('package-init-'.uniqid());
    File::deleteDirectory($tempDir);

    $context = new BuildContext(
        destinationPath: $tempDir,
        baseNamespace: 'Vendor\\Package',
        packageName: 'vendor/package',
    );

    $context->setConfig(Config::fromJson([
        'app' => [
            'name' => 'Demo',
            'description' => null,
            'capabilities' => ['http:web'],
        ],
        'entities' => [],
    ]));

    MagicNamespaces::use('Vendor\\Package');

    try {
        app(InitializePackageAction::class)($context);

        $routesDir = $tempDir.'/routes';
        File::ensureDirectoryExists($routesDir);

        File::put($routesDir.'/app.php', <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('package-test', fn () => 'ok')->name('package-test');
PHP);

        if (File::exists($routesDir.'/web.php')) {
            File::delete($routesDir.'/web.php');
        }

        require_once $tempDir.'/src/Providers/PackageServiceProvider.php';

        $providerClass = 'Vendor\\Package\\Providers\\PackageServiceProvider';
        $provider = new $providerClass($this->app);
        $provider->register();
        $provider->boot();

        expect(Route::has('package-test'))->toBeTrue();
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
        File::deleteDirectory($tempDir);
    }
});
