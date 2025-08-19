<?php

namespace Glugox\Magic\Support\Config;

class Dev
{
    public function __construct(
        /**
         * Whether to enable seeding of the database with initial data.
         *
         * @var bool|null
         */
        public ?bool $seedEnabled = false,
        /**
         * Number of records to seed for each entity.
         *
         * @var int|null
         */
        public ?int $seedCount = 20,

        /**
         * Faker mappings for generating fake data.
         *
         */
        public ?array $fakerMappings = null,

        /**
         * Whether to generate strong passwords (longer time) for seeded users,
         * or use 'password' (faster) for testing purposes.
         */
        public ?bool $strongPasswords = false
    ) {}

    /**
     * Create a Dev configuration object from an array of properties.
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['seedEnabled'] ?? false,
            $data['seedCount'] ?? 20,
            $data['fakerMappings'] ?? null,
            $data['strongPasswords'] ?? false
        );
    }
}
