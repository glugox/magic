<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Build\PublishFilesAction;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Support\Facades\Log;

class PublishFilesCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:publish-files
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Publish Magic files to the Laravel application';

    public function handle()
    {
        Log::channel('magic')->info('Starting Magic file publishing...');

        app(PublishFilesAction::class)(BuildContext::fromOptions($this->options()));

        Log::channel('magic')->info('Magic file publishing complete!');

        return 0;
    }
}
