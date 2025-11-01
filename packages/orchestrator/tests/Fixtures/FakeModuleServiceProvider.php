<?php

namespace Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class FakeModuleServiceProvider extends ServiceProvider
{
    public static array $events = [];

    public function register(): void
    {
        static::$events[] = 'register';
    }

    public function boot(): void
    {
        static::$events[] = 'boot';
    }

    public static function reset(): void
    {
        static::$events = [];
    }
}
