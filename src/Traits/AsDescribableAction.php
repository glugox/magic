<?php

namespace Glugox\Magic\Traits;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Support\ActionDescriptionData;
use ReflectionClass;

trait AsDescribableAction
{
    /**
     * Returns structured metadata describing the action.
     * Example:
     * [
     *   'name' => 'generate_entity_form',
     *   'description' => 'Generates a Vue form component from an entity config',
     *   'parameters' => [
     *      'entity' => 'The entity configuration JSON'
     *   ]
     * ]
     */
    public function describe(): ActionDescriptionData
    {
        $ref = new ReflectionClass($this);
        $attrs = $ref->getAttributes(ActionDescription::class);

        if (! empty($attrs)) {
            $instance = $attrs[0]->newInstance();

            return new ActionDescriptionData(
                name: $instance->name,
                description: $instance->description,
                parameters: $instance->parameters,
            );
        }

        return new ActionDescriptionData(
            name: $ref->getShortName(),
            description: 'No description provided'
        );
    }
}
