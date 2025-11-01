<?php

namespace Glugox\Orchestrator\Support;

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\ModuleManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class DevRouteRegistrar
{
    public function __construct(
        protected Application $app,
        protected array $config = []
    ) {
    }

    public function register(): void
    {
        if (! $this->app->bound('router')) {
            return;
        }

        $middleware = Arr::wrap($this->config['middleware'] ?? []);
        $prefix = trim((string) ($this->config['prefix'] ?? 'dev/orchestrator'), '/');
        $domain = $this->normaliseDomain($this->config['domain'] ?? null);

        $groupAttributes = array_filter([
            'middleware' => $middleware,
            'prefix' => $prefix === '' ? null : $prefix,
            'domain' => $domain,
        ], static function ($value) {
            if ($value === null) {
                return false;
            }

            if (is_array($value)) {
                return $value !== [];
            }

            return true;
        });

        Route::group($groupAttributes, function (): void {
            Route::get('/', function (ModuleManager $manager) {
                $modules = $manager->all()->map(function (ModuleDescriptor $module) {
                    return $this->transformModule($module);
                });

                $installed = $manager->installed()->count();
                $enabled = $manager->enabledModules()->count();

                $specs = $manager->specs()->map(function ($spec) {
                    return $this->transformSpec($spec);
                });

                return response()->json([
                    'summary' => [
                        'total_modules' => $modules->count(),
                        'installed_modules' => $installed,
                        'enabled_modules' => $enabled,
                        'total_specs' => $specs->count(),
                    ],
                    'modules' => $modules->values()->all(),
                    'specs' => $specs->values()->all(),
                ]);
            })->name('orchestrator.dev.index');

            Route::get('/modules', function (ModuleManager $manager) {
                $modules = $manager->all()->map(function (ModuleDescriptor $module) {
                    return $this->transformModule($module);
                })->values()->all();

                return response()->json([
                    'data' => $modules,
                ]);
            })->name('orchestrator.dev.modules');

            Route::get('/modules/{module}', function (ModuleManager $manager, string $moduleId) {
                try {
                    $module = $manager->module($moduleId);
                } catch (InvalidArgumentException $exception) {
                    return response()->json([
                        'message' => $exception->getMessage(),
                    ], 404);
                }

                return response()->json($this->transformModule($module));
            })->where('module', '.+')
                ->name('orchestrator.dev.modules.show');
        });
    }

    protected function transformModule(ModuleDescriptor $module): array
    {
        return array_merge($module->toArray(), [
            'health' => [
                'status' => $module->healthStatus(),
                'healthy' => $module->isHealthy(),
                'base_path_exists' => $module->basePathExists(),
            ],
            'provider_diagnostics' => $this->describeProviders($module),
        ]);
    }

    protected function transformSpec(mixed $spec): array
    {
        if ($spec instanceof Arrayable) {
            return $spec->toArray();
        }

        if ($spec instanceof Collection) {
            return $spec->toArray();
        }

        if (is_array($spec)) {
            return $spec;
        }

        return ['value' => $spec];
    }

    protected function normaliseDomain(mixed $domain): ?string
    {
        if (! is_string($domain)) {
            return null;
        }

        $domain = trim($domain);

        return $domain === '' ? null : $domain;
    }

    /**
     * @return array<int, array{class: string, exists: bool, loaded: bool, path: string|null}>
     */
    protected function describeProviders(ModuleDescriptor $module): array
    {
        $providers = [];

        foreach ($module->providers() as $provider) {
            if (! is_string($provider) || $provider === '') {
                continue;
            }

            $providers[] = $this->describeProvider($provider);
        }

        return $providers;
    }

    /**
     * @return array{class: string, exists: bool, loaded: bool, path: string|null}
     */
    protected function describeProvider(string $provider): array
    {
        $loaded = class_exists($provider, false);
        $exists = class_exists($provider);
        $path = null;

        if ($exists) {
            try {
                $reflection = new ReflectionClass($provider);
                $path = $reflection->getFileName() ?: null;
            } catch (ReflectionException) {
                $exists = false;
                $path = null;
            }
        }

        return [
            'class' => $provider,
            'exists' => $exists,
            'loaded' => $loaded,
            'path' => $path,
        ];
    }
}
