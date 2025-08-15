<?php

declare(strict_types=1);

namespace Glugox\Magic\Support\Config;

class Config
{
    public App $app;

    /** @var Entity[] */
    private array $entities = [];

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
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Convert the configuration from JSON string to Config object.
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
     * Get the application configuration.
     *
     * @return App
     */
    public function getDevConfig(): Dev
    {
        return $this->dev;
    }
}
