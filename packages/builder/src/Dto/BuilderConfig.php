<?php

declare(strict_types=1);

namespace Glugox\Builder\Dto;

use InvalidArgumentException;

/**
 * Holds the raw configuration extracted from the Magic JSON file.
 */
readonly class BuilderConfig
{
    /**
     * @param  EntityDefinition[]  $entities
     */
    public function __construct(
        public string $applicationName,
        public array $entities,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $entitiesPayload = $payload['entities'] ?? null;

        if (! is_array($entitiesPayload) || $entitiesPayload === []) {
            throw new InvalidArgumentException('The configuration must define at least one entity.');
        }

        $entities = [];
        foreach ($entitiesPayload as $entityPayload) {
            if (is_array($entityPayload)) {
                $entities[] = EntityDefinition::fromArray($entityPayload);
            }
        }

        if ($entities === []) {
            throw new InvalidArgumentException('Unable to resolve a valid entity from the configuration.');
        }

        $applicationName = $payload['app']['name'] ?? 'Application';
        if (! is_string($applicationName) || $applicationName === '') {
            $applicationName = 'Application';
        }

        return new self($applicationName, $entities);
    }

    public function primaryEntity(): EntityDefinition
    {
        return $this->entities[0];
    }
}
