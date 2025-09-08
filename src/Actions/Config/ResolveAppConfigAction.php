<?php

namespace Glugox\Magic\Actions\Config;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;

#[ActionDescription(
    name: 'parse_app_config',
    description: 'Parses a JSON configuration into a Config object, data class for config, entities ( that contain fields ), etc.',
    parameters: [
        'filePath' => 'The path to the JSON configuration file',
        'starter' => 'The starter template to use (optional)',
        'overrides' => 'Inline config overrides in key=value format (dot notation allowed) (optional)',
    ]
)]
class ResolveAppConfigAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    public function __invoke(
        array $options
    ): Config {
        // Initialize an empty config
        $config = null;
        // Check if input is a file path
        if (! empty($options['config'])) {
            $filePath = $options['config'];
            if (file_exists($filePath)) {
                $config = Config::fromJsonFile($filePath);
            }
        }
        // Check if input is a starter template
        if (! empty($options['starter'])) {
            $starter = $options['starter'];
            $starterPath = __DIR__."/../../../stubs/samples/{$starter}.json";
            if (file_exists($starterPath)) {
                $config = Config::fromJsonFile($starterPath);
            }
        }
        // Apply overrides if provided
        if ($config && ! empty($options['overrides'])) {
            $overrides = $options['overrides'];
            $config = $config->applyOverrides($overrides);
        }

        Log::channel('magic')->info('Config parsed successfully.');

        return $config;
    }
}
