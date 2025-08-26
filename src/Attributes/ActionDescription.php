<?php

namespace Glugox\Magic\Attributes;

use Attribute;

/**
 * Attribute to describe an action with metadata.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ActionDescription
{
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters = []
    ) {}
}
