<?php

namespace Glugox\Magic\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ResetLaravelCommand extends Command
{
    protected $signature = 'magic:reset-laravel';

    protected $description = 'Reset Laravel app parts';

    public function handle()
    {
        $this->info('Starting Laravel reset...');

        $source = __DIR__.'/../../stubs/laravel';
        $destination = base_path();

        $files = new Filesystem;

        $this->copyDirectoryRecursively($source, $destination, $files);

        $this->info('Reset Laravel complete!');

        return 0;
    }

    protected function copyDirectoryRecursively(string $source, string $destination, Filesystem $files)
    {
        $items = $files->allFiles($source);

        foreach ($items as $item) {
            $relativePath = $item->getRelativePathname();
            $targetPath = $destination.'/'.$relativePath;

            // Ensure directory exists
            $files->ensureDirectoryExists(dirname($targetPath));

            // Copy the file, overwrite if exists
            if ($files->copy($item->getRealPath(), $targetPath)) {
                $this->info("Copied: {$relativePath}");
            } else {
                $this->error("Failed to copy: {$relativePath}");
            }
        }
    }
}
