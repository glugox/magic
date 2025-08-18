<?php

namespace Glugox\Magic\Commands;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallNodePackagesCommand extends MagicBaseCommand
{
    protected $signature = 'magic:install-node-packages
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Install Node.js packages required for the Magic app';

    protected array $shadcnComponents = [
        'table',
        'switch',
        // add more components here...
    ];

    protected array $npmPackages = [
        /*'axios',
        'dayjs',*/
        // add more npm packages here...
    ];

    public function handle()
    {
        Log::channel('magic')->info('Checking Node.js dependencies...');

        $packageJsonPath = base_path('package.json');
        $packageJson = file_exists($packageJsonPath)
            ? json_decode(file_get_contents($packageJsonPath), true)
            : [];

        // 1. Install shadcn-vue components
        foreach ($this->shadcnComponents as $component) {
            if ($this->isShadcnInstalled($component, $packageJson)) {
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

        return CommandAlias::SUCCESS;
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
        $this->info($message);

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
