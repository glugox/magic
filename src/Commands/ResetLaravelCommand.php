<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Files\CopyDirectoryAction;
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

        $sourcePath = __DIR__.'/../../stubs/laravel';
        $destinationPath = base_path();

        app(CopyDirectoryAction::class)($sourcePath, $destinationPath, true);
        Log::channel('magic')->info('Reset Laravel complete!');

        return 0;
    }
}
