<?php

declare(strict_types=1);

namespace Glugox\Builder\Attributes;

use Attribute;

/**
 * Provides metadata that can be used to describe an action to AI agents.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ActionDescription
{
    public function __construct(
        public string $name = '',
        public string $description = '',
        public array $parameters = [],
    ) {}
}
