<?php

namespace Glugox\Magic\Contracts;

use Glugox\Magic\Support\ActionDescriptionData;

interface DescribableAction
{
    /**
     * Returns structured metadata describing the action.
     *
     * Example:
     * [
     *   'name' => 'generate_entity_form',
     *   'description' => 'Generates a Vue form component from an entity config',
     *   'parameters' => [
     *      'entity' => 'The entity configuration JSON'
     *   ]
     * ]
     */
    public function describe(): ActionDescriptionData;
}
