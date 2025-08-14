<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Support\Config\Config;

/**
 * Loads and parses the JSON configuration file for the Magic package.
 */
class ConfigLoader
{
    /**
     * Load the JSON configuration file.
     *
     * @param  string|null  $path  Path to the JSON config file. If null, uses the default path from config.
     * @return array Parsed configuration data.
     *
     * @throws \RuntimeException If the file does not exist or contains invalid JSON.
     */
    public static function load(?string $path = null): Config
    {
        $path = $path ?? config('magic.config_path', base_path('resume.json'));

        if (! file_exists($path)) {
            throw new \RuntimeException("Config file not found at: {$path}");
        }

        $json = file_get_contents($path);

        $config = Config::fromJson($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in config file: '.json_last_error_msg());
        }

        return $config;
    }
}
