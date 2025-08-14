<?php

namespace Glugox\Magic\Support\Config;

class App
{
    public function __construct(
        private string $name
    ) {}

    public static function fromConfig(array $data): self
    {
        return new self(
            $data['name'] ?? 'Uno'
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
