<?php

namespace Glugox\Magic\Support\Config;

class App
{
    public function __construct(
        /**
         * The name of the application.
         *
         * @var string
         */
        public string $name,

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
         */
        public ?array $fakerMappings = null,

        /**
         * Whether to generate strong passwords (longer time) for seeded users,
         * or use 'password' (faster) for testing purposes.
         */
        public ?bool $strongPasswords = false
    ) {}

    public static function fromConfig(array $data): self
    {
        return new self(
            $data['name'] ?? 'Uno',
            $data['seedEnabled'] ?? false,
            $data['seedCount'] ?? 20,
            $data['fakerMappings'] ?? null,
            $data['strongPasswords'] ?? false
        );
    }

    /**
     * To array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    /**
     * To JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
