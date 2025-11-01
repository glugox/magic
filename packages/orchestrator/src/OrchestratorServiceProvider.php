<?php

namespace Glugox\Orchestrator;

use Glugox\Orchestrator\Commands\BuildModulesCommand;
use Glugox\Orchestrator\Commands\DisableModuleCommand;
use Glugox\Orchestrator\Commands\DoctorCommand;
use Glugox\Orchestrator\Commands\EnableModuleCommand;
use Glugox\Orchestrator\Commands\InstallModuleCommand;
use Glugox\Orchestrator\Commands\ListModulesCommand;
use Glugox\Orchestrator\Commands\ReloadModulesCommand;
use Glugox\Orchestrator\Services\ModuleRegistry;
use Glugox\Orchestrator\Support\DevRouteRegistrar;
use Glugox\Orchestrator\Support\ModuleDiscovery;
use Glugox\Orchestrator\Support\OrchestratorConfig;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class OrchestratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/orchestrator.php', 'orchestrator');

        $this->app->singleton(OrchestratorConfig::class, function ($app) {
            $config = $app['config']['orchestrator'] ?? [];

            return new OrchestratorConfig(is_array($config) ? $config : []);
        });

        $this->app->singleton(ModuleManifest::class, function ($app) {
            return new ModuleManifest($app->make(OrchestratorConfig::class)->manifestPath());
        });

        $this->app->singleton(ModuleDiscovery::class, function ($app) {
            return new ModuleDiscovery($app->make(OrchestratorConfig::class));
        });

        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry(
                $app->make(OrchestratorConfig::class),
                $app->make(ModuleDiscovery::class),
                $app->make(ModuleManifest::class)
            );
        });

        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager(null, $app->make(ModuleRegistry::class), $app);
        });

        $this->app->alias(ModuleManager::class, 'modules');
        $this->app->alias(ModuleManager::class, 'orchestrator');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/orchestrator.php' => $this->configPath('orchestrator.php'),
        ], 'orchestrator-config');

        $this->publishes([
            __DIR__.'/../config/orchestrator.php' => $this->configPath('orchestrator.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListModulesCommand::class,
                EnableModuleCommand::class,
                DisableModuleCommand::class,
                ReloadModulesCommand::class,
                BuildModulesCommand::class,
                InstallModuleCommand::class,
                DoctorCommand::class
            ]);
        }

        $this->registerDevRoutes();

        $this->app->booted(function (Application $app): void {
            $this->registerEnabledModulesSafely($app);
        });
    }

    protected function registerDevRoutes(): void
    {
        $config = $this->app['config']->get('orchestrator.dev_tools', []);

        if (! $this->shouldRegisterDevRoutes($config)) {
            return;
        }

        (new DevRouteRegistrar($this->app, $config))->register();
    }

    /**
     * Determine whether the dev routes should be registered for the current request cycle.
     */
    protected function shouldRegisterDevRoutes(mixed $config): bool
    {
        if (! is_array($config)) {
            return false;
        }

        $enabled = $config['enabled'] ?? null;

        if ($enabled === null) {
            return (bool) $this->app['config']->get('app.debug', false);
        }

        if (is_bool($enabled)) {
            return $enabled;
        }

        if (is_string($enabled)) {
            $normalized = strtolower($enabled);

            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
        }

        return (bool) $enabled;
    }

    protected function configPath(string $path): string
    {
        if (method_exists($this->app, 'configPath')) {
            return $this->app->configPath($path);
        }

        if (function_exists('config_path')) {
            return config_path($path);
        }

        return $this->app->basePath('config/'.$path);
    }

    /**
     * Safely register enabled modules once the application has fully booted.
     */
    protected function registerEnabledModulesSafely(Application $app): void
    {
        try {
            $manager = $app->make(ModuleManager::class);
            $manager->setApplication($app);
            $manager->registerEnabledModules($app);
        } catch (Throwable $exception) {
            $this->reportModuleRegistrationFailure($exception);
        }
    }

    protected function reportModuleRegistrationFailure(Throwable $exception): void
    {
        $message = 'Failed to register enabled orchestrator modules. '.$exception->getMessage();

        try {
            Log::error($message, [
                'exception' => $exception,
            ]);
        } catch (Throwable) {
            // Ignore logging failures and fall back to error_log below.
        }

        error_log($message.' in '.$exception->getFile().':'.$exception->getLine());
    }
}
