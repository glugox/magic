<?php

namespace Glugox\Magic;

use Glugox\Magic\Commands\BuildAppCommand;
use Glugox\Magic\Commands\BuildControllersCommand;
use Glugox\Magic\Commands\BuildMigrationsCommand;
use Glugox\Magic\Commands\BuildModelsCommand;
use Glugox\Magic\Commands\BuildSeedersCommand;
use Glugox\Magic\Commands\BuildVuePagesCommand;
use Glugox\Magic\Commands\FreshCommand;
use Glugox\Magic\Commands\InstallNodePackagesCommand;
use Glugox\Magic\Commands\ListSamplesCommand;
use Glugox\Magic\Commands\PublishFilesCommand;
use Glugox\Magic\Commands\ResetAppCommand;
use Glugox\Magic\Commands\ResetByManifestCommand;
use Glugox\Magic\Commands\ResetLaravelCommand;
use Glugox\Magic\Commands\SuggestionsCommand;
use Glugox\Magic\Commands\VueSidebarUpdaterCommand;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Log\LogIndentTap;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MagicServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/magic.php' => config_path('magic.php'),
        ], 'config');

        // Register your commands only when running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildAppCommand::class,
                ResetAppCommand::class,
                BuildMigrationsCommand::class,
                BuildModelsCommand::class,
                BuildControllersCommand::class,
                BuildVuePagesCommand::class,
                VueSidebarUpdaterCommand::class,
                BuildSeedersCommand::class,
                FreshCommand::class,
                ResetLaravelCommand::class,
                InstallNodePackagesCommand::class,
                PublishFilesCommand::class,
                SuggestionsCommand::class,
                ListSamplesCommand::class,
                ResetByManifestCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/../config/magic.php',
            'magic'
        );

        // Register magic logging channel
        $this->registerMagicLogChannel();

        // Singletons DI
        $this->app->singleton(CodeGenerationHelper::class);
    }

    /**
     * Register the Magic logging channel.
     *
     * This sets up a custom logging channel for the Magic package,
     * which can log to both a file and the console.
     */
    private function registerMagicLogChannel(): void
    {
        if (! config('magic.logging.enabled', true)) {
            return;
        }

        // Define a runtime-only logging channel
        $magicChannel = [
            'driver' => 'stack',
            'channels' => ['magic_file', 'magic_console'],
            'ignore_exceptions' => false,
            'tap' => [LogIndentTap::class],
        ];

        // Merge into logging.channels
        Config::set('logging.channels.magic', $magicChannel);

        // File logger
        Config::set('logging.channels.magic_file', [
            'driver' => 'single',
            'path' => storage_path('logs/magic.log'),
            'level' => 'debug',
        ]);

        // Console logger (for Artisan commands)
        Config::set('logging.channels.magic_console', [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'level' => 'debug',
        ]);
    }
}
