<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[ActionDescription(
    'Installs required Composer packages for the project'
)]
class InstallComposerPackagesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected array $composerPackages = [
        'glugox/model-meta',
        // add more Composer packages here...
    ];

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        Log::channel('magic')->info('Checking Composer dependencies...');

        foreach ($this->composerPackages as $package) {
            if ($this->isComposerPackageInstalled($package)) {
                Log::channel('magic')->info("Composer package {$package} is already installed.");

                continue;
            }

            Log::channel('magic')->info("Installing Composer package: {$package}...");
            $this->runProcess(
                ['/usr/local/bin/composer', 'require', $package],
                "Installing Composer package: {$package}..."
            );
        }

        Log::channel('magic')->info('All Composer packages are up to date!');

        return $context;
    }

    /**
     * Check if Composer package is installed.
     */
    protected function isComposerPackageInstalled(string $package): bool
    {
        $process = new Process(['/usr/local/bin/composer', 'show', $package]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Run a Symfony Process command and handle output and errors.
     */
    protected function runProcess(array $command, string $message): void
    {
        Log::channel('magic')->info($message);

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
