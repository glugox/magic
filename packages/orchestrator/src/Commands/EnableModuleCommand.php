<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

class EnableModuleCommand extends Command
{
    protected $signature = 'orchestrator:enable {module : The module identifier} {--install : Install the module before enabling it}';

    protected $description = 'Enable a module and register its service providers.';

    public function handle(ModuleManager $modules): int
    {
        $id = (string) $this->argument('module');

        try {
            if ($this->option('install')) {
                $modules->install($id);
            }

            $modules->enable($id);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Module [%s] enabled.', $id));

        return self::SUCCESS;
    }
}
