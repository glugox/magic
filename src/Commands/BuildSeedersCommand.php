<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\SeederBuilderService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

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
     * @throws \JsonException
     */
    public function handle()
    {
        $service = new SeederBuilderService($this->files, $this->getConfig());
        $service->build();

        Log::channel('magic')->info("Build complete!");

        return 0;
    }
}
