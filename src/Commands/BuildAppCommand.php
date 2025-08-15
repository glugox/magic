<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\ConsoleBlock;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BuildAppCommand extends MagicBaseCommand
{
    protected $signature = 'magic:build
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

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

        // Run all build steps
        foreach (self::BUILD_STEPS as $command => $message) {
            $this->runStep($command, $message);
        }

        // Run migrations and optional seeding
        Log::channel('magic')->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        if ($this->getConfig()->dev->seedEnabled) {
            Log::channel('magic')->info("Seeding the database with default seedCount of {$this->getConfig()->dev->seedCount}...");
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->warn('Database seeding is disabled in the config.');
        }

        Log::channel('magic')->info('✅ Build complete!');

        return CommandAlias::SUCCESS;
    }

    /**
     * Run a single build step with consistent messaging.
     */
    private function runStep(string $command, string $message): void
    {
        $this->block->info($message.'...');
        $this->call($command, [
            '--config' => $this->getConfigPath(),
            '--starter' => $this->option('starter'),
            '--set' => $this->option('set'),
        ]
        );
        // $this->block->info("✅ {$message} completed!");
    }
}
