<?php

namespace Glugox\Ai\Drivers;

use Glugox\Ai\Contracts\AiDriver;

class Driver
{
    /**
     * Return default driver by checking the config file.
     */
    public static function default(): AiDriver
    {
        $driverName = config('ai.default_driver');
        $driverClass = config("ai.drivers.$driverName.class");

        return app($driverClass);
    }
}
