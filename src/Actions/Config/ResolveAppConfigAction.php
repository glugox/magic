<?php

namespace Glugox\Magic\Actions\Config;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;
use RuntimeException;

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
        $config = null;

        if (! empty($options['config'])) {
            $filePath = $options['config'];
            if (file_exists($filePath)) {
                $config = Config::fromJsonFile($filePath);
            }
        }

        if ($config === null && ! empty($options['starter'])) {
            $starter = $options['starter'];
            $starterPath = __DIR__."/../../../stubs/samples/{$starter}.json";
            if (file_exists($starterPath)) {
                $config = Config::fromJsonFile($starterPath);
            }
        }

        if ($config === null) {
            throw new RuntimeException('Unable to resolve Magic configuration from the provided options.');
        }

        $overrides = $options['overrides'] ?? $options['set'] ?? [];
        if (is_string($overrides)) {
            $overrides = [$overrides];
        }

        if (! empty($overrides)) {
            $config = $config->applyOverrides($overrides);
        }

        Log::channel('magic')->info('Config parsed successfully.');

        return $config;
    }
}
