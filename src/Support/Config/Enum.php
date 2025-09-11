<?php

namespace Glugox\Magic\Support\Config;

class Enum
{
    public function __construct(
        // Entity name, e.g. "User", "Post"
        public string $name,
        /** @var string[] */
        public ?array $values = [],
    ) {}

    /**
     * Create an Entity object from an array of properties.
     */
    public static function fromConfig(array|string $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(
            name: $data['name'] ?? '',
            values: $data['values'] ?? [],
        );
    }

    /**
     * Get the enum's name.
     * Example: "Status"
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns json representation of the entity.
     */
    public function toJson(): string
    {
        $data = [
            'name' => $this->name,
            'values' => $this->values,
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
