<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\TsBuilderService;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class BuildTsCommand extends Command
{
    protected $signature = 'magic:build-ts {--config= : Path to JSON config file}';

    protected $description = 'Build TS support files from JSON config';

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
            $this->error('Failed to load config: '.$e->getMessage());

            return 1;
        }

        $service = new TsBuilderService($this->files, $config);
        $service->build();

        Log::channel('magic')->info('Build TS complete!');

        return 0;
    }
}
