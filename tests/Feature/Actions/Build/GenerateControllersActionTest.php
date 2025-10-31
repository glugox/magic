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
});

/*
Expected generated controllers based on current JSON config:

- Regular controllers: 11
    User, Role, Warehouse, Category, Product, Order, OrderItem, Shipment, CarrierDetail, Attachment, Review

- API controllers: 11
    (one API controller per entity)

- Relation controllers: 20
    User: 4 (Order, Shipment, Role, Attachment)
    Role: 1 (User)
    Warehouse: 2 (Product, User)
    Category: 1 (Product)
    Product: 4 (Warehouse, Category, Attachment, Review)
    Order: 3 (User, OrderItem, Shipment)
    OrderItem: 2 (Order, Product)
    Shipment: 2 (Order, CarrierDetail)
    CarrierDetail: 1 (Shipment)
    Attachment: 0
    Review: 0
*/
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
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
        app()->forgetInstance(GenerateControllersAction::class);
        File::deleteDirectory($packagePath);
    }
});
