<?php

declare(strict_types=1);

namespace Glugox\Builder\Concerns;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Support\ActionDescriptionData;
use ReflectionClass;

/**
 * Reusable implementation of the {@see \Glugox\Builder\Contracts\DescribableAction} contract.
 */
trait AsDescribableAction
{
    public function describe(): ActionDescriptionData
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(ActionDescription::class);

        if ($attributes !== []) {
            $instance = $attributes[0]->newInstance();

            return new ActionDescriptionData(
                name: $instance->name,
                description: $instance->description,
                parameters: $instance->parameters,
            );
        }

        return new ActionDescriptionData(
            name: $reflection->getShortName(),
            description: 'No description available.',
        );
    }
}
