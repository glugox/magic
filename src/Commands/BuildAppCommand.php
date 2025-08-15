<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\ConfigLoader;
use Glugox\Magic\Support\ConsoleBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BuildAppCommand extends Command
{
    protected $signature = 'magic:build
        {--config= : Path to JSON config file}
        {--starter= : Starter template to use}';

    protected $description = 'Build Laravel app parts from JSON config';

    /**
     * The build steps to run in order.
     */
    private const array BUILD_STEPS = [
        'magic:build-migrations' => 'Building migrations',
        'magic:build-models' => 'Building models',
        'magic:build-seeders' => 'Building seeders',
        'magic:build-controllers' => 'Building controllers',
        'magic:build-ts' => 'Building TypeScript support files',
        'magic:build-vue-pages' => 'Building Vue pages',
        'magic:update-vue-pages' => 'Updating Vue sidebar',
    ];

    /**
     * Console block for structured output.
     *
     * ex output:
     *
     * ```
     *
     * Building migrations...
     *
     * ✅ Building migrations completed!
     *
     * ```
     */
    private ConsoleBlock $block;

    /**
     * @throws \JsonException
     */
    public function handle(): int
    {

        // Initialize console block for structured output
        $this->block = new ConsoleBlock($this);
        $this->block->info('✨[MAGIC]✨ Building Laravel app...');

        // Resolve config path (option > config file default)
        $configPath = $this->option('config') ?? config('magic.config_path');

        // Handle starter template setup
        if ($starterPath = $this->setupStarterTemplate($this->option('starter'))) {
            $configPath = $starterPath;
        }

        // Load config
        $config = ConfigLoader::load($configPath);
        if (! $config->isValid()) {
            return CommandAlias::FAILURE;
        }

        // Run all build steps
        foreach (self::BUILD_STEPS as $command => $message) {
            $this->runStep($command, $message, $configPath);
        }

        // Run migrations and optional seeding
        Log::channel('magic')->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        if ($config->dev->isSeedEnabled()) {
            Log::channel('magic')->info('Seeding the database...');
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->warn('Database seeding is disabled in the config.');
        }

        Log::channel('magic')->info('✅ Build complete!');

        return CommandAlias::SUCCESS;
    }

    /**
     * Setup starter template if provided.
     */
    private function setupStarterTemplate(?string $starter): ?string
    {
        if (! $starter) {
            Log::channel('magic')->info('No starter template specified, using default.');

            return null;
        }

        Log::channel('magic')->info("Using starter template: {$starter}");

        $source = __DIR__."/../../stubs/samples/{$starter}.json";
        $destination = base_path("{$starter}.json");

        if (! File::exists($source)) {
            $this->error("Starter template file not found: {$source}");

            return null;
        }

        File::copy($source, $destination);
        Log::channel('magic')->info("Copied starter template to: {$destination}");

        return $destination;
    }

    /**
     * Run a single build step with consistent messaging.
     */
    private function runStep(string $command, string $message, string $configPath): void
    {
        $this->block->info($message.'...');
        $this->call($command, ['--config' => $configPath]);
        // $this->block->info("✅ {$message} completed!");
    }
}
