<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\ModuleManager;
use Illuminate\Console\Command;

class ListModulesCommand extends Command
{
    protected $signature = 'orchestrator:list';

    protected $description = 'Display the discovered modules and their status.';

    public function handle(ModuleManager $modules): int
    {
        $rows = $modules->all()->map(function (ModuleDescriptor $module): array {
            $providers = $module->providers();
            $capabilities = $module->capabilities();

            return [
                'id' => $module->id(),
                'name' => $module->name(),
                'version' => $module->version(),
                'installed' => $module->isInstalled() ? 'yes' : 'no',
                'enabled' => $module->isEnabled() ? 'yes' : 'no',
                'health' => $module->healthStatus(),
                'path' => $module->basePath(),
                'providers' => $providers === [] ? '—' : implode(PHP_EOL, $providers),
                'capabilities' => $capabilities === [] ? '—' : implode(', ', $capabilities),
            ];
        });

        if ($rows->isEmpty()) {
            $this->info('No modules have been discovered.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Version', 'Installed', 'Enabled', 'Health', 'Path', 'Providers', 'Capabilities'],
            $rows->map(fn (array $row): array => array_values($row))->all()
        );

        return self::SUCCESS;
    }
}
