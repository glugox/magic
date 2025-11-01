<?php

namespace Glugox\Orchestrator\Commands;

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\ModuleManager;
use Glugox\Orchestrator\Support\OrchestratorConfig;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'orchestrator:doctor';

    protected $description = 'Diagnose the orchestrator installation and highlight potential issues.';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['modules:doctor'];

    public function handle(OrchestratorConfig $config, ModuleManager $modules): int
    {
        $checks = [
            $this->directoryCheck('Base path', $config->basePath(), true, 'Configured base path does not exist.'),
            $this->directoryCheck('Modules directory', $config->modulesPath(), false, 'Modules directory was not found.'),
            $this->directoryCheck('Manifest directory', dirname($config->manifestPath()), true, 'Manifest directory does not exist.'),
            $this->fileCheck(
                'Manifest file',
                $config->manifestPath(),
                false,
                'Manifest file has not been generated. Run orchestrator:reload to create it.'
            ),
            $this->fileCheck(
                'Composer installed.json',
                $config->installedJsonPath(),
                true,
                'Composer metadata file missing. Modules installed via Composer cannot be discovered.'
            ),
            $this->directoryCheck(
                'Module specs directory',
                $config->moduleSpecsPath(),
                false,
                'Module specification directory missing. Specs will be ignored.'
            ),
        ];

        $this->info('Orchestrator configuration');
        $this->table(['Check', 'Value', 'Status', 'Notes'], array_map(
            fn (array $row) => [
                $row['check'],
                $row['value'],
                strtoupper($row['status']),
                $row['message'],
            ],
            $checks
        ));

        $issues = $this->collectModuleIssues($modules);

        if ($issues === []) {
            $this->info('No module issues detected.');

            return self::SUCCESS;
        }

        $this->warn('Potential issues detected:');

        foreach ($issues as $issue) {
            $this->line(sprintf(' - %s', $issue));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{check: string, value: string, status: string, message: string}
     */
    protected function directoryCheck(string $label, string $path, bool $required, string $missingMessage): array
    {
        $status = 'ok';
        $message = '';

        if (! is_dir($path)) {
            $status = $required ? 'error' : 'warning';
            $message = $missingMessage;
        } elseif (! is_readable($path)) {
            $status = 'warning';
            $message = 'Directory is not readable.';
        }

        return [
            'check' => $label,
            'value' => $path,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @return array{check: string, value: string, status: string, message: string}
     */
    protected function fileCheck(string $label, string $path, bool $required, string $missingMessage): array
    {
        $status = 'ok';
        $message = '';

        if (! is_file($path)) {
            $status = $required ? 'error' : 'warning';
            $message = $missingMessage;
        } elseif (! is_readable($path)) {
            $status = 'warning';
            $message = 'File is not readable.';
        }

        return [
            'check' => $label,
            'value' => $path,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function collectModuleIssues(ModuleManager $modules): array
    {
        $issues = [];

        foreach ($modules->all() as $module) {
            $issues = array_merge($issues, $this->inspectModule($module));
        }

        return array_values(array_unique($issues));
    }

    /**
     * @return array<int, string>
     */
    protected function inspectModule(ModuleDescriptor $module): array
    {
        $issues = [];

        if ($module->isInstalled() && ! is_dir($module->basePath())) {
            $issues[] = sprintf('Module [%s] base path does not exist: %s', $module->id(), $module->basePath());
        }

        if ($module->isEnabled() && ! $module->isInstalled()) {
            $issues[] = sprintf('Module [%s] is enabled but not installed.', $module->id());
        }

        return $issues;
    }
}
