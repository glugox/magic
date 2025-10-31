<?php

namespace Glugox\Module\Providers;

use Glugox\Module\Support\ModulePaths;
use Glugox\Module\Support\ModuleResourceRegistrar;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Base service provider for generated modules.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    private ?ModuleResourceRegistrar $registrar = null;

    public function boot(): void
    {
        $registrar = $this->registrar();

        $registrar->registerRoutes($this->routeFiles());
        $registrar->registerViews();
        $registrar->registerTranslations();
        $registrar->registerMigrations();

        $this->registerAssets($registrar);
    }

    /**
     * Resolve the registrar responsible for bootstrapping module resources.
     */
    protected function registrar(): ModuleResourceRegistrar
    {
        if ($this->registrar === null) {
            $this->registrar = new ModuleResourceRegistrar(
                $this,
                $this->moduleBasePath(),
                $this->moduleViewNamespace(),
            );
        }

        return $this->registrar;
    }

    /**
     * Publish module assets if available.
     */
    protected function registerAssets(ModuleResourceRegistrar $registrar): void
    {
        $registrar->registerAssets(
            $this->assetPublishTag(),
            $this->assetNamespace(),
        );
    }

    /**
     * Determine the namespace used for publishing assets.
     */
    protected function assetNamespace(): string
    {
        return $this->moduleViewNamespace();
    }

    /**
     * Determine the publish tag applied to module assets.
     */
    protected function assetPublishTag(): string
    {
        return Str::slug($this->moduleViewNamespace()).'-assets';
    }

    /**
     * Route files that should be registered during boot.
     *
     * @return string[]
     */
    protected function routeFiles(): array
    {
        return [
            'routes/web.php',
            'routes/app.php',
            'routes/api.php',
            'routes/app/api.php',
        ];
    }

    /**
     * Absolute base path for the module on disk.
     */
    abstract protected function moduleBasePath(): string;

    /**
     * Namespace used for module views and translations.
     */
    abstract protected function moduleViewNamespace(): string;

    /**
     * Build an absolute path relative to the module base directory.
     */
    protected function modulePath(string ...$segments): string
    {
        return ModulePaths::join($this->moduleBasePath(), ...$segments);
    }
}
