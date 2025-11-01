<?php

namespace Glugox\Orchestrator\Support;

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\SpecDescriptor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ModuleDiscovery
{
    public function __construct(protected OrchestratorConfig $config)
    {
    }

    /**
     * @return array<string, ModuleDescriptor>
     */
    public function discover(): array
    {
        $modules = [];

        foreach ($this->discoverFromComposer() as $module) {
            $modules[$module->id()] = $module;
        }

        foreach ($this->discoverFromModuleJson() as $module) {
            if (isset($modules[$module->id()])) {
                continue;
            }

            $modules[$module->id()] = $module;
        }

        ksort($modules);

        return $modules;
    }

    /**
     * Discover from specs files.
     *
     * @return array<int, SpecDescriptor>
     */
    public function discoverSpecs(): array
    {
        $modules = [];
        $files = glob($this->config->moduleSpecsPath().'/*.json') ?: [];
        foreach ($files as $file) {
            $json = json_decode((string) file_get_contents($file), true);

            if (! is_array($json)) {
                $this->warn('Skipping invalid module spec: unable to decode JSON.', ['file' => $file]);

                continue;
            }

            $jsonApp = $json['app'] ?? null;

            // Check if it is disabled
            if (is_array($jsonApp) && isset($jsonApp['disabled']) && $jsonApp['disabled'] === true) {
                continue;
            }

            if (! is_array($jsonApp)) {
                $this->warn('Skipping invalid module spec: missing app section.', ['file' => $file]);

                continue;
            }

            $name = $jsonApp['name'] ?? null;

            if (! is_string($name) || $name === '') {
                $this->warn('Skipping invalid module spec: missing or empty app.name.', ['file' => $file]);

                continue;
            }

            $vendor = $jsonApp['vendor'] ?? $this->config->defaultVendor();

            if (! is_string($vendor) || $vendor === '') {
                $this->warn('Skipping module spec because vendor could not be determined.', ['file' => $file]);

                continue;
            }

            $segments = explode('/', $name);
            $moduleShortName = $segments[1] ?? end($segments) ?: $name;

            if (! is_string($moduleShortName) || $moduleShortName === '') {
                $this->warn('Skipping invalid module spec: module name could not be determined.', ['file' => $file]);

                continue;
            }

            $moduleShortName = strtolower($moduleShortName);
            $vendorPrefix = strtolower($vendor);
            $id = $vendorPrefix . '/' . $moduleShortName;

            $modules[] = new SpecDescriptor(
                $id,
                $name,
                $vendor . '\\' . Str::studly($moduleShortName),
                $file,
            );
        }

        return $modules;
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

    /**
     * @return array<int, ModuleDescriptor>
     */
    protected function discoverFromComposer(): array
    {
        $packages = $this->readComposerPackages();
        $modules = [];

        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }

            $meta = Arr::get($package, 'extra.glugox-module', []);

            if (($package['type'] ?? null) !== 'laravel-module' && empty($meta)) {
                continue;
            }

            $id = $meta['id'] ?? $package['name'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            $modules[] = new ModuleDescriptor(
                $id,
                $meta['name'] ?? ($package['name'] ?? $id),
                $meta['version'] ?? ($package['version'] ?? $package['pretty_version'] ?? '0.0.0'),
                $this->config->autoInstall(),
                $this->config->autoEnable(),
                $this->resolveInstallPath($package),
                $this->normalisePaths($meta),
                $this->normaliseProviders(Arr::get($package, 'extra.laravel.providers', [])),
                $this->normaliseArray($meta['capabilities'] ?? []),
                is_array($meta) ? $meta : []
            );
        }

        return $modules;
    }

    /**
     * @return array<int, ModuleDescriptor>
     */
    protected function discoverFromModuleJson(): array
    {
        $modules = [];

        foreach ($this->config->moduleJsonPatterns() as $pattern) {
            $files = glob($this->config->absolutePath($pattern)) ?: [];

            foreach ($files as $file) {
                $json = json_decode((string) file_get_contents($file), true);

                if (! is_array($json) || ! isset($json['id'])) {
                    continue;
                }

                $modules[] = new ModuleDescriptor(
                    $json['id'],
                    $json['name'] ?? $json['id'],
                    $json['version'] ?? '0.0.0',
                    $this->config->autoInstall(),
                    $this->config->autoEnable(),
                    $this->config->canonicalizePath(dirname($file)),
                    $this->normalisePaths($json),
                    $this->normaliseArray( $json['providers'] ?? []),
                    $this->normaliseArray($json['capabilities'] ?? []),
                    $json
                );
            }
        }

        return $modules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readComposerPackages(): array
    {
        $installedPath = $this->config->installedJsonPath();

        if (! is_file($installedPath)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($installedPath), true);

        if (! is_array($json)) {
            return [];
        }

        if (isset($json['packages']) && is_array($json['packages'])) {
            return $json['packages'];
        }

        if (Arr::isAssoc($json) && isset($json['name'])) {
            return [$json];
        }

        $packages = [];

        foreach ($json as $entry) {
            if (isset($entry['packages']) && is_array($entry['packages'])) {
                $packages = array_merge($packages, $entry['packages']);
            } elseif (is_array($entry) && isset($entry['name'])) {
                $packages[] = $entry;
            }
        }

        return $packages;
    }

    /**
     * @param  array<string, mixed>  $package
     */
    protected function resolveInstallPath(array $package): string
    {
        $path = $package['install_path'] ?? $package['install-path'] ?? null;

        if (is_string($path) && $path !== '') {
            $composerDir = dirname($this->config->installedJsonPath());
            $candidate = $this->config->canonicalizePath($composerDir.DIRECTORY_SEPARATOR.$path);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        $name = $package['name'] ?? null;

        if (is_string($name) && $name !== '') {
            return $this->config->absolutePath('vendor/'.$name);
        }

        return $this->config->basePath();
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int, string>
     */
    protected function normaliseArray(array $values): array
    {
        $result = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * @param  mixed  $providers
     * @return array<int, string>
     */
    protected function normaliseProviders(mixed $providers): array
    {
        if (is_string($providers)) {
            return [$providers];
        }

        if (! is_array($providers)) {
            return [];
        }

        return $this->normaliseArray($providers);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function normalisePaths(array $meta): array
    {
        $paths = [];
        $keys = ['routes', 'migrations', 'seeds', 'views', 'translations'];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $meta)) {
                continue;
            }

            $value = $meta[$key];

            if (is_array($value)) {
                $paths[$key] = array_values(array_filter($value, fn ($path) => is_string($path) && $path !== ''));
            } elseif (is_string($value) && $value !== '') {
                $paths[$key] = $value;
            }
        }

        return $paths;
    }
}
