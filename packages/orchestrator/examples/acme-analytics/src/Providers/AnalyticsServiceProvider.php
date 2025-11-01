<?php

namespace Acme\Analytics\Providers;

use Glugox\Orchestrator\Facades\Modules;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    protected string $moduleId = 'acme/analytics';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'acme-analytics');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'acme-analytics');

        if (! Modules::enabled($this->moduleId)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('analytics')
            ->name('acme.analytics.')
            ->group(__DIR__.'/../../routes/web.php');
    }
}
