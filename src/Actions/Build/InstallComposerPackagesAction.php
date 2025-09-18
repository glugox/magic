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
    name: 'install_composer_packages',
    description: 'Ensures that specified Composer packages are installed in the project.',
    parameters: ['context' => 'The BuildContext instance.']
)]
class InstallComposerPackagesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * List of Composer packages with flexible definitions.
     *
     * Example entry:
     * [
     *   'name'    => 'pestphp/pest',
     *   'version' => '^2.0',           // optional, not used yet
     *   'options' => ['--dev']         // per-package options
     * ]
     *
     * @var array<int, array{name: string, version?: string|null, options?: array<int, string>}>
     */
    protected array $composerPackages = [
        [
            'name' => 'glugox/model-meta',
            'version' => null,
            'options' => [],
        ],
        [
            'name' => 'pestphp/pest',
            'version' => null,
            'options' => ['--with-all-dependencies'],
        ],
        [
            'name' => 'pestphp/pest-plugin-browser',
            'version' => null,
            'options' => [],
        ],
    ];

    /**
     * Options applied to all packages.
     *
     * @var string[]
     */
    protected array $globalComposerOptions = [
        '--dev', // example global option
    ];

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        Log::channel('magic')->info('Checking Composer dependencies...');

        foreach ($this->composerPackages as $pkg) {
            $name = $pkg['name'];
            $version = $pkg['version'] ?? null;
            $pkgOptions = $pkg['options'] ?? [];

            if ($this->isComposerPackageInstalled($name)) {
                Log::channel('magic')->info("Composer package {$name} is already installed.");

                continue;
            }

            // Merge global + per-package + context options
            /** @var string[] $globalOptions */
            $globalOptions = $context->getConfig()->getConfigValue('composer_global_options', $this->globalComposerOptions);
            $options = array_merge($globalOptions, $pkgOptions);

            // Package string (later you can append version if needed)
            /** @var string $packageString */
            $packageString = $version ? "{$name}:{$version}" : $name;

            /** @var string[] $command */
            $command = array_merge(
                ['/usr/local/bin/composer', 'require', $packageString],
                $options
            );

            Log::channel('magic')->info("Installing Composer package: {$packageString} with options: ".implode(' ', $options));

            $this->runProcess($command, "Installing Composer package: {$packageString}...");
        }

        Log::channel('magic')->info('All Composer packages are up to date!');

        return $context;
    }

    protected function isComposerPackageInstalled(string $package): bool
    {
        $process = new Process(['/usr/local/bin/composer', 'show', $package]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Runs a Symfony Process command and logs output.
     *
     * @param  string[]  $command
     */
    protected function runProcess(array $command, string $message): void
    {
        Log::channel('magic')->info($message);

        $process = new Process($command, base_path());
        $process->setTimeout(null);

        $process->run(function ($type, string $buffer) {
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
