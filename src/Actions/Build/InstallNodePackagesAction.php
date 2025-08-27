<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[ActionDescription(
    name: 'install_node_packages',
    description: 'Ensures that specified shadcn-vue components and npm packages are installed in the project.',
    parameters: ['context' => 'The BuildContext instance.']
)]
class InstallNodePackagesAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * List of shadcn-vue components to ensure are installed.
     */
    protected array $shadcnComponents = [
        'table',
        'switch',
        // add more components here...
    ];

    /**
     * List of npm packages to ensure are installed.
     */
    protected array $npmPackages = [
        /*'axios',
        'dayjs',*/
        // add more npm packages here...
    ];

    /**
     * @param BuildContext $context
     * @return BuildContext
     */
    public function __invoke(BuildContext $context): BuildContext
    {
        Log::channel('magic')->info('Checking Node.js dependencies...');

        $packageJsonPath = base_path('package.json');
        $packageJson = file_exists($packageJsonPath)
            ? json_decode(file_get_contents($packageJsonPath), true)
            : [];

        // 1. Install shadcn-vue components
        foreach ($this->shadcnComponents as $component) {
            if ($this->isShadcnInstalled($component)) {
                Log::channel('magic')->info("shadcn-vue component [$component] already installed, skipping...");

                continue;
            }

            $this->runProcess([
                '/usr/local/bin/npx',
                'shadcn-vue@latest',
                'add',
                $component,
            ], "Installing shadcn-vue component [$component]...");
        }

        // 2. Install npm packages
        foreach ($this->npmPackages as $package) {
            if ($this->isPackageInstalled($package, $packageJson)) {
                Log::channel('magic')->info("npm package [$package] already installed, skipping...");

                continue;
            }

            $this->runProcess([
                'npm', 'install', $package, '--save-dev',
            ], "Installing npm package [$package]...");
        }

        Log::channel('magic')->info('All Node.js dependencies are up to date!');

        return $context;
    }

    /**
     * Check if a shadcn-vue component is installed.
     */
    protected function isShadcnInstalled(string $component): bool
    {
        $uiPath = resource_path("js/components/ui/{$component}.vue");

        return file_exists($uiPath) || is_dir(resource_path("js/components/ui/{$component}"));
    }

    /**
     * Check if a package is installed in package.json.
     */
    protected function isPackageInstalled(string $package, array $packageJson): bool
    {
        return isset($packageJson['dependencies'][$package])
            || isset($packageJson['devDependencies'][$package]);
    }

    /**
     * Run a shell command and handle output.
     */
    protected function runProcess(array $command, string $message): void
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
