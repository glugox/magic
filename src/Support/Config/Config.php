<?php

declare(strict_types=1);

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Readers\SchemaReader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

class Config
{
    /**
     * @var App Application configuration for general settings like name, and version.
     */
    public App $app;

    /**
     * Entities configured in the application.
     * Each entity represents a model or resource in the application.
     * The entities are defined in the JSON configuration file.
     *
     * @var Entity[]
     */
    public array $entities = [];

    /**
     * Config related to development environment.
     */
    public Dev $dev;

    /**
     * @param  SchemaReader  $schemaReader  The schema reader instance to load SDL.
     */
    public function __construct(
        protected SchemaReader $schemaReader,
    ) {}

    /**
     * Convert the configuration from JSON string to Config object.
     *
     * @throws JsonException
     */
    public static function fromJson(string|array $json): self
    {
        // Decode JSON if it's a string
        $data = is_string($json) ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : $json;

        $entities = [];
        foreach ($data['entities'] ?? [] as $entityData) {
            $entities[] = Entity::fromConfig($entityData);
        }

        $app = App::fromConfig($data['app'] ?? []);
        $dev = isset($data['dev']) ? Dev::fromJson($data['dev']) : null;

        $config = app(self::class);
        $config->app = $app;
        $config->entities = $entities;
        $config->dev = $dev ?? new Dev();
        $config->processEntities();

        return $config;
    }

    /**
     * Convert the configuration from json file path to Config object.
     *
     * @throws JsonException
     */
    public static function fromJsonFile(string $filePath): self
    {
        $filePath = self::ensureBasePath($filePath);
        if (! file_exists($filePath)) {
            throw new RuntimeException("Configuration file not found: {$filePath}");
        }
        $json = file_get_contents($filePath);
        if ($json === false) {
            throw new RuntimeException("Failed to read configuration file: {$filePath}");
        }

        return static::fromJson($json);
    }

    /**
     * Ensures we have only one base path
     * in the beginning of the path.
     */
    public static function ensureBasePath(string $path): string
    {
        $base = base_path();

        // Already absolute inside base
        if (str_starts_with($path, $base) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * Initialize a new Config instance.
     */
    public function init(): self
    {
        $this->processEntities();

        return $this;
    }

    /**
     * Load JSON into typed entities
     */
    public function loadJson(string $json): void
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $entities = [];
        foreach ($data['entities'] ?? [] as $entityData) {
            $entities[] = Entity::fromConfig($entityData);
        }

        $app = App::fromConfig($data['app'] ?? []);
        $dev = isset($data['dev']) ? Dev::fromJson($data['dev']) : null;

        $this->app = $app;
        $this->entities = $entities;
        $this->dev = $dev ?? new Dev();
    }

    /**
     * Load SDL into typed entities
     */
    public function loadGraphQL(string $sdl): self
    {
        $this->schemaReader->load($sdl);

        $this->app = $this->schemaReader->getApp();
        $this->entities = $this->schemaReader->getEntities();
        $this->dev = new Dev();

        return $this;
    }

    /**
     * Check if the configuration is valid.
     */
    public function isValid(): bool
    {
        // TODO: Implement validation logic
        return true;
    }

    /**
     * Get config value by key.
     * This is , for now, only a wrapper around config('magic.xxx')
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return config("magic.{$key}", $default);
    }

    /**
     * @return Config Applies overrides to the configuration array.
     *
     * Applies overrides to the configuration array.
     * This is usually used to modify specific configuration values from the command line or other sources.
     *
     * @throws ReflectionException
     */
    public function applyOverrides(array $overrides): self
    {
        Log::channel('magic')->info('Applying overrides to config: '.json_encode($overrides));

        // start from root config
        $modified = $this;

        foreach ($overrides as $override) {
            [$key, $value] = explode('=', $override, 2);
            $keys = explode('.', $key);

            $current = &$modified;

            foreach ($keys as $index => $k) {
                if (! property_exists($current, $k)) {
                    throw new RuntimeException("Property '{$k}' does not exist in ".get_class($current));
                }

                // If this is the last key, assign the value with proper type
                if ($index === array_key_last($keys)) {
                    $reflection = new ReflectionProperty($current, $k);
                    $reflectionType = $reflection->getType();
                    $type = 'mixed';
                    if($reflectionType !== null ) {
                        // @phpstan-ignore-next-line
                        $type = $reflectionType->getName();
                    }
                    $currentShortClass = new ReflectionClass($current)->getShortName();

                    // If the property is a nested config object
                    if (class_exists($type) && is_subclass_of($type, self::class)) {
                        // @phpstan-ignore-next-line
                        $current->$k = new $type((array) $value);
                        Log::channel('magic')->info("Setting {$currentShortClass} -> {$k} to value: ".json_encode($current->$k));
                    } else {
                        $current->$k = self::castType($type, $value);
                        Log::channel('magic')->info("Setting {$currentShortClass} -> {$k} to value: ".json_encode($current->$k));
                    }
                } else {
                    $current = &$current->$k; // traverse deeper
                }
            }
        }

        Log::channel('magic')->info('Config overrides applied successfully.');

        return $modified;
    }

    /**
     * Print debug information about the configuration.
     * Loop all properties and their values.
     * Display them in Log channel 'magic'.
     */
    public function printDebugInfo()
    {
        Log::channel('magic')->info('Configuration Debug Info:');
        Log::channel('magic')->info("App Name: {$this->app->name}");
        Log::channel('magic')->info('Entities Count: '.count($this->entities));
        Log::channel('magic')->info('Development Seed Enabled: '.($this->seedEnabled ? 'true' : 'false'));
        Log::channel('magic')->info("Development Seed Count: {$this->seedCount}");

        foreach ($this->entities as $entity) {
            Log::channel('magic')->info("Entity: {$entity->getName()}, Table: {$entity->getTableName()}");
        }
    }

    /**
     * Converts the configuration back to json string.
     */
    public function toJson(): string
    {
        $data = [
            'app' => $this->app,
            'entities' => array_map(fn ($entity) => json_decode($entity->toJson(), true), $this->entities),
            'dev' => $this->dev,
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Writes the content json to file path provided.
     */
    public function saveToFile(string $tmpFilePath)
    {
        File::ensureDirectoryExists(dirname($tmpFilePath));
        file_put_contents($tmpFilePath, $this->toJson());
    }

    /**
     * Get entity by its name.
     */
    public function getEntityByName(string $relatedEntityName): ?Entity
    {
        return array_find($this->entities, fn ($entity) => $entity->getName() === $relatedEntityName);
    }

    /**
     * Check if any entity has images enabled in settings.
     */
    public function anyEntityHasImages(): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity->settings->hasImages) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generic type caster
     */
    protected static function castType(string $type, mixed $value): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
            default => $value,
        };
    }

    /**
     * Process entities to resolve relations and other settings.
     */
    private function processEntities()
    {
        foreach ($this->entities as $entity) {
            $entity->processRelations($this);
        }
    }
}
