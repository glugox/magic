<?php

declare(strict_types=1);

namespace Glugox\Builder\Support;

/**
 * Structured metadata describing an action so it can be surfaced to AI tooling.
 */
readonly class ActionDescriptionData
{
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters = [],
    ) {}
}
