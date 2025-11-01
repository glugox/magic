<?php

namespace Glugox\Orchestrator;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @phpstan-type SpecDescriptorArray array{
 *     id: string,
 *     name: string,
 *     namespace: string,
 *     config_path: string,
 *     is_enabled: bool
 * }
 */
class SpecDescriptor implements Arrayable
{
    public const ATTRIBUTE_ID = 'id';
    public const ATTRIBUTE_NAME = 'name';
    public const ATTRIBUTE_NAMESPACE = 'namespace';
    public const ATTRIBUTE_CONFIG_PATH = 'config_path';
    public const ATTRIBUTE_IS_ENABLED = 'is_enabled';

    public function __construct(
        protected string $id,
        protected string $name,
        protected string $namespace,
        protected string $configPath,
        protected bool $isEnabled = true,
    ) {

    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function configPath(): string
    {
        return $this->configPath;
    }

    /**
     * @return SpecDescriptorArray
     */
    public function toArray(): array
    {
        return [
            self::ATTRIBUTE_ID => $this->id,
            self::ATTRIBUTE_NAME => $this->name,
            self::ATTRIBUTE_NAMESPACE => $this->namespace,
            self::ATTRIBUTE_CONFIG_PATH => $this->configPath,
            self::ATTRIBUTE_IS_ENABLED => $this->isEnabled,
        ];
    }

    public function toString(): string
    {
        return "{$this->name} ({$this->id}), Namespace: {$this->namespace}, Config: {$this->configPath}";
    }
}
