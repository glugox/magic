<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ResetLaravelCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:reset-laravel
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Reset Laravel app parts';

    public function handle(): int
    {
        Log::channel('magic')->info('Starting Laravel reset...');

        $sourcePath = base_path('.magic/backup');
        $destinationPath = base_path();

        if (! File::exists($sourcePath)) {
            Log::channel('magic')->warning('No Laravel backup found under .magic/backup. Nothing to restore.');

            return 0;
        }

        app(CopyDirectoryAction::class)($sourcePath, $destinationPath, true);
        Log::channel('magic')->info('Reset Laravel complete!');

        return 0;
    }
}
