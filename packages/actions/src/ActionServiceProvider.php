<?php

namespace Glugox\Actions;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/actions.php' => config_path('actions.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/actions.php', 'actions');
    }

    protected function registerRoutes(): void
    {
        $cfg = config('actions.route');
        Route::group([
            'prefix' => $cfg['prefix'] ?? 'api/actions',
            //'middleware' => $cfg['middleware'] ?? ['web'/*,'auth'*/],
        ], function () {
            Route::post('run', [Http\Controllers\ActionController::class, 'run'])->name('api.actions.run');
            Route::get('runs/{run}', [Http\Controllers\ActionController::class, 'show'])->name('api.actions.show');
        });
    }
}
