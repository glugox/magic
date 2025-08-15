<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\SeederBuilderService;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class BuildSeedersCommand extends Command
{
    protected $signature = 'magic:build-seeders {--config= : Path to JSON config file}';

    protected $description = 'Build Laravel app seeders from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle()
    {
        $configPath = $this->option('config') ?? config('magic.config_path');

        try {
            $config = ConfigLoader::load($configPath);
        } catch (\Exception $e) {
            $this->error("Failed to load config: " . $e->getMessage());
            return 1;
        }

        $service = new SeederBuilderService($this->files, $config);
        $service->build();

        Log::channel('magic')->info("Build complete!");

        return 0;
    }
}
