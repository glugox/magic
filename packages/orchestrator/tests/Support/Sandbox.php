<?php

function createSandbox(): string
{
    $sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'orch-'.uniqid('', true);
    mkdir($sandbox, 0777, true);

    return $sandbox;
}

function orchestratorConfig(string $basePath, array $overrides = []): array
{
    return array_replace_recursive([
        'base_path' => $basePath,
        'manifest_path' => 'bootstrap/cache/modules.php',
        'installed_path' => 'vendor/composer/installed.json',
        'module_json_paths' => ['modules/*/module.json'],
        'module_specs_path' => 'specs/modules',
        'modules_default_version' => '^1.0',
        'auto_install' => true,
        'auto_enable' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $options
 */
function populateSandbox(string $basePath, array $options = []): void
{
    $composerDir = $basePath.'/vendor/composer';
    $modulesDir = $basePath.'/modules/custom';
    $cacheDir = $basePath.'/bootstrap/cache';
    $vendorModuleDir = $basePath.'/vendor/foo-module';

    foreach ([$composerDir, $modulesDir, $cacheDir, $vendorModuleDir] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    $defaultComposer = [
        'name' => 'vendor/foo-module',
        'type' => 'laravel-module',
        'version' => '1.2.3',
        'install_path' => '../foo-module',
        'extra' => [
            'laravel' => [
                'providers' => ['Vendor\\Foo\\ServiceProvider'],
            ],
            'glugox-module' => [
                'id' => 'vendor/foo-module',
                'name' => 'Vendor Foo Module',
                'version' => '1.2.3',
                'routes' => 'routes/web.php',
                'migrations' => 'database/migrations',
                'capabilities' => ['api'],
            ],
        ],
    ];

    $composerModule = array_replace_recursive($defaultComposer, $options['composer'] ?? []);

    $installed = [
        'packages' => [
            $composerModule,
        ],
    ];

    file_put_contents(
        $composerDir.'/installed.json',
        json_encode($installed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $defaultModuleJson = [
        'id' => 'custom/module-json',
        'name' => 'Custom Module',
        'version' => '0.1.0',
        'routes' => ['routes/api.php', 'routes/web.php'],
        'providers' => ['Custom\\Module\\Provider'],
    ];

    $moduleJson = $options['module_json'] ?? $defaultModuleJson;

    if ($moduleJson !== null) {
        if (! is_dir(dirname($modulesDir.'/module.json'))) {
            mkdir(dirname($modulesDir.'/module.json'), 0777, true);
        }

        file_put_contents(
            $modulesDir.'/module.json',
            json_encode($moduleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

function cleanupSandbox(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $fileInfo) {
        if ($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());
        } else {
            unlink($fileInfo->getPathname());
        }
    }

    rmdir($directory);
}
