<?php

namespace Glugox\Orchestrator\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * This class handles the configuration settings for the Orchestrator.
 * It resolves paths and normalizes configuration options.
 * It provides methods to access these settings throughout the application.
 */
class OrchestratorConfig
{
    protected string $basePath;

    protected string $modulesPath;

    protected string $manifestPath;

    protected string $installedJsonPath;

    /**
     * @var array<int, string>
     */
    protected array $moduleJsonPatterns;

    /**
     * @var string
     */
    protected string $moduleSpecsPath;

    protected bool $autoInstall;

    protected bool $autoEnable;

    protected $defaultVendor;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $this->basePath = $this->resolveBasePath($config['base_path'] ?? null);
        $this->modulesPath = $this->absolutePath($config['modules_default_path'] ?? 'modules');
        $this->manifestPath = $this->absolutePath($config['manifest_path'] ?? 'bootstrap/cache/modules.php');
        $this->installedJsonPath = $this->absolutePath($config['installed_path'] ?? 'vendor/composer/installed.json');
        $this->moduleJsonPatterns = $this->normalisePatterns($config['module_json_paths'] ?? []);
        $this->moduleSpecsPath = $this->absolutePath($config['module_specs_path'] ?? 'specs/modules');
        $this->autoInstall = (bool) ($config['auto_install'] ?? true);
        $this->autoEnable = (bool) ($config['auto_enable'] ?? true);
        $this->defaultVendor = $config['default_vendor'] ?? null;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function modulesPath(): string
    {
        return $this->modulesPath;
    }

    public function defaultVendor()
    {
        return $this->defaultVendor;
    }

    public function manifestPath(): string
    {
        return $this->manifestPath;
    }

    public function installedJsonPath(): string
    {
        return $this->installedJsonPath;
    }

    /**
     * @return array<int, string>
     */
    public function moduleJsonPatterns(): array
    {
        return $this->moduleJsonPatterns;
    }

    /**
     * @return bool
     */
    public function moduleSpecsPath(): string
    {
        return $this->moduleSpecsPath;
    }

    public function autoInstall(): bool
    {
        return $this->autoInstall;
    }

    public function autoEnable(): bool
    {
        return $this->autoEnable;
    }

    public function absolutePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $this->canonicalizePath($path);
        }

        return $this->canonicalizePath($this->basePath.DIRECTORY_SEPARATOR.$path);
    }

    public function canonicalizePath(string $path): string
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $segments = explode(DIRECTORY_SEPARATOR, $path);
        $stack = [];
        $prefix = '';

        if ($path !== '' && ($path[0] ?? '') === DIRECTORY_SEPARATOR) {
            $prefix = DIRECTORY_SEPARATOR;
        } elseif (isset($segments[0]) && Str::contains($segments[0], ':')) {
            $prefix = array_shift($segments).DIRECTORY_SEPARATOR;
        }

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($stack);
                continue;
            }

            $stack[] = $segment;
        }

        $resolved = implode(DIRECTORY_SEPARATOR, $stack);

        return $prefix.$resolved;
    }

    protected function resolveBasePath(mixed $configured): string
    {
        if (is_string($configured) && $configured !== '') {
            return $this->canonicalizePath($configured);
        }

        if (function_exists('base_path')) {
            return base_path();
        }

        $cwd = getcwd();

        if (! is_string($cwd) || $cwd === '') {
            throw new RuntimeException('Unable to determine application base path for the orchestrator.');
        }

        return $cwd;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return Str::startsWith($path, ['/', '\\']) || (strlen($path) > 1 && $path[1] === ':');
    }

    /**
     * @param  mixed  $patterns
     * @return array<int, string>
     */
    protected function normalisePatterns(mixed $patterns): array
    {
        $patterns = Arr::wrap($patterns);
        $result = [];

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $result[] = $pattern;
        }

        if ($result === []) {
            $result = [
                'vendor/*/*/module.json',
                'modules/*/module.json',
                'packages/*/*/module.json',
            ];
        }

        return array_values(array_unique($result));
    }
}
