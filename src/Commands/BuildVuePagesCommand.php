<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\VuePageBuilderService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class BuildVuePagesCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:build-vue-pages
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Build Laravel app VUE pages from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * @throws \JsonException
     */
    public function handle()
    {
        $service = new VuePageBuilderService($this->files, $this->getConfig());
        $service->build();

        Log::channel('magic')->info("Build Vue pages complete!");

        return 0;
    }
}
