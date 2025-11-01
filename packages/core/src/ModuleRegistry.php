<?php

declare(strict_types=1);

namespace Glugox\Core;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;

/**
 * Coordinates discovery and registration of module resources defined in the core configuration.
 */
class ModuleRegistry
{
    private bool $routesRegistered = false;

    public function __construct(
        private readonly Repository $config,
        private readonly Filesystem $files,
    ) {
    }

    public function registerRoutes(): void
    {
        if ($this->routesRegistered) {
            return;
        }

        foreach ($this->modules() as $module) {
            foreach ($module['routes'] as $routeFile) {
                $fullPath = $module['path'].DIRECTORY_SEPARATOR.ltrim($routeFile, DIRECTORY_SEPARATOR);

                if ($this->files->exists($fullPath)) {
                    require_once $fullPath;
                }
            }
        }

        $this->routesRegistered = true;
    }

    public function refresh(): void
    {
        $this->routesRegistered = false;
        $this->registerRoutes();
    }

    /**
     * @return array<int, array{path: string, routes: array<int, string>}> 
     */
    private function modules(): array
    {
        $modules = $this->config->get('core.modules', []);

        if (! is_array($modules)) {
            return [];
        }

        $normalized = [];

        foreach ($modules as $module) {
            if (! is_array($module)) {
                continue;
            }

            $path = $module['path'] ?? null;

            if (! is_string($path) || $path === '') {
                continue;
            }

            $routes = $this->normalizeRoutes($module['routes'] ?? null);

            if ($routes === []) {
                continue;
            }

            $normalized[] = [
                'path' => rtrim($path, DIRECTORY_SEPARATOR),
                'routes' => $routes,
            ];
        }

        return $normalized;
    }

    /**
     * @return string[]
     */
    private function normalizeRoutes(mixed $routes): array
    {
        if (is_string($routes) && $routes !== '') {
            return [$routes];
        }

        if (! is_array($routes)) {
            return [];
        }

        $normalized = [];

        foreach ($routes as $value) {
            if (is_string($value) && $value !== '') {
                $normalized[] = $value;

                continue;
            }

            if (is_array($value) && isset($value['path']) && is_string($value['path']) && $value['path'] !== '') {
                $normalized[] = $value['path'];
            }
        }

        return array_values($normalized);
    }
}
