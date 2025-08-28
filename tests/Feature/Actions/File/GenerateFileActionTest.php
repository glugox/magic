<?php

use Illuminate\Support\Facades\File;

use Glugox\Magic\Actions\Files\GenerateFileAction;

test('it generates a model file', function () {
    $path = base_path('app/Models/User.php');

    // run your generator with overridden output dir
    app(GenerateFileAction::class)(
        $path,
        'model.stub'
    );

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toContain('model.stub');
});
