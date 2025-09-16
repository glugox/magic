<?php

use Glugox\Magic\Actions\Build\GenerateMigrationsAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates migration for each sample config', function (): void {

    foreach (sampleConfigsFilePaths() as $configJsonPath) {

        $config = Config::fromJsonFile($configJsonPath);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath,
        ])->setConfig($config);

        // Run action
        app(GenerateMigrationsAction::class)($buildContext);

        // Assert migrations exist in temp folder
        foreach ($config->entities as $entity) {
            $tableName = $entity->getTableName();

            $files = File::files(database_path('migrations'));
            $files = collect($files)
                ->filter(fn ($file) => Str::contains($file->getFilename(), "create_{$tableName}_table.php"))
                ->map(fn ($file) => $file->getFilename())
                ->values()
                ->all();

            $this->assertNotEmpty($files, "Migration file not found for entity: {$entity->name}");
        }

        $allMigrations = File::files(database_path('migrations'));
        // We need to have same count of generated  count($config->entities), $allMigrations
        $this->assertGreaterThan(count($config->entities), count($allMigrations));
    }
});

/*it('properly creates pivot tables for relations', function (): void {

    $buildContext = $this->createBuildContextFromFile('callcenter.json');

    // Run action
    app(GenerateMigrationsAction::class)($buildContext);

    // Assert migrations exist in temp folder
});*/
