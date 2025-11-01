<?php

namespace Glugox\Ai\Facades;

use Glugox\Ai\AiManager;
use Illuminate\Support\Facades\Facade;

class Ai extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AiManager::class;
    }
}
