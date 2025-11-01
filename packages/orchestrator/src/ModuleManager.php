<?php

namespace Glugox\Orchestrator;

use Glugox\Orchestrator\Services\ModuleInstaller;
use Glugox\Orchestrator\Services\ModuleRegistry;
use Glugox\Orchestrator\Support\ModuleDiscovery;
use Glugox\Orchestrator\Support\OrchestratorConfig;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleManager
{
    protected ModuleRegistry $registry;

    protected OrchestratorConfig $config;

    protected ?Application $application;

    /**
     * @var array<string, bool>
     */
    protected array $registeredProviders = [];

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(?array $config = null, ?ModuleRegistry $registry = null, ?Application $application = null)
    {
        if ($registry) {
            $this->registry = $registry;
            $this->config = $registry->config();
        } else {
            $resolved = is_array($config) ? $config : (config('orchestrator') ?? []);
            if (! is_array($resolved)) {
                $resolved = [];
            }

            $this->config = new OrchestratorConfig($resolved);
            $manifest = new ModuleManifest($this->config->manifestPath());
            $discovery = new ModuleDiscovery($this->config);
            $this->registry = new ModuleRegistry($this->config, $discovery, $manifest);
        }

        $this->application = $application ?? $this->resolveApplication();
    }

    public function setApplication(?Application $application): void
    {
        $this->application = $application;
    }

    public function basePath(): string
    {
        return $this->config->basePath();
    }

    public function modulesPath(): string
    {
        return $this->config->modulesPath();
    }

    public function manifestPath(): string
    {
        return $this->registry->manifest()->path();
    }

    public function isCached(): bool
    {
        return $this->registry->manifest()->exists();
    }

    /**
     * @return Collection<int, ModuleDescriptor>
     */
    public function all(): Collection
    {
        return $this->registry->all();
    }

    /**
     * @return Collection<int, SpecDescriptor>
     */
    public function specs(): Collection
    {
        return $this->registry->specs();
    }

    /**
     * @return Collection<int, ModuleDescriptor>
     */
    public function installed(): Collection
    {
        return $this->all()->filter(fn (ModuleDescriptor $module) => $module->isInstalled())->values();
    }

    /**
     * @return Collection<int, ModuleDescriptor>
     */
    public function enabledModules(): Collection
    {
        return $this->all()->filter(fn (ModuleDescriptor $module) => $module->isEnabled())->values();
    }

    public function module(string $id): ModuleDescriptor
    {
        return $this->registry->get($id);
    }

    public function path(string $id, ?string $subPath = null): string
    {
        return $this->module($id)->path($subPath);
    }

    public function isEnabled(string $id): bool
    {
        return $this->module($id)->isEnabled();
    }

    public function enabled(string $id): bool
    {
        return $this->isEnabled($id);
    }

    public function install(string $id): void
    {
        $module = $this->registry->get($id);

        $installer = app(ModuleInstaller::class);

        // Always treat as composer package
        $installer->install(
            $module->id(),   // e.g. "glugox/crm"
            $module->path()            // local path to /modules/Glugox/Crm
        );

        $this->registry->setInstalled($id, true);

        if (! $module->isEnabled() && $this->config->autoEnable()) {
            $module->markEnabled(true);
        }

        $this->registry->persist();
        $this->registerModuleProviders($module);
    }

    public function uninstall(string $id, bool $dropData = false): void
    {
        $module = $this->registry->setInstalled($id, false);
        $module->uninstall();
        $this->registry->persist();
    }

    public function enable(string $id): void
    {
        $module = $this->registry->setEnabled($id, true);
        $this->registry->persist();
        $this->registerModuleProviders($module);
    }

    public function disable(string $id): void
    {
        $this->registry->setEnabled($id, false);
        $this->registry->persist();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function migrate(string $id, array $options = []): int
    {
        $module = $this->module($id);
        $paths = $module->paths();
        $migrations = $paths['migrations'] ?? null;

        if (! $migrations || ! class_exists('Illuminate\\Support\\Facades\\Artisan') || ! function_exists('app')) {
            return 0;
        }

        $migrationPath = is_array($migrations) ? ($migrations[0] ?? null) : $migrations;

        if (! is_string($migrationPath) || $migrationPath === '') {
            return 0;
        }

        $arguments = array_merge(['--path' => $module->path($migrationPath)], $options);

        /** @var \Illuminate\Support\Facades\Artisan $artisan */
        $artisan = app('artisan');

        return $artisan->call('migrate', $arguments);
    }

    public function seed(string $id, ?string $class = null): void
    {
        $module = $this->module($id);
        $paths = $module->paths();
        $seeders = $paths['seeds'] ?? null;

        if (! $seeders || ! class_exists('Illuminate\\Support\\Facades\\Artisan') || ! function_exists('app')) {
            return;
        }

        $arguments = [];

        if ($class) {
            $arguments['--class'] = $class;
        }

        if (! $class && is_string($seeders) && $seeders !== '') {
            $arguments['--class'] = $this->resolveDefaultSeeder($seeders, $module->extra());
        }

        /** @var \Illuminate\Support\Facades\Artisan $artisan */
        $artisan = app('artisan');
        $artisan->call('db:seed', $arguments);
    }

    /**
     * @return array<string, ModuleDescriptor>
     */
    public function discover(bool $writeManifest = true): array
    {
        return $this->registry->refresh($writeManifest);
    }

    /**
     * @return array<string, ModuleDescriptor>
     */
    public function reload(bool $writeManifest = true): array
    {
        return $this->discover($writeManifest);
    }

    public function cache(): void
    {
        $this->registry->persist();
    }

    public function clearCache(): void
    {
        $this->registry->clear();
    }

    public function registerEnabledModules(?Application $application = null): void
    {
        if ($application) {
            $this->application = $application;
        }

        foreach ($this->enabledModules() as $module) {
            $this->registerModuleProviders($module);
        }
    }

    protected function registerModuleProviders(ModuleDescriptor $module): void
    {
        if (! $module->isEnabled()) {
            return;
        }

        $app = $this->application;

        if (! $app) {
            return;
        }

        foreach ($module->providers() as $provider) {
            if (! is_string($provider) || $provider === '' || isset($this->registeredProviders[$provider])) {
                continue;
            }

            if (! class_exists($provider)) {
                $this->warn('Module provider class is missing.', [
                    'module' => $module->id(),
                    'provider' => $provider,
                ]);

                continue;
            }

            $app->register($provider);
            $this->registeredProviders[$provider] = true;
        }
    }

    protected function warn(string $message, array $context = []): void
    {
        if (class_exists(Log::class)) {
            try {
                Log::warning($message, $context);

                return;
            } catch (Throwable $exception) {
                // Fall back to error_log below.
            }
        }

        $suffix = empty($context) ? '' : ' '.json_encode($context);
        error_log($message.$suffix);
    }

    protected function resolveApplication(): ?Application
    {
        if (! function_exists('app')) {
            return null;
        }

        $application = app();

        return $application instanceof Application ? $application : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function resolveDefaultSeeder(string $seeders, array $extra): string
    {
        $seeders = trim($seeders, '\\/');

        if (isset($extra['default_seeder']) && is_string($extra['default_seeder']) && $extra['default_seeder'] !== '') {
            return $extra['default_seeder'];
        }

        $segments = explode('/', $seeders);
        $name = Arr::last($segments) ?: 'DatabaseSeeder';
        $name = str_replace(['-', '_'], ' ', $name);
        $name = str_replace(' ', '', ucwords($name));

        if (! str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        return $name;
    }
}
