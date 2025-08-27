<?php

use Glugox\Magic\Actions\Build\GenerateMigrationsAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

//uses(TestCase::class)->group('migrations');

beforeEach(function () {
    config()->set('logging.channels.magic_console.level', 'error');
});

afterEach(function () {
    // Clean up generated files
    $files = glob(database_path('migrations') . '/*.php');
    foreach ($files as $file) {
        unlink($file);
    }
});

function sampleConfigs(): array
{
    return collect(File::files(__DIR__ . '/../../stubs/samples'))
        ->filter(fn ($file) => $file->getExtension() === 'json')
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

it('generates migration for each sample config', function () {
    $configFiles = sampleConfigs();

    foreach ($configFiles as $configJsonPath) {
        $configContent = file_get_contents($configJsonPath);
        $this->assertNotFalse($configContent, "Failed to read config file: $configJsonPath");

        $configArray = json_decode($configContent, true);
        $this->assertNotNull($configArray, "Failed to decode JSON from file: $configJsonPath");

        $config = Config::fromJson($configArray);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath
        ])->setConfig($config);

        // Run action
        app(GenerateMigrationsAction::class)($buildContext);

        // Assert migrations exist in temp folder
        foreach ($config->entities as $entity) {
            $tableName = $entity->table ?? Str::plural(Str::snake($entity->name));

            $files = File::files(database_path('migrations'));
            $files = collect($files)
                ->filter(fn ($file) => Str::contains($file->getFilename(), "create_{$tableName}_table.php"))
                ->map(fn ($file) => $file->getFilename())
                ->values()
                ->all();

            $this->assertNotEmpty($files, "Migration file not found for entity: {$entity->name}");
        }
    }
});
