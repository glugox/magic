<?php

namespace Glugox\Orchestrator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Glugox\Orchestrator\OrchestratorManager
 */
class Orchestrator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'orchestrator';
    }
}
