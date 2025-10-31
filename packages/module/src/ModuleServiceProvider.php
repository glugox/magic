<?php

namespace Glugox\Module;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/attachments.php', 'attachments');
    }

    public function boot(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/attachments.php' => config_path('attachments.php'),
            ], 'glugox-module-config');
        }

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $routesPath = __DIR__.'/../routes/attachments.php';

        if (! File::exists($routesPath)) {
            return;
        }

        $this->loadRoutesFrom($routesPath);
    }
}
