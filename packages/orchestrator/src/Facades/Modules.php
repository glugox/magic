<?php

namespace Glugox\Orchestrator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection installed()
 * @method static \Illuminate\Support\Collection enabledModules()
 * @method static bool isEnabled(string $id)
 * @method static bool enabled(string $id)
 * @method static \Glugox\Orchestrator\ModuleDescriptor module(string $id)
 * @method static string path(string $id, ?string $subPath = null)
 * @method static void enable(string $id)
 * @method static void disable(string $id)
 * @method static void install(string $id)
 * @method static void uninstall(string $id, bool $dropData = false)
 * @method static array discover(bool $writeManifest = true)
 * @method static array reload(bool $writeManifest = true)
 * @method static void cache()
 * @method static void clearCache()
 * @method static void registerEnabledModules(?\Illuminate\Contracts\Foundation\Application $application = null)
 * @see \Glugox\Orchestrator\ModuleManager
 */
class Modules extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'modules';
    }
}
