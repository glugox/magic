<?php

declare(strict_types=1);

namespace Glugox\Builder\Actions;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Concerns\AsDescribableAction;
use Glugox\Builder\Contracts\DescribableAction;
use Glugox\Builder\Dto\BuilderConfig;
use Glugox\Builder\Dto\EntityDefinition;

#[ActionDescription(
    name: 'resolve_primary_entity',
    description: 'Returns the primary entity from the builder configuration.',
    parameters: [
        'config' => 'The loaded builder configuration.',
    ],
)]
class ResolvePrimaryEntityAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(BuilderConfig $config): EntityDefinition
    {
        return $config->primaryEntity();
    }
}
