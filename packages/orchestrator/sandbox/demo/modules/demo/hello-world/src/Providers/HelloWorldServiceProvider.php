<?php

declare(strict_types=1);

namespace Demo\HelloWorld\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HelloWorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings are required for the demo module.
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function (): void {
                Route::get('hello-world', static fn (): array => [
                    'message' => 'Hello from the Hello World module!',
                ]);
            });
    }
}
