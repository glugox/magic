<?php

use Glugox\Magic\Actions\Build\InitializePackageAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\LocalPackages;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

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

    $expectedRepository = LocalPackages::repositoryFor('glugox/module', $tempDir);
    expect($expectedRepository)->not->toBeNull();
    expect($composer['repositories'] ?? [])->toContain($expectedRepository);

    $providerPath = $tempDir.'/src/Providers/PackageServiceProvider.php';
    expect(File::exists($providerPath))->toBeTrue();

    $providerContents = File::get($providerPath);
    expect($providerContents)
        ->toContain('namespace Vendor\\Package\\Providers;')
        ->and($providerContents)->toContain('use Glugox\\Module\\ModuleServiceProvider;')
        ->and($providerContents)->toContain('extends ModuleServiceProvider')
        ->and($providerContents)->toContain('function register(): void')
        ->and($providerContents)->toContain('$this->registerModule();')
        ->and($providerContents)->toContain('function boot(): void')
        ->and($providerContents)->toContain('$this->bootModule();')
        ->and($providerContents)->toContain("$this->publishModuleAssets('package-assets');")
        ->and($providerContents)->toContain('function moduleBasePath(): string')
        ->and($providerContents)->toContain("return dirname(__DIR__, 2);")
        ->and($providerContents)->toContain("return 'package';");

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

        $viewsDir = $tempDir.'/resources/views';
        File::ensureDirectoryExists($viewsDir);
        File::put($viewsDir.'/example.blade.php', 'example');

        $langDir = $tempDir.'/resources/lang/en';
        File::ensureDirectoryExists($langDir);
        File::put($langDir.'/messages.php', <<<'PHP'
<?php

return [
    'title' => 'Example Title',
];
PHP);

        File::put($tempDir.'/resources/lang/en.json', json_encode(['json-key' => 'Json Example']).PHP_EOL);

        $migrationsDir = $tempDir.'/database/migrations';
        File::ensureDirectoryExists($migrationsDir);
        File::put($migrationsDir.'/2024_01_01_000000_create_examples_table.php', '<?php return [];');

        $publicDir = $tempDir.'/public';
        File::ensureDirectoryExists($publicDir);
        File::put($publicDir.'/module.js', 'console.log("ok");');

        require_once $tempDir.'/src/Providers/PackageServiceProvider.php';

        $providerClass = 'Vendor\\Package\\Providers\\PackageServiceProvider';
        $provider = new $providerClass($this->app);
        $provider->register();
        $provider->boot();

        Lang::setLocale('en');

        expect(Route::has('package-test'))->toBeTrue();
        expect(View::exists('package::example'))->toBeTrue();
        expect(Lang::get('package::messages.title'))->toBe('Example Title');
        expect(app('migrator')->paths())->toContain($migrationsDir);

        $published = IlluminateServiceProvider::pathsToPublish($providerClass);
        expect($published)->toHaveKey($publicDir);
        expect($published[$publicDir])->toBe(public_path('vendor/package'));

        $tagged = IlluminateServiceProvider::pathsToPublish($providerClass, 'package-assets');
        expect($tagged[$publicDir] ?? null)->toBe(public_path('vendor/package'));
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
        File::deleteDirectory($tempDir);
    }
});
