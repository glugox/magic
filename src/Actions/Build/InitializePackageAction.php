<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

#[ActionDescription(
    name: 'initialize_package',
    description: 'Prepares the target package directory before generation begins.',
    parameters: ['context' => 'The BuildContext containing configuration data.']
)]
class InitializePackageAction implements DescribableAction
{
    use AsDescribableAction;
    use CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        if (! $context->isPackageBuild()) {
            return $context;
        }

        $destination = $context->getDestinationPath();
        if ($destination === null) {
            return $context;
        }

        MagicPaths::usePackage($destination);

        foreach ($this->directoriesToEnsure() as $directory) {
            $path = MagicPaths::base($directory);
            if (! File::exists($path)) {
                File::ensureDirectoryExists($path);
                $context->registerCreatedFolder($path);
            }
        }

        $this->writeComposerManifest($context);
        $this->writeModuleManifest($context);
        $this->ensureServiceProvider($context);

        return $context;
    }

    /**
     * Directories that should exist inside the package prior to generation.
     *
     * @return string[]
     */
    protected function directoriesToEnsure(): array
    {
        return [
            '',
            'src',
            'database',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'resources',
            'resources/js',
            'resources/js/components',
            'resources/js/pages',
            'resources/js/types',
            'resources/views',
            'routes',
            'storage/magic',
            'tests',
        ];
    }

    protected function writeComposerManifest(BuildContext $context): void
    {
        $packageName = $context->getPackageName();
        if ($packageName === null) {
            return;
        }

        $composerPath = MagicPaths::base('composer.json');
        $existing = [];
        $isNew = ! File::exists($composerPath);

        if (! $isNew) {
            $contents = File::get($composerPath);
            $decoded = json_decode($contents, true);
            $existing = is_array($decoded) ? $decoded : [];
        }

        $baseNamespace = MagicNamespaces::base();
        $providerFqcn = MagicNamespaces::providers('MagicPackageServiceProvider');

        $existing['name'] = $packageName;
        $existing['type'] = $existing['type'] ?? 'library';
        $existing['autoload']['psr-4'][$baseNamespace.'\\'] = 'src/';
        $existing['autoload-dev']['psr-4'][$baseNamespace.'\\Tests\\'] = 'tests/';

        $providers = $existing['extra']['laravel']['providers'] ?? [];
        if (! in_array($providerFqcn, $providers, true)) {
            $providers[] = $providerFqcn;
        }
        $existing['extra']['laravel']['providers'] = array_values($providers);

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
        File::put($composerPath, $json);

        if ($isNew) {
            $context->registerGeneratedFile($composerPath);
        } else {
            $context->registerUpdatedFile($composerPath);
        }
    }

    protected function ensureServiceProvider(BuildContext $context): void
    {

        // Package name
        $packageName = $context->getPackageName();

        // Provider
        $idShortName = Str::studly(Str::afterLast($packageName, '/'));
        $providerClass = Str::title($idShortName) . 'ServiceProvider';

        $providerNamespace = MagicNamespaces::providers();
        $providerPath = MagicPaths::app("Providers/{$providerClass}.php");
        $isNew = ! File::exists($providerPath);

        $viewNamespace = Str::kebab(class_basename(MagicNamespaces::base()));


        $contents = StubHelper::loadStub('package/service-provider.stub', [
            'providerNamespace' => MagicNamespaces::providers(),
            'providerClass' => $providerClass,
            'viewNamespace' => $viewNamespace,
        ]);

        File::put($providerPath, $contents);

        if ($isNew) {
            $context->registerGeneratedFile($providerPath);
        } else {
            $context->registerUpdatedFile($providerPath);
        }
    }

    protected function writeModuleManifest(BuildContext $context): void
    {
        $packageName = $context->getPackageName();
        if ($packageName === null) {
            return;
        }

        $modulePath = MagicPaths::base('module.json');
        $existing = [];
        $isNew = ! File::exists($modulePath);

        if (! $isNew) {
            $contents = File::get($modulePath);
            $decoded = json_decode($contents, true);
            $existing = is_array($decoded) ? $decoded : [];
        }

        $config = null;

        try {
            $config = $context->getConfig();
        } catch (RuntimeException) {
            $config = null;
        }

        $module = $existing ?? [];

        $module['id'] = $packageName;
        $module['name'] = $config?->app->name ?? Str::headline(Str::afterLast($context->getBaseNamespace(), '\\'));
        $module['namespace'] = $context->getBaseNamespace();

        // Provider
        $idShortName = Str::studly(Str::afterLast($packageName, '/'));
        $module['providers'] = [MagicNamespaces::providers(Str::title($idShortName) . 'ServiceProvider')];

        if ($config !== null) {
            if ($config->app->description !== null) {
                $module['description'] = $config->app->description;
            } elseif (! array_key_exists('description', $module)) {
                $module['description'] = null;
            }

            $module['capabilities'] = $config->app->capabilities;
        } else {
            $module['capabilities'] = $module['capabilities'] ?? [];
        }

        $existing = $module;

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
        File::put($modulePath, $json);

        if ($isNew) {
            $context->registerGeneratedFile($modulePath);
        } else {
            $context->registerUpdatedFile($modulePath);
        }
    }
}
