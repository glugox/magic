<?php

namespace Glugox\Magic\Actions;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Traits\AsDescribableAction;

#[ActionDescription(
    name: 'parse_entity_config',
    description: 'Parses a JSON configuration into a Config object, data class for config, entities ( that contain fields ), etc.',
    parameters: ['input' => 'The entity configuration as JSON string or array']
)]
class ParseEntityConfig implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param  string|array  $input  JSON string or array
     *
     * @throws \JsonException
     */
    public function __invoke(string|array $input): Config
    {
        return Config::fromJson($input);
    }
}
