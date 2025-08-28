<?php

use Glugox\Magic\Actions\Build\GenerateModelsAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;



it('generates model for each sample config', function () {
    $configFiles = sampleConfigsFilePaths();

    foreach ($configFiles as $configJsonPath) {

        $config = Config::fromJsonFile($configJsonPath);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath
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
