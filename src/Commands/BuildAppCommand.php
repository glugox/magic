<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Build\GenerateAppAction;
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
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function handle(): int
    {

        // Initialize console block for structured output
        $block = new ConsoleBlock($this);
        $block->info('✨[MAGIC]✨ Building Laravel app...');

        // Reset if manifest file exists
        // $this->call('magic:reset-by-manifest');

        // If manifest file exists , throw an error
        if (file_exists(storage_path('magic/generated_files.json'))) {
            throw new \Exception('Manifest file exist. Please reset the app first by running  magic:reset');
        }

        // V2 - Actions
        $buildContext = app(GenerateAppAction::class)($this->options());
        if ($buildContext->hasErrors()) {
            Log::channel('magic')->error('Build failed: '.$buildContext->error());

            return CommandAlias::FAILURE;
        }

        Log::channel('magic')->info('✅ Build complete!');

        return CommandAlias::SUCCESS;
    }
}
