<?php

namespace Glugox\Magic;

use Glugox\Magic\Commands\BuildAppCommand;
use Glugox\Magic\Commands\BuildControllersCommand;
use Glugox\Magic\Commands\BuildMigrationsCommand;
use Glugox\Magic\Commands\BuildModelsCommand;
use Glugox\Magic\Commands\BuildSeedersCommand;
use Glugox\Magic\Commands\BuildTsCommand;
use Glugox\Magic\Commands\BuildVuePagesCommand;
use Glugox\Magic\Commands\FreshCommand;
use Glugox\Magic\Commands\ResetAppCommand;
use Glugox\Magic\Commands\ResetLaravelCommand;
use Glugox\Magic\Commands\VueSidebarUpdaterCommand;
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
                BuildTsCommand::class,
                ResetLaravelCommand::class,
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
    }
}
