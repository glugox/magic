<?php

declare(strict_types=1);

namespace Glugox\Builder;

use Glugox\Builder\Commands\GenerateModuleCommand;
use Illuminate\Support\ServiceProvider;

class BuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings are required for the lightweight builder package.
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateModuleCommand::class,
        ]);
    }
}
