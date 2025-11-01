<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\LocalPackages;
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
        $providerClass = $this->resolveServiceProviderClass($context);
        $providerFqcn = MagicNamespaces::providers($providerClass);

        $existing['name'] = $packageName;
        $existing['type'] = $existing['type'] ?? 'library';
        $existing['autoload']['psr-4'][$baseNamespace.'\\'] = 'src/';
        $existing['autoload-dev']['psr-4'][$baseNamespace.'\\Tests\\'] = 'tests/';

        $existing['require'] = isset($existing['require']) && is_array($existing['require'])
            ? $existing['require']
            : [];

        $moduleConstraint = $this->resolveModuleVersionConstraint($context);
        if ($moduleConstraint !== null && ! array_key_exists('glugox/module', $existing['require'])) {
            $existing['require']['glugox/module'] = $moduleConstraint;
            ksort($existing['require']);
        }

        $moduleRepository = $this->resolveModuleRepositoryDefinition($context);
        if ($moduleRepository !== null) {
            $repositories = $existing['repositories'] ?? [];
            if (! is_array($repositories)) {
                $repositories = [];
            }

            $alreadyRegistered = false;
            foreach ($repositories as $repository) {
                if (! is_array($repository)) {
                    continue;
                }

                if (($repository['type'] ?? null) === $moduleRepository['type']
                    && ($repository['url'] ?? null) === $moduleRepository['url']) {
                    $alreadyRegistered = true;
                    break;
                }
            }

            if (! $alreadyRegistered) {
                $repositories[] = $moduleRepository;
            }

            $existing['repositories'] = $repositories;
        }

        $providers = $existing['extra']['laravel']['providers'] ?? [];
        if (! in_array($providerFqcn, $providers, true)) {
            $providers[] = $providerFqcn;
        }
        $existing['extra']['laravel']['providers'] = array_values($providers);

        if ($this->isDevMode($context)) {
            $existing['minimum-stability'] = $existing['minimum-stability'] ?? 'dev';
        }

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
        File::put($composerPath, $json);

        if ($isNew) {
            $context->registerGeneratedFile($composerPath);
        } else {
            $context->registerUpdatedFile($composerPath);
        }
    }

    protected function resolveModuleVersionConstraint(BuildContext $context): ?string
    {
        $config = $this->getConfigIfAvailable($context);

        if ($config !== null && $config->app->isDevMode()) {
            return 'dev-main';
        }

        return 'dev-main';
    }

    /**
     * @return array{type: string, url: string, options?: array<string, mixed>}|null
     */
    protected function resolveModuleRepositoryDefinition(BuildContext $context): ?array
    {
        $repositoryBase = MagicPaths::base();

        return LocalPackages::repositoryFor('glugox/module', $repositoryBase);
    }

    protected function getConfigIfAvailable(BuildContext $context): ?\Glugox\Magic\Support\Config\Config
    {
        try {
            return $context->getConfig();
        } catch (RuntimeException) {
            return null;
        }
    }

    protected function isDevMode(BuildContext $context): bool
    {
        $config = $this->getConfigIfAvailable($context);

        return $config !== null && $config->app->isDevMode();
    }

    protected function ensureServiceProvider(BuildContext $context): void
    {

        $providerClass = $this->resolveServiceProviderClass($context);

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
        $providerClass = $this->resolveServiceProviderClass($context);
        $module['providers'] = [MagicNamespaces::providers($providerClass)];

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

    protected function resolveServiceProviderClass(BuildContext $context): string
    {
        $packageName = $context->getPackageName();

        if ($packageName === null || $packageName === '') {
            return 'MagicPackageServiceProvider';
        }

        $shortName = Str::afterLast($packageName, '/');
        $shortName = $shortName !== '' ? $shortName : $packageName;

        $studly = Str::studly($shortName);

        if ($studly === '') {
            return 'MagicPackageServiceProvider';
        }

        return $studly.'ServiceProvider';
    }
}
