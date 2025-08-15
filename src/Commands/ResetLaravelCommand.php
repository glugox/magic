<?php

namespace Glugox\Magic\Commands;

use Illuminate\Filesystem\Filesystem;
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

    public function handle()
    {
        Log::channel('magic')->info("Starting Laravel reset...");

        $source = __DIR__ . '/../../stubs/laravel';
        $destination = base_path();

        $files = new Filesystem();

        $this->copyDirectoryRecursively($source, $destination, $files);

        Log::channel('magic')->info("Reset Laravel complete!");
        return 0;
    }

    protected function copyDirectoryRecursively(string $source, string $destination, Filesystem $files): void
    {
        $items = $files->allFiles($source);

        foreach ($items as $item) {
            $relativePath = $item->getRelativePathname();
            $targetPath = $destination . '/' . $relativePath;

            // Ensure directory exists
            $files->ensureDirectoryExists(dirname($targetPath));

            // Copy the file, overwrite if exists
            if ($files->copy($item->getRealPath(), $targetPath)) {
                Log::channel('magic')->info("Copied: {$relativePath}");
            } else {
                Log::channel('magic')->error("Failed to copy: {$relativePath}");
            }
        }
    }
}
