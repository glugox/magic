<?php

use Glugox\Magic\Actions\Build\GenerateSeedersAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates seeder for each sample config', function (): void {

    // Mock
    $mock = Mockery::mock(CodeGenerationHelper::class);

    // 1 for admin user + 11 models seeders registration in DatabaseSeeder.php + 2 pivot models (RoleUserPivotSeeder, CategoryProductPivotSeeder)
    $mock->shouldReceive('appendCodeBlock')->times(14)->andReturn(true);
    $mock->shouldNotReceive('removeRegion');
    $this->app->instance(CodeGenerationHelper::class, $mock);

    $configFiles = sampleConfigsFilePaths();

    foreach ($configFiles as $configJsonPath) {

        $config = Config::fromJsonFile($configJsonPath);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath,
        ])->setConfig($config);

        // Run action
        app(GenerateSeedersAction::class)($buildContext);

        // Assert migrations exist in temp folder
        foreach ($config->entities as $entity) {
            $entityName = $entity->getName();

            $files = File::files(database_path('seeders'));
            $files = collect($files)
                ->filter(fn ($file) => Str::contains($file->getFilename(), "{$entityName}Seeder.php"))
                ->map(fn ($file) => $file->getFilename())
                ->values()
                ->all();

            $this->assertNotEmpty($files, "Seeder file not found for entity: {$entityName}");
        }
    }
});

it('skips DatabaseSeeder scaffolding in package builds', function (): void {
    $packagePath = base_path('packages/seeder-package');
    File::deleteDirectory($packagePath);

    $context = getFixtureBuildContext();
    $context->destinationPath = $packagePath;

    MagicPaths::usePackage($packagePath);

    try {
        $seederPath = MagicPaths::database('seeders/DatabaseSeeder.php');
        expect(File::exists($seederPath))->toBeFalse();

        app(GenerateSeedersAction::class)($context);

        expect(File::exists($seederPath))->toBeFalse();
    } finally {
        MagicPaths::clearPackage();
    }
});
