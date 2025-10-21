<?php

use Glugox\Magic\Actions\Build\GenerateActionsAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates console commands for command-type entity actions', function (): void {
    File::deleteDirectory(app_path('Console/Actions'));

    $buildContext = getFixtureBuildContext();

    $action = app(GenerateActionsAction::class);

    $action($buildContext);

    foreach ($buildContext->getConfig()->entities as $entity) {
        foreach ($entity->getActions() as $configAction) {
            if ($configAction->type !== 'command' || empty($configAction->command)) {
                continue;
            }

            $className = Str::studly(Str::replace('.', '_', $configAction->name)).'Action.php';
            $path = app_path('Actions/'.$className);

            expect(File::exists($path))->toBeTrue();
            expect(File::get($path))->toContain($configAction->name);
        }
    }
});
