<?php

namespace Glugox\Magic\Commands;

use Exception;
use Glugox\Magic\Actions\Build\GenerateAppAction;
use Glugox\Magic\Support\ConsoleBlock;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\Log;
use ReflectionException;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BuildAppCommand extends MagicBaseCommand
{
    protected $signature = 'magic:build
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}
    {--package-path= : Directory where the generated application should be written}
    {--package-namespace= : Root namespace to use when generating into a package}
    {--package-name= : Composer package name (vendor/package) for package builds}';

    protected $description = 'Build Laravel app parts from JSON config';

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function handle(): int
    {

        // Initialize console block for structured output
        $block = new ConsoleBlock($this);
        $options = $this->options();

        $packageMode = ! empty($options['package-path']);

        if ($packageMode) {
            $namespace = (string) ($options['package-namespace'] ?? '');
            $packageName = (string) ($options['package-name'] ?? '');

            if ($namespace === '' || $packageName === '') {
                throw new Exception('Package builds require both --package-namespace and --package-name options.');
            }

            MagicNamespaces::use($namespace);
            MagicPaths::usePackage($options['package-path']);
        } else {
            MagicNamespaces::clear();
            MagicPaths::clearPackage();
        }

        $block->info('✨[MAGIC]✨ Building Laravel app...');

        // Reset if manifest file exists
        // $this->call('magic:reset-by-manifest');

        // If manifest file exists , throw an error
        $manifestPath = MagicPaths::storage('magic/generated_files.json');
        if (file_exists($manifestPath)) {
            throw new Exception('Manifest file exist. Please reset the app first by running  magic:reset');
        }

        // V2 - Actions
        // @phpstan-ignore-next-line
        try {
            $buildContext = app(GenerateAppAction::class)($options);
            if ($buildContext->hasErrors()) {
                Log::channel('magic')->error('Build failed: '.$buildContext->error());

                return CommandAlias::FAILURE;
            }

            Log::channel('magic')->info('✅ Build complete!');

            return CommandAlias::SUCCESS;
        } finally {
            if ($packageMode) {
                MagicPaths::clearPackage();
            }

            MagicNamespaces::clear();
        }
    }
}
