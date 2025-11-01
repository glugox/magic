<?php

namespace Glugox\Module;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally left blank. Child providers should call helper methods
        // such as registerModule() to opt-in to shared registration logic.
    }

    public function boot(): void
    {
        // Intentionally left blank. Child providers should call helper methods
        // such as bootModule() to opt-in to shared bootstrap logic.
    }

    /**
     * Register module level configuration and console bindings.
     */
    protected function registerModule(): void
    {
        $this->registerModuleConfigs();
        $this->registerModuleCommands();
    }

    /**
     * Bootstrap module resources including routes, views, translations, and
     * factories.
     */
    protected function bootModule(): void
    {
        $this->publishModuleConfigs();
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->loadModuleTranslations();
        $this->loadModuleJsonTranslations();
        $this->loadModuleFactories();
    }

    protected function registerModuleConfigs(): void
    {
        $configPath = $this->modulePath('config');

        if (! File::isDirectory($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $key = $file->getFilenameWithoutExtension();
            $this->mergeConfigFrom($file->getRealPath(), $key);
        }
    }

    protected function publishModuleConfigs(): void
    {
        $configPath = $this->modulePath('config');

        if (! File::isDirectory($configPath) || ! function_exists('config_path')) {
            return;
        }

        $publishes = [];

        foreach (File::files($configPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $publishes[$file->getRealPath()] = config_path($file->getFilename());
        }

        if ($publishes !== []) {
            $this->publishes($publishes, $this->modulePublishTag('config'));
        }
    }

    protected function loadModuleRoutes(): void
    {
        $routesPath = $this->modulePath('routes');

        if (! File::isDirectory($routesPath)) {
            return;
        }

        foreach (File::files($routesPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->loadRoutesFrom($file->getRealPath());
        }
    }

    protected function loadModuleMigrations(): void
    {
        $migrationsPath = $this->modulePath('database/migrations');

        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function loadModuleViews(): void
    {
        $viewsPath = $this->modulePath('resources/views');

        if (! File::isDirectory($viewsPath)) {
            return;
        }

        $namespace = $this->moduleViewNamespace();

        $this->loadViewsFrom($viewsPath, $namespace);

        if (function_exists('resource_path')) {
            $this->publishes([
                $viewsPath => resource_path('views/vendor/'.$namespace),
            ], $this->modulePublishTag('views'));
        }
    }

    protected function loadModuleTranslations(): void
    {
        $langPath = $this->modulePath('resources/lang');

        if (! File::isDirectory($langPath)) {
            return;
        }

        $namespace = $this->moduleViewNamespace();

        $this->loadTranslationsFrom($langPath, $namespace);

        if (function_exists('resource_path')) {
            $this->publishes([
                $langPath => resource_path('lang/vendor/'.$namespace),
            ], $this->modulePublishTag('lang'));
        }
    }

    protected function loadModuleJsonTranslations(): void
    {
        $langPath = $this->modulePath('resources/lang');

        if (File::isDirectory($langPath)) {
            $this->loadJsonTranslationsFrom($langPath);
        }
    }

    protected function loadModuleFactories(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $factoriesPath = $this->modulePath('database/factories');

        if (File::isDirectory($factoriesPath)) {
            $this->loadFactoriesFrom($factoriesPath);
        }
    }

    protected function registerModuleCommands(): void
    {
        $commands = $this->moduleCommands();

        if ($commands === [] || ! $this->app->runningInConsole()) {
            return;
        }

        $this->commands($commands);
    }

    /**
     * Publish the module's public assets to the Laravel application's public
     * directory.
     *
     * @param  string|null  $tag  Optional publish tag to group the assets under.
     * @param  string|null  $destination  Override the default publish destination.
     */
    protected function publishModuleAssets(?string $tag = null, ?string $destination = null): void
    {
        if (! function_exists('public_path')) {
            return;
        }

        $assetsPath = $this->modulePath('public');

        if (! File::isDirectory($assetsPath)) {
            return;
        }

        $target = $destination ?? public_path('vendor/'.$this->moduleViewNamespace());

        $this->publishes([
            $assetsPath => $target,
        ], $tag ?? $this->modulePublishTag('assets'));
    }

    /**
     * @return array<int, class-string<\Illuminate\Console\Command>>
     */
    protected function moduleCommands(): array
    {
        return [];
    }

    protected function moduleBasePath(): string
    {
        return dirname(__DIR__);
    }

    protected function moduleViewNamespace(): string
    {
        return 'module';
    }

    protected function modulePath(string $path = ''): string
    {
        $basePath = rtrim($this->moduleBasePath(), DIRECTORY_SEPARATOR);

        if ($path === '') {
            return $basePath;
        }

        return $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    protected function modulePublishTag(string $suffix): string
    {
        return $this->modulePublishTagPrefix().'-'.$suffix;
    }

    protected function modulePublishTagPrefix(): string
    {
        $namespace = Str::slug($this->moduleViewNamespace());

        if ($namespace === '') {
            $namespace = Str::slug(class_basename(static::class));
        }

        return 'glugox-module-'.$namespace;
    }
}
