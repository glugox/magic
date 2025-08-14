<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\ControllerBuilderService;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;

class BuildControllersCommand extends Command
{
    protected $signature = 'magic:build-controllers {--config= : Path to JSON config file}';

    protected $description = 'Build Laravel app controllers from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $configPath = $this->option('config') ?? config('magic.config_path');
        try {
            $config = ConfigLoader::load($configPath);
        } catch (\Exception $e) {
            $this->error('Failed to load config: '.$e->getMessage());

            return 1;
        }

        $migrationBuilderService = new ControllerBuilderService($config);
        $migrationBuilderService->build();

        $this->info('Build controllers complete!');

        return 0;
    }
}
