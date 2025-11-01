<?php

declare(strict_types=1);

namespace Glugox\Builder\Dto;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Represents an entity definition extracted from the Magic JSON config.
 */
readonly class EntityDefinition
{
    /**
     * @param  string[]  $fields
     */
    public function __construct(
        public string $name,
        public array $fields,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? null;

        if (! is_string($name) || $name === '') {
            throw new InvalidArgumentException('Each entity must define a non-empty name.');
        }

        $fieldsData = $data['fields'] ?? [];

        if (! is_array($fieldsData)) {
            throw new InvalidArgumentException('Entity fields must be provided as an array.');
        }

        $fields = [];
        foreach ($fieldsData as $field) {
            if (is_array($field) && isset($field['name']) && is_string($field['name'])) {
                $fields[] = $field['name'];
            } elseif (is_string($field)) {
                $fields[] = $field;
            }
        }

        if ($fields === []) {
            throw new InvalidArgumentException('Entity must declare at least one field.');
        }

        return new self($name, array_values($fields));
    }

    /**
     * Produces a plural, kebab-cased route slug for the entity.
     */
    public function routeName(): string
    {
        $base = Str::kebab($this->name);

        return Str::plural($base);
    }

    /**
     * @return string[]
     */
    public function fieldNames(): array
    {
        return $this->fields;
    }
}
