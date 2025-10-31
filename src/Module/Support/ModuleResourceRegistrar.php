<?php

namespace Glugox\Module\Support;

use Illuminate\Support\ServiceProvider;

/**
 * Coordinates registering module routes and resources against a service provider.
 */
class ModuleResourceRegistrar
{
    public function __construct(
        protected ServiceProvider $provider,
        protected string $basePath,
        protected string $viewNamespace,
    ) {
    }

    /**
     * Register the configured route files with the provider.
     *
     * @param  string[]  $relativeFiles
     */
    public function registerRoutes(array $relativeFiles): void
    {
        foreach ($relativeFiles as $relativeFile) {
            $path = $this->path($relativeFile);

            if (is_file($path)) {
                $this->provider->loadRoutesFrom($path);
            }
        }
    }

    /**
     * Register view namespaces for the module if available.
     */
    public function registerViews(): void
    {
        $viewsPath = $this->path('resources/views');

        if (is_dir($viewsPath)) {
            $this->provider->loadViewsFrom($viewsPath, $this->viewNamespace);
        }
    }

    /**
     * Register translation resources for the module if present.
     */
    public function registerTranslations(): void
    {
        $langPath = $this->path('resources/lang');

        if (! is_dir($langPath)) {
            return;
        }

        $this->provider->loadTranslationsFrom($langPath, $this->viewNamespace);

        if (method_exists($this->provider, 'loadJsonTranslationsFrom')) {
            $this->provider->loadJsonTranslationsFrom($langPath);
        }
    }

    /**
     * Register database migrations bundled with the module.
     */
    public function registerMigrations(): void
    {
        $migrationsPath = $this->path('database/migrations');

        if (is_dir($migrationsPath)) {
            $this->provider->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Expose public assets for publishing.
     */
    public function registerAssets(?string $publishTag = null, ?string $assetNamespace = null): void
    {
        $assetsPath = $this->path('public');

        if (! is_dir($assetsPath)) {
            return;
        }

        $assetNamespace ??= $this->viewNamespace;
        $publishTag ??= $assetNamespace.'-assets';

        $target = ModulePaths::join(public_path('vendor'), $assetNamespace);

        $this->provider->publishes([
            $assetsPath => $target,
        ], $publishTag);
    }

    protected function path(string ...$segments): string
    {
        return ModulePaths::join($this->basePath, ...$segments);
    }
}
