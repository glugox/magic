<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Support\Config\Config;
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
        $path = $path ?? config('magic.config_path', base_path('resume.json'));

        Log::channel('magic')->info("Loading Magic config from: {$path}");
        if (! file_exists($path)) {
            Log::channel('magic')->error("Config file not found at: {$path}");
            throw new RuntimeException("Config file not found at: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            Log::channel('magic')->error("Unable to read config file at: {$path}");
            throw new RuntimeException("Unable to read config file at: {$path}");
        }

        $config = Config::fromJson($json);

        Log::channel('magic')->info('Config loaded successfully: '.$path);

        // Apply overrides if provided
        if ($overrides) {
            $config = $config->applyOverrides($overrides);
        }

        return $config;
    }
}
