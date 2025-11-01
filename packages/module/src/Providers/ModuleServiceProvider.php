<?php

namespace Glugox\Module\Providers;

use Glugox\Module\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * @internal This bridge class maintains backwards compatibility for packages
 *           still extending the legacy provider namespace.
 */
class ModuleServiceProvider extends BaseModuleServiceProvider
{
}
