<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

class InstallModuleCommand extends Command
{
    protected $signature = 'orchestrator:install {module? : The module identifier}';

    protected $description = 'Install a module.';

    public function handle(ModuleManager $modules): int
    {
        $modulesSpecs = [];
        $id = (string) $this->argument('module');
        $modules->reload();

        // Find all available modules specs from ./specs/*.json
        foreach ($modules->specs() as $spec) {
            // Check passed id
            if (!empty($id) && $spec->id() !== $id) {
                continue;
            }
            $modulesSpecs[] = $spec;
        }

        foreach ($modulesSpecs as $spec) {
            $this->info(sprintf('Installing module [%s]...', $spec->id()));

            try {
                $modules->install($spec->id());
            } catch (InvalidArgumentException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info(sprintf('Module [%s] installed.', $spec->id()));
        }

        return self::SUCCESS;
    }
}
