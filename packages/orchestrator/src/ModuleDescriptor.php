<?php

namespace Glugox\Orchestrator;

use Illuminate\Contracts\Support\Arrayable;
use function is_dir;

/**
 * @phpstan-type ModuleDescriptorPayload array{
 *     id: string,
 *     name?: string|null,
 *     version?: string|null,
 *     installed?: bool,
 *     enabled?: bool,
 *     base_path?: string|null,
 *     paths?: array<string, mixed>,
 *     providers?: array<int|string, mixed>,
 *     capabilities?: array<int|string, mixed>,
 *     extra?: array<string, mixed>
 * }
 * @phpstan-type ModuleDescriptorArray array{
 *     id: string,
 *     name: string,
 *     version: string,
 *     installed: bool,
 *     enabled: bool,
 *     base_path: string,
 *     paths: array<string, mixed>,
 *     providers: array<int, string>,
 *     capabilities: array<int, string>,
 *     extra: array<string, mixed>
 * }
 */
class ModuleDescriptor implements Arrayable
{
    public const ATTRIBUTE_ID = 'id';
    public const ATTRIBUTE_NAME = 'name';
    public const ATTRIBUTE_VERSION = 'version';
    public const ATTRIBUTE_INSTALLED = 'installed';
    public const ATTRIBUTE_ENABLED = 'enabled';
    public const ATTRIBUTE_BASE_PATH = 'base_path';
    public const ATTRIBUTE_PATHS = 'paths';
    public const ATTRIBUTE_PROVIDERS = 'providers';
    public const ATTRIBUTE_CAPABILITIES = 'capabilities';
    public const ATTRIBUTE_EXTRA = 'extra';

    protected bool $installed;
    protected bool $enabled;

    /**
     * @param  array<string, mixed>  $paths
     * @param  array<int, string>  $providers
     * @param  array<int, string>  $capabilities
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        protected string $id,
        protected string $name,
        protected string $version,
        bool $installed,
        bool $enabled,
        protected string $basePath,
        protected array $paths = [],
        protected array $providers = [],
        protected array $capabilities = [],
        protected array $extra = []
    ) {
        $this->installed = $installed;
        $this->enabled = $enabled && $installed;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function basePathExists(): bool
    {
        $path = $this->basePath();

        return $path !== '' && is_dir($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * @return array<int, string>
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * @return array<int, string>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function extra(): array
    {
        return $this->extra;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isHealthy(): bool
    {
        return $this->healthStatus() === 'healthy';
    }

    public function healthStatus(): string
    {
        if (! $this->isInstalled()) {
            return 'not installed';
        }

        if (! $this->basePathExists()) {
            return 'missing files';
        }

        if (! $this->isEnabled()) {
            return 'disabled';
        }

        return 'healthy';
    }

    public function enable(): void
    {
        $this->installed = true;
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function uninstall(): void
    {
        $this->installed = false;
        $this->enabled = false;
    }

    public function markInstalled(bool $installed): void
    {
        $this->installed = $installed;
        if (! $installed) {
            $this->enabled = false;
        }
    }

    public function markEnabled(bool $enabled): void
    {
        if ($enabled) {
            $this->enable();

            return;
        }

        $this->disable();
    }

    public function path(?string $subPath = null): string
    {
        if ($subPath === null || $subPath === '') {
            return $this->basePath;
        }

        return rtrim($this->basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($subPath, DIRECTORY_SEPARATOR);
    }

    /**
     * @return ModuleDescriptorArray
     */
    public function toArray(): array
    {
        return [
            self::ATTRIBUTE_ID => $this->id,
            self::ATTRIBUTE_NAME => $this->name,
            self::ATTRIBUTE_VERSION => $this->version,
            self::ATTRIBUTE_INSTALLED => $this->installed,
            self::ATTRIBUTE_ENABLED => $this->enabled,
            self::ATTRIBUTE_BASE_PATH => $this->basePath,
            self::ATTRIBUTE_PATHS => $this->paths,
            self::ATTRIBUTE_PROVIDERS => $this->providers,
            self::ATTRIBUTE_CAPABILITIES => $this->capabilities,
            self::ATTRIBUTE_EXTRA => $this->extra,
        ];
    }

    /**
     * @param  ModuleDescriptorPayload  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes[self::ATTRIBUTE_ID],
            $attributes[self::ATTRIBUTE_NAME] ?? $attributes[self::ATTRIBUTE_ID],
            $attributes[self::ATTRIBUTE_VERSION] ?? '0.0.0',
            (bool) ($attributes[self::ATTRIBUTE_INSTALLED] ?? true),
            (bool) ($attributes[self::ATTRIBUTE_ENABLED] ?? ($attributes[self::ATTRIBUTE_INSTALLED] ?? true)),
            $attributes[self::ATTRIBUTE_BASE_PATH] ?? '',
            is_array($attributes[self::ATTRIBUTE_PATHS] ?? null) ? $attributes[self::ATTRIBUTE_PATHS] : [],
            self::stringArray($attributes[self::ATTRIBUTE_PROVIDERS] ?? []),
            self::stringArray($attributes[self::ATTRIBUTE_CAPABILITIES] ?? []),
            is_array($attributes[self::ATTRIBUTE_EXTRA] ?? null) ? $attributes[self::ATTRIBUTE_EXTRA] : []
        );
    }

    /**
     * @param  array<mixed>  $values
     * @return array<int, string>
     */
    protected static function stringArray(array $values): array
    {
        $strings = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $strings[] = $value;
            }
        }

        return $strings;
    }
}
