<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Illuminate\Support\Facades\Log;

class ResetByManifestCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:reset-by-manifest';

    protected $description = 'If manifest files exists, will use it to delete files';

    /**
     * @throws \JsonException
     */
    public function handle()
    {
        FilesGenerationUpdate::deleteGeneratedFiles();

        Log::channel('magic')->info('ResetByManifestCommand complete!');

        return 0;
    }
}
