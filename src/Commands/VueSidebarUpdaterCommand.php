<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Build\UpdateVuePagesAction;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Support\Facades\Log;

class VueSidebarUpdaterCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:update-vue-pages
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Update Laravel app VUE pages from JSON config';

    /**
     * Constructor for the command.
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle(): int
    {
        app(UpdateVuePagesAction::class)(BuildContext::fromOptions($this->options()));

        Log::channel('magic')->info('Update Vue sidebar complete!');

        return 0;
    }
}
