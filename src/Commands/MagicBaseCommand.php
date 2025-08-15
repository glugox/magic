<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

abstract class MagicBaseCommand extends Command
{
    /**
     * Config path resolved from the command options or default config path.
     */
    protected ?string $configPath = null;

    /**
     * Config already resolved to prevent multiple loads.
     */
    protected ?Config $config = null;

    /**
     * @throws \JsonException
     */
    protected function getConfig(): ?Config
    {
        // If config is already loaded, return it
        if ($this->config) {
            return $this->config;
        }

        // Load configuration overrides and path
        $overrides = $this->option('set') ?? [];

        $configPath = $this->getConfigPath();
        $config = ConfigLoader::load($configPath, $overrides);
        if (! $config->isValid()) {
            throw new \JsonException("Invalid configuration file: {$configPath}");
        }

        // Update the resolved config
        $this->config = $config;

        Log::channel('magic')->info("Configuration loaded from: {$configPath}");

        return $this->config;
    }

    /**
     * Returns the path to the configuration file.
     */
    protected function getConfigPath(): string
    {
        // Check if config path is already resolved
        if ($this->configPath) {
            return $this->configPath;
        }

        $configPath = $this->option('config') ?? config('magic.config_path');

        // Handle starter template setup
        if ($starterPath = $this->setupStarterTemplate($this->option('starter'))) {
            $configPath = $starterPath;
        }

        if (! File::exists($configPath)) {
            throw new \RuntimeException("Configuration file not found: {$configPath}");
        }

        // Update the resolved config path
        $this->configPath = $configPath;

        return $this->configPath;
    }

    /**
     * Setup starter template if provided.
     */
    protected function setupStarterTemplate(?string $starter): ?string
    {
        if (! $starter) {
            Log::channel('magic')->info('No starter template specified, using default.');

            return null;
        }

        Log::channel('magic')->info("Using starter template: {$starter}");

        $source = __DIR__."/../../stubs/samples/{$starter}.json";
        $destination = base_path("{$starter}.json");

        if (! File::exists($source)) {
            $this->error("Starter template file not found: {$source}");

            return null;
        }

        File::copy($source, $destination);
        Log::channel('magic')->info("Copied starter template to: {$destination}");

        return $destination;
    }
}
