<?php

namespace Glugox\Magic\Support\Config;

class Dev
{
    public function __construct(
        /**
         * Whether to enable seeding of the database with initial data.
         * @var bool|null
         */
        public ?bool $seedEnabled = false,
        /**
         * Number of records to seed for each entity.
         * @var int|null
         */
        public ?int $seedCount = 20,

        /**
         * Faker mappings for generating fake data.
         * @var array
         */
        public ?array $fakerMappings = null,
    ){}

    /**
     * Create a Dev configuration object from an array of properties.
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['seedEnabled'] ?? false,
            $data['seedCount'] ?? 20,
            $data['fakerMappings'] ?? null
        );
    }
}
