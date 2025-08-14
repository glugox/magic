<?php

namespace Glugox\Magic\Support\Config;

class Dev
{
    public function __construct(
        private ?bool $seedEnabled = false,
        private ?int $seedCount = 20,
    ){}

    /**
     * Create a Dev configuration object from an array of properties.
     *
     * @param array $data
     * @return Dev
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['seedEnabled'] ?? false,
            $data['seedCount'] ?? 20
        );
    }

    public function isSeedEnabled(): bool
    {
        return $this->seedEnabled;
    }

    public function getSeedCount(): int
    {
        return $this->seedCount;
    }
}
