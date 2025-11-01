<?php

declare(strict_types=1);

namespace Glugox\Builder\Contracts;

use Glugox\Builder\Support\ActionDescriptionData;

/**
 * Contract implemented by actions that can describe themselves for AI agents.
 */
interface DescribableAction
{
    public function describe(): ActionDescriptionData;
}
