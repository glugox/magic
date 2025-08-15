<?php

namespace Glugox\Magic\Commands;

use Illuminate\Support\Facades\Log;

class FreshCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:fresh
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Rebuild Laravel app parts from JSON config';

    public function handle()
    {
        // User can change the config values directly in the command line
        $overrides = $this->option('set');

        // Call reset command
        $this->call('magic:reset', [
            '--config' => $this->option('config'),
            '--starter' => $this->option('starter'),
            '--set' => $overrides,
        ]);

        // Call build command
        $this->call('magic:build', [
            '--config' => $this->option('config'),
            '--starter' => $this->option('starter'),
            '--set' => $overrides,
        ]);

        Log::channel('magic')->info('Fresh complete!');

        return 0;
    }
}
