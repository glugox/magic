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
        $providerClass = 'MagicPackageServiceProvider';
        $providerNamespace = MagicNamespaces::providers();
        $providerPath = MagicPaths::app("Providers/{$providerClass}.php");
        $isNew = ! File::exists($providerPath);

        $viewNamespace = Str::kebab(class_basename(MagicNamespaces::base()));

        $contents = StubHelper::loadStub('package/service-provider.stub', [
            'providerNamespace' => $providerNamespace,
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
}
