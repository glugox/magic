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
        'src/Providers',
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

test('clearing package restores application defaults', function () {
    $tempDir = base_path('package-output-restore');
    File::deleteDirectory($tempDir);

    MagicPaths::usePackage($tempDir);
    MagicPaths::clearPackage();

    expect(MagicPaths::isUsingPackage())
        ->toBeFalse()
        ->and(MagicPaths::base())
        ->toBe(base_path())
        ->and(MagicPaths::app('Models'))
        ->toBe(app_path('Models'))
        ->and(MagicPaths::resource('js/types'))
        ->toBe(resource_path('js/types'))
        ->and(config('magic.paths.support_types_file'))
        ->toBe(resource_path('js/types/support.ts'));

    File::deleteDirectory($tempDir);
});

test('relative package paths are normalized against the base path', function () {
    $relative = 'package-output-relative';
    $resolved = base_path($relative);
    File::deleteDirectory($resolved);

    MagicPaths::usePackage($relative);

    expect(MagicPaths::base())
        ->toBe($resolved)
        ->and(MagicPaths::routes('api.php'))
        ->toBe($resolved.'/routes/api.php');

    MagicPaths::clearPackage();
    File::deleteDirectory($resolved);
});
