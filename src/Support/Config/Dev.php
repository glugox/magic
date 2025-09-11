<?php

namespace Glugox\Magic\Support\Config;

class Dev
{
    public function __construct(

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
