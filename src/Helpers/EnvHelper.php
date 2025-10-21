<?php

namespace Glugox\Magic\Helpers;

class EnvHelper
{
    /**
     * Set or update an environment variable in the .env file.
     *
     * @param  string  $key  The environment variable key.
     * @param  string  $value  The environment variable value.
     * @param  string|null  $envPath  The path to the .env file. Defaults to base_path('.env').
     */
    public static function setEnvValue(string $key, string $value, ?string $envPath = null): void
    {
        $envPath = $envPath ?? base_path('.env');
        $escaped = preg_quote($key, '/');

        $content = file_get_contents($envPath);

        // Check if key exists
        if (preg_match("/^{$escaped}=.*/m", $content)) {
            // Replace existing
            $content = preg_replace(
                "/^{$escaped}=.*/m",
                "{$key}=\"{$value}\"",
                $content
            );
        } else {
            // Append new
            $content .= "\n{$key}=\"{$value}\"";
        }

        file_put_contents($envPath, $content);
    }
}
