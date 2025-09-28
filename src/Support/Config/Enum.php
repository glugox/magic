<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Helpers\EnumFieldOptionsParser;
use Glugox\Magic\Support\Config\Field\EnumFieldOption;

class Enum
{
    public function __construct(
        // Entity name, e.g. "User", "Post"
        public string $name,
        /** @var EnumFieldOption[] */
        public ?array $options = [],
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
            options: EnumFieldOptionsParser::parse($data['options'] ?? []),
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
            'options' => $this->options,
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
