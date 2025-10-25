<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;
use RuntimeException;

/**
 * Loads and parses the JSON configuration file for the Magic package.
 */
class ConfigLoader
{
    /**
     * Load the JSON configuration file.
     *
     * @param  string|null  $path  Path to the JSON config file. If null, uses the default path from config.
     * @return Config Parsed configuration data.
     *
     * @throws JsonException If the file does not exist or contains invalid JSON.
     * @throws ReflectionException
     */
    public static function load(?string $path = null, ?array $overrides = null): Config
    {
        $path = $path ?? config('magic.config_path', MagicPaths::base('resume.json'));

        Log::channel('magic')->info("Loading Magic config from: {$path}");
        if (! file_exists($path)) {
            Log::channel('magic')->error("Config file not found at: {$path}");
            throw new RuntimeException("Config file not found at: {$path}");
        }

        $json = file_get_contents($path);

        $config = Config::fromJson($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('magic')->error('Invalid JSON in config file: '.json_last_error_msg());
            throw new RuntimeException('Invalid JSON in config file: '.json_last_error_msg());
        }

        Log::channel('magic')->info('Config loaded successfully: '.$path);

        // Apply overrides if provided
        if ($overrides) {
            $config = $config->applyOverrides($overrides);
        }

        return $config;
    }
}
