<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\VueSidebarUpdaterService;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VueSidebarUpdaterCommand extends Command
{
    protected $signature = 'magic:update-vue-pages {--config= : Path to JSON config file}';

    protected $description = 'Update Laravel app VUE pages from JSON config';

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

        $service = new VueSidebarUpdaterService($config);
        $service->update();

        Log::channel('magic')->info('Update Vue sidebar complete!');

        return 0;
    }
}
