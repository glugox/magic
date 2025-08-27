<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Build\InstallNodePackagesAction;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Support\Facades\Log;

class InstallNodePackagesCommand extends MagicBaseCommand
{
    protected $signature = 'magic:install-node-packages
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Install Node.js packages required for the Magic app';

    public function handle()
    {
        // Action call -- Use InstallNodePackagesAction
        app(InstallNodePackagesAction::class)(BuildContext::fromOptions($this->options()));

        Log::channel('magic')->info('Node.js packages installation complete!');
    }
}
