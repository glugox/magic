<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\ActionRegistry;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Support\Facades\Log;

class RunActionCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:run-action
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--action= : Action class to run}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Publish Magic files to the Laravel application';

    public function handle(): int
    {
        /** @var string $alias */
        $alias = $this->option('action');
        /** @var ActionRegistry $registry */
        $registry = app(ActionRegistry::class);
        $actionClass = $registry->get($alias);

        if (empty($actionClass) || ! class_exists($actionClass)) {
            $this->error("Invalid or missing action alias: `{$alias}`");
            $this->warn('Available actions: '.implode(', ', array_keys($registry->all())));

            return 1;
        }

        Log::channel('magic')->info("Starting action: $actionClass");

        // @phpstan-ignore-next-line
        app($actionClass)(BuildContext::fromOptions($this->options()));

        Log::channel('magic')->info("Action $actionClass complete!");

        return 0;
    }
}
