<?php

use Glugox\Magic\Actions\Build\GenerateModelsAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates model for each sample config', function (): void {
    $configFiles = sampleConfigsFilePaths();

    foreach ($configFiles as $configJsonPath) {

        $config = Config::fromJsonFile($configJsonPath);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath,
        ])->setConfig($config);

        // Run action
        app(GenerateModelsAction::class)($buildContext);

        // Assert migrations exist in temp folder
        foreach ($config->entities as $entity) {
            $entityName = $entity->getName();

            $files = File::files(app_path('Models'));
            $files = collect($files)
                ->filter(fn ($file) => Str::contains($file->getFilename(), "{$entityName}.php"))
                ->map(fn ($file) => $file->getFilename())
                ->values()
                ->all();

            $this->assertNotEmpty($files, "Model file not found for entity: {$entityName}");
        }
    }
});

it('uses shared module has name trait when generating package models', function (): void {
    $packagePath = base_path('packages/model-package-'.uniqid());
    File::deleteDirectory($packagePath);

    $context = new BuildContext(
        destinationPath: $packagePath,
        baseNamespace: 'Glugox\\Inventory',
        packageName: 'glugox/inventory',
    );

    $context->setConfig(getFixtureConfig());

    MagicPaths::usePackage($packagePath);
    MagicNamespaces::use('Glugox\\Inventory');

    $action = app(GenerateModelsAction::class);
    $action($context);

    $modelPath = $packagePath.'/src/Models/Order.php';
    expect(File::exists($modelPath))->toBeTrue();

    $contents = File::get($modelPath);
    expect($contents)
        ->toContain('use Glugox\\Module\\Contracts\\HasName;')
        ->and($contents)->toContain('use HasName;');

    MagicPaths::clearPackage();
    MagicNamespaces::clear();
    File::deleteDirectory($packagePath);
});

it('generates models with enum casting when enum fields exist', function (): void {
    // Arrange
    $context = getFixtureBuildContext();
    $action = app(GenerateModelsAction::class);

    $modelPath = app_path('Models/Product.php');
    if (File::exists($modelPath)) {
        File::delete($modelPath);
    }

    // Act
    $action($context);

    // Assert
    expect(File::exists($modelPath))->toBeTrue();

    $contents = File::get($modelPath);

    expect($contents)->toContain("'status' => \\App\\Enums\\ProductStatusEnum::class");

    // Check that does not import enums when no enum fields exist
    $modelWithNoEnumPath = app_path('Models/Category.php');
    $contents = File::get($modelWithNoEnumPath);
    expect($contents)->not->toContain('use App\\Enums\\');

});
