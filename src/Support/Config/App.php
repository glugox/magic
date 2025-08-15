<?php

namespace Glugox\Magic\Support\Config;

class App
{
    public function __construct(
        /**
         * The name of the application.
         * @var string
         */
        public string $name
    ) {}

    public static function fromConfig(array $data): self
    {
        return new self(
            $data['name'] ?? 'Uno'
        );
    }
}
