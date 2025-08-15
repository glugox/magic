<?php

declare(strict_types=1);

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Facades\Log;

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
     * @param  Entity[]  $entities
     */
    public function __construct(App $app, array $entities, ?Dev $dev = null)
    {
        $this->entities = $entities;
        $this->app = $app;
        $this->dev = $dev ?? new Dev;
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
     * Convert the configuration from JSON string to Config object.
     *
     * @throws \JsonException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $entities = [];
        foreach ($data['entities'] ?? [] as $entityData) {
            $entities[] = Entity::fromConfig($entityData);
        }

        $app = App::fromConfig($data['app'] ?? []);
        $dev = isset($data['dev']) ? Dev::fromJson($data['dev']) : null;

        return new self($app, $entities, $dev);
    }

    /**
     * @return void
     *
     * Applies overrides to the configuration array.
     * This is usually used to modify specific configuration values from the command line or other sources.
     *
     * @throws \ReflectionException
     */
    public function applyOverrides(array $overrides): Config
    {
        Log::channel('magic')->info('Applying overrides to config: '.json_encode($overrides));

        foreach ($overrides as $override) {
            [$key, $value] = explode('=', $override, 2);
            $keys = explode('.', $key);

            $modified = $this; // start from root config
            $current = &$modified;

            foreach ($keys as $index => $k) {
                if (! property_exists($current, $k)) {
                    throw new \RuntimeException("Property '{$k}' does not exist in ".get_class($current));
                }

                // If this is the last key, assign the value with proper type
                if ($index === array_key_last($keys)) {
                    $reflection = new \ReflectionProperty($current, $k);
                    $type = $reflection->getType()?->getName() ?? 'mixed';
                    $currentShortClass = new \ReflectionClass($current)->getShortName();

                    // If the property is a nested config object
                    if (class_exists($type) && is_subclass_of($type, Config::class)) {
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
     * Print debug information about the configuration.
     * Loop all properties and their values.
     * Display them in Log channel 'magic'.
     */
    public function printDebugInfo()
    {
        Log::channel('magic')->info('Configuration Debug Info:');
        Log::channel('magic')->info("App Name: {$this->app->name}");
        Log::channel('magic')->info('Entities Count: '.count($this->entities));
        Log::channel('magic')->info('Development Seed Enabled: '.($this->dev->seedEnabled ? 'true' : 'false'));
        Log::channel('magic')->info("Development Seed Count: {$this->dev->seedCount}");

        foreach ($this->entities as $entity) {
            Log::channel('magic')->info("Entity: {$entity->getName()}, Table: {$entity->getTableName()}");
        }
    }
}
