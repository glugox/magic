<?php

namespace Glugox\Magic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Glugox\Magic\Magic
 */
class Magic extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Glugox\Magic\Magic::class;
    }
}
