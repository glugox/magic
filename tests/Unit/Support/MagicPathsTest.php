<?php

use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;

test('package paths are applied when using package mode', function () {
    $tempDir = base_path('package-output');
    File::deleteDirectory($tempDir);

    MagicPaths::usePackage($tempDir);

    expect(MagicPaths::base())
        ->toBe($tempDir)
        ->and(MagicPaths::app('Models'))
        ->toBe($tempDir.'/src/Models')
        ->and(MagicPaths::resource('js/types'))
        ->toBe($tempDir.'/resources/js/types')
        ->and(config('magic.paths.support_types_file'))
        ->toBe($tempDir.'/resources/js/types/support.ts');

    foreach ([
        'src',
        'resources/js',
        'database/migrations',
        'routes',
        'tests',
    ] as $dir) {
        expect(File::isDirectory($tempDir.'/'.$dir))->toBeTrue();
    }

    MagicPaths::clearPackage();
    File::deleteDirectory($tempDir);
});
