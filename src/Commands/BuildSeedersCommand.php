<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Build\GenerateSeedersAction;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;

class BuildSeedersCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:build-seeders
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Build Laravel app seeders from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    public function handle()
    {
        // Action call -- Use GenerateSeedersAction
        app(GenerateSeedersAction::class)(BuildContext::fromOptions($this->options()));

        Log::channel('magic')->info('Build complete!');

        return 0;
    }
}
