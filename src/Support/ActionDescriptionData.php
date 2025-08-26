<?php

namespace Glugox\Magic\Support;

/**
 * Structured metadata describing an action.
 */
class ActionDescriptionData
{
    public function __construct(
        /**
         * The name of the action.
         *
         * @example "generate_entity_form"
         */
        public readonly string $name,
        /**
         * A brief description of what the action does.
         *
         * @example "Generates a Vue form component from an entity config"
         */
        public readonly string $description,
        /**
         * An associative array of parameter names to their descriptions.
         *
         * @example ["entity" => "The entity configuration JSON"]
         */
        public readonly array $parameters = []
    ) {}
}
