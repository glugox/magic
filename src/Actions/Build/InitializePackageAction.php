<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;

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
}
