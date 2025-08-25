<?php

namespace Glugox\Magic\Commands;

use Glugox\Ai\AiManager;
use Illuminate\Support\Facades\Log;

class ListSamplesCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:list-samples';

    protected $description = 'List available JSON config samples';

    public function handle()
    {
        $samplesDir = __DIR__ . '/../../stubs/samples';
        if (!is_dir($samplesDir)) {
            $this->error("Samples directory not found: {$samplesDir}");
            return 1;
        }

        $files = scandir($samplesDir);
        $jsonFiles = array_filter($files, fn($file) => str_ends_with($file, '.json'));

        if (empty($jsonFiles)) {
            $this->info("No JSON config samples found in: {$samplesDir}");
            return 0;
        }

        $this->info("Available JSON config samples in {$samplesDir}:\n");
        foreach ($jsonFiles as $file) {
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            $this->line("- {$baseName}");
        }

        Log::channel('magic')->info('Listed JSON config samples.');
        return 0;
    }
}
