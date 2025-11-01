<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleManager;
use Illuminate\Console\Command;

class ReloadModulesCommand extends Command
{
    protected $signature = 'orchestrator:reload {--no-cache : Do not write the manifest after reloading modules}';

    protected $description = 'Flush the manifest cache and rediscover available modules.';

    public function handle(ModuleManager $modules): int
    {
        $writeManifest = ! $this->option('no-cache');
        $discovered = $modules->discover($writeManifest);

        $this->info(sprintf('Discovered %d modules.', count($discovered)));

        return self::SUCCESS;
    }
}
