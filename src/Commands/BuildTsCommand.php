<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\TsBuilderService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class BuildTsCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:build-ts
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Build TS support files from JSON config';

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
        // Call service to build TS files
        $service = new TsBuilderService($this->files, $this->getConfig());
        $service->build();

        Log::channel('magic')->info('Build TS complete!');

        return 0;
    }
}
