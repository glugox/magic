<?php

namespace Acme\Blog\Providers;

use Glugox\Orchestrator\Facades\Modules;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    protected string $moduleId = 'acme/blog';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/module.php', 'modules.'.$this->moduleId);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'acme-blog');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'acme-blog');

        if (! Modules::enabled($this->moduleId)) {
            return;
        }

        $this->mapRoutes();
    }

    protected function mapRoutes(): void
    {
        Route::middleware('web')
            ->name('acme.blog.')
            ->group(__DIR__.'/../../routes/web.php');
    }
}
