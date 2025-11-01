<?php

namespace Glugox\Ai;

use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register config file
        $this->mergeConfigFrom(__DIR__.'/../config/ai.php', 'ai');

        // Register other bindings, services, etc.
        $this->app->singleton(AiManager::class, function ($app) {
            return new AiManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/ai.php' => config_path('ai.php'),
        ], 'config');

        // Load routes, views, translations if needed
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai');
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ai');
    }
}
