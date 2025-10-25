<?php

namespace Glugox\Magic\Helpers;

use RuntimeException;

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
        $envPath = $envPath ?? \Glugox\Magic\Support\MagicPaths::base('.env');
        if (! file_exists($envPath)) {
            throw new RuntimeException("Env file not found at: {$envPath}");
        }

        $content = file_get_contents($envPath);

        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);

        $pattern = "/^{$key}\s*=\s*.*$/m";
        $line = $key.'='.self::escapeValue($value);

        if (preg_match($pattern, $content)) {
            // Replace existing line
            $content = preg_replace($pattern, $line, $content);
        } else {
            // Append new variable with a newline before if not empty
            $content = mb_rtrim($content)."\n".$line."\n";
        }

        file_put_contents($envPath, $content);
    }

    /**
     * Escape env value properly (only quote if needed).
     */
    protected static function escapeValue(string $value): string
    {
        if (preg_match('/\s/', $value) || str_contains($value, '#') || str_contains($value, '"')) {
            return '"'.addslashes($value).'"';
        }

        return $value;
    }
}
