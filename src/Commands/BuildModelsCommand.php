<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Services\ModelBuilderService;
use Illuminate\Support\Facades\Log;

class BuildModelsCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:build-models
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Build Laravel app models from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \JsonException
     */
    public function handle()
    {
        $migrationBuilderService = new ModelBuilderService($this->getConfig());
        $migrationBuilderService->build();

        Log::channel('magic')->info("Build models complete!");

        return 0;
    }
}
