<?php

use Glugox\Magic\Actions\Build\GenerateModelMetaAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates model meta for each sample config', function (): void {

    $buildCotext = getFixtureBuildContext();
    $action = app(GenerateModelMetaAction::class);

    // Run action
    $action($buildCotext);

    // Assert migrations exist in temp folder
    foreach ($buildCotext->getConfig()->entities as $entity) {
        $entityName = $entity->getName();

        $files = File::files(app_path('Meta/Models'));
        $files = collect($files)
            ->filter(fn ($file) => Str::contains($file->getFilename(), "{$entityName}Meta.php"))
            ->map(fn ($file) => $file->getFilename())
            ->values()
            ->all();

        $this->assertNotEmpty($files, "ModelMeta file not found for entity: {$entityName}");
    }
});
