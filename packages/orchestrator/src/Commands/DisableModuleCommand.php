<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

class DisableModuleCommand extends Command
{
    protected $signature = 'orchestrator:disable {module : The module identifier}';

    protected $description = 'Disable a module while keeping it installed.';

    public function handle(ModuleManager $modules): int
    {
        $id = (string) $this->argument('module');

        try {
            $modules->disable($id);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Module [%s] disabled.', $id));

        return self::SUCCESS;
    }
}
