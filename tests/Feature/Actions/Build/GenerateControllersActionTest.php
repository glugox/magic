<?php

use Glugox\Magic\Actions\Build\GenerateControllersAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;

it('generates controller and routes for entities', function (): void {

    // Prepare
    $action = app(GenerateControllersAction::class);
    $buildContext = getFixtureBuildContext('resume');

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0);

});

it('generates both API and regular controllers for entities', function (): void {
    // Prepare
    $action = app(GenerateControllersAction::class);
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    // Assert overall generation
    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0);

    // Example entity: User
    $apiControllerPath = app_path('Http/Controllers/Api/UserApiController.php');
    $regularControllerPath = app_path('Http/Controllers/UserController.php');

    // Assert files exist
    expect(File::exists($apiControllerPath))->toBeTrue()
        ->and(File::exists($regularControllerPath))->toBeTrue();

    // Assert API controller has required methods
    $apiContents = File::get($apiControllerPath);
    $expectedApiMethods = [
        'public function index',
        'public function store',
        'public function show',
        'public function update',
        'public function destroy'
    ];
    foreach ($expectedApiMethods as $method) {
        expect($apiContents)->toContain($method);
    }

    // Assert regular controller has required methods
    $regularContents = File::get($regularControllerPath);
    $expectedRegularMethods = [
        'public function index',
        'public function create',
        'public function store',
        'public function show',
        'public function edit',
        'public function update',
        'public function destroy',
    ];
    foreach ($expectedRegularMethods as $method) {
        expect($regularContents)->toContain($method);
    }

    $apiRoutesPath = base_path('routes/app/api.php');
    expect(File::exists($apiRoutesPath))->toBeTrue();

    $apiRoutesContents = File::get($apiRoutesPath);
    expect($apiRoutesContents)
        ->toContain('$moduleApiMiddleware ??= (static function (): array {')
        ->and($apiRoutesContents)
        ->toContain("config('module.api.middleware')")
        ->and($apiRoutesContents)
        ->toContain("config('module.api.prefix')")
        ->and($apiRoutesContents)
        ->toContain("config('module.api.require_auth')")
        ->and($apiRoutesContents)
        ->toContain("config('auth.guards.sanctum')")
        ->and($apiRoutesContents)
        ->toContain('\\Illuminate\\Support\\Facades\\Route::has(\'login\')')
        ->and($apiRoutesContents)
        ->toContain('Route::prefix($moduleApiPrefix)')
        ->and($apiRoutesContents)
        ->toContain('->middleware($moduleApiMiddleware)');
});

it('generates relation controllers for entities', function (): void {

    $action = app(GenerateControllersAction::class);
    $buildContext = getFixtureBuildContext();
    $controllersBase = base_path('app/Http/Controllers/');

    // Act: generate controllers
    $buildContext = $action($buildContext);

    $createdFiles = $buildContext->getFilesGenerationUpdate()->created;

    expect($createdFiles)->toBeArray()->not()->toBeEmpty();

    // Filter relation controllers by naming convention
    $relationControllers = array_filter($createdFiles, function ($file) use ($controllersBase): bool {
        // Only consider files under app/Http/Controllers/
        if (! str_starts_with($file, $controllersBase)) {
            return false;
        }

        // Get path relative to Controllers
        $relative = mb_substr($file, mb_strlen($controllersBase));

        // Must be in a subfolder and not under Api/
        return str_contains($relative, '/')          // in a subfolder
            && ! str_starts_with($relative, 'Api/');
    });

    expect($relationControllers)->not()->toBeEmpty();

    // Strip base path for easier reading
    $relationControllersShort = array_map(fn ($f): string => mb_substr((string) $f, mb_strlen($controllersBase)), $relationControllers);
    // Sort for consistency
    sort($relationControllersShort);

    foreach ($relationControllers as $file) {
        // Check that file exists
        expect(File::exists($file))->toBeTrue();

        $content = File::get($file);

        // Check namespace
        expect($content)->toContain('namespace App\Http\Controllers\\');

        // Check class declaration
        preg_match('/class (\w+) extends Controller/', $content, $matches);
        expect($matches[1] ?? null)->not()->toBeNull();

        // Check that index method exists
        expect($content)->toContain('public function index(');

        // Optionally check that relation placeholder is replaced
        expect($content)->not()->toContain('{{relationName}}');
        expect($content)->not()->toContain('{{controllerClass}}');
        expect($content)->not()->toContain('{{parentModelClass}}');
    }

});

it('extends module controller when generating in package mode', function (): void {
    $packagePath = base_path('tmp/package-build');
    File::deleteDirectory($packagePath);

    MagicPaths::usePackage($packagePath);
    MagicNamespaces::use('Vendor\\Package');

    $buildContext = new BuildContext(
        destinationPath: $packagePath,
        baseNamespace: 'Vendor\\Package',
        packageName: 'vendor/package'
    );
    $buildContext->setConfig(getFixtureConfig());

    try {
        $action = app(GenerateControllersAction::class);
        $buildContext = $action($buildContext);

        $controllerPath = $packagePath.'/src/Http/Controllers/UserController.php';
        $apiControllerPath = $packagePath.'/src/Http/Controllers/Api/UserApiController.php';

        expect(File::exists($controllerPath))->toBeTrue();
        expect(File::exists($apiControllerPath))->toBeTrue();

        $controllerContents = File::get($controllerPath);
        $apiControllerContents = File::get($apiControllerPath);

        expect($controllerContents)
            ->toContain('use Glugox\\Module\\Http\\Controller as ModuleController;')
            ->and($controllerContents)
            ->toContain('extends ModuleController');

        expect($apiControllerContents)
            ->toContain('use Glugox\\Module\\Http\\Controller as ModuleController;')
            ->and($apiControllerContents)
            ->toContain('extends ModuleController');

        $packageApiRoutesPath = $packagePath.'/routes/app/api.php';
        expect(File::exists($packageApiRoutesPath))->toBeTrue();

        $packageApiRoutes = File::get($packageApiRoutesPath);
        expect($packageApiRoutes)
            ->toContain('$moduleApiMiddleware ??= (static function (): array {')
            ->and($packageApiRoutes)
            ->toContain("config('module.api.prefix')")
            ->and($packageApiRoutes)
            ->toContain("config('auth.guards.sanctum')")
            ->and($packageApiRoutes)
            ->toContain('Route::prefix($moduleApiPrefix)')
            ->and($packageApiRoutes)
            ->toContain('->middleware($moduleApiMiddleware)');
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
        app()->forgetInstance(GenerateControllersAction::class);
        File::deleteDirectory($packagePath);
    }
});
