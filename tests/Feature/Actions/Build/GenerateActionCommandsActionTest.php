<?php

use Glugox\Magic\Actions\Build\GenerateActionCommandsAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates console commands for command-type entity actions', function (): void {
    File::deleteDirectory(app_path('Console/Actions'));

    $buildContext = getFixtureBuildContext();

    $action = app(GenerateActionCommandsAction::class);

    $action($buildContext);

    foreach ($buildContext->getConfig()->entities as $entity) {
        foreach ($entity->getActions() as $configAction) {
            if ($configAction->type !== 'command' || empty($configAction->command)) {
                continue;
            }

            $className = Str::studly($configAction->name).'Action.php';
            $path = app_path('Console/Actions/'.$entity->getName().'/'.$className);

            expect(File::exists($path))->toBeTrue();
            expect(File::get($path))->toContain($configAction->command);
        }
    }
});
