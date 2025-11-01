<?php

declare(strict_types=1);

namespace Glugox\Core;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Core module orchestration bindings and publishes configuration.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/core.php', 'core');

        $this->app->singleton(ModuleRegistry::class, function ($app): ModuleRegistry {
            return new ModuleRegistry(
                $app->make(Repository::class),
                $app->make(Filesystem::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/core.php' => config_path('core.php'),
        ], 'core-config');

        $this->app->booted(function (): void {
            $this->app->make(ModuleRegistry::class)->registerRoutes();
        });
    }
}
