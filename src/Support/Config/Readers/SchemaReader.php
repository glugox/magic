<?php

namespace Glugox\Magic\Support\Config\Readers;

use Glugox\Magic\Helpers\GraphQlHelper;
use Glugox\Magic\Support\Config\App;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Enum;
use Glugox\Magic\Support\Config\Normalizer\GraphQlTypeNormalizer;

class SchemaReader
{
    /**
     * @var App Application configuration for general settings like name, and version.
     */
    protected App $app;

    /**
     * @var array<string, Entity> Associative array of entities keyed by their names.
     */
    protected array $entities = [];

    /**
     * Returns all enums found in the SDL.
     *
     * @return array<Enum> List of enums
     */
    public function getEnums(): array
    {
        return $this->graphQlHelper->getEnums();
    }

    public function __construct(
        protected GraphQlTypeNormalizer $typeNormalizer,
        protected GraphQlHelper $graphQlHelper
    ) {
        $this->app = new App('');
    }

    /**
     * Reads the SDL string and populates the configuration.
     */
    public function load(string $sdl): void
    {
        $this->entities = [];

        // Match all type blocks, including optional @config
        preg_match_all('/(type|enum)\s+(\w+)(\s+@config)?\s*{([^}]*)}/s', $sdl, $matches, PREG_SET_ORDER);

        // Extract config first
        foreach ($matches as $match) {
            [$full, $kind, $name, $config, $body] = $match;
            if (trim($config) === '@config') {
                $this->graphQlHelper->populateApp($this->app, $match[4]);
                break; // Assuming only one @config block
            }
        }

        // Extract enums first, as entities may depend on them
        foreach ($matches as $match) {
            [$full, $kind, $name, $config, $body] = $match;
            if ($kind === 'enum') {
                $this->graphQlHelper->extractEnum($match);
            }
        }

        // Extract entities after all enums are added
        foreach ($matches as $match) {
            [$full, $kind, $name, $config, $body] = $match;
            if (trim($kind) === 'type' && trim($config) !== '@config') {
                $entity = $this->graphQlHelper->extractEntity($match);
                $this->entities[$entity->getName()] = $entity;
            }
        }

        // Extract relations after all entities are added
        foreach ($matches as $match) {
            [$full, $kind, $name, $config, $body] = $match;
            if (trim($kind) === 'type' && trim($config) !== '@config') {
                $entityName = trim($match[2]);
                $relations = $this->graphQlHelper->extractRelationsForEntity($match, $this->entities);
                foreach ($relations as $relation) {
                    $this->entities[$entityName]->addRelation($relation);
                }
            }
        }
    }

    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * @return array<Entity> List of entities
     */
    public function getEntities(): array
    {
        return array_values($this->entities);
    }

    /**
     * To json representation of the configuration.
     */
    public function toJson(): string
    {
        $data = [
            'app' => $this->app->toArray(),
            'entities' => array_map(fn($entity) => json_decode($entity->toJson(), true), $this->getEntities()),
            'enums' => array_map(fn($enum) => json_decode($enum->toJson(), true), $this->getEnums()),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
