<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;

class FreshCommand extends Command
{
    protected $signature = 'magic:fresh {--config= : Path to JSON config file} {--starter= : Starter template to use}';

    protected $description = 'Rebuild Laravel app parts from JSON config';

    public function handle()
    {

        // Call reset command
        $this->call('magic:reset', [
            '--config' => $this->option('config'),
            '--starter' => $this->option('starter'),
        ]);

        // Call build command
        $this->call('magic:build', [
            '--config' => $this->option('config'),
            '--starter' => $this->option('starter'),
        ]);


        $this->info("Fresh complete!");

        return 0;
    }
}
