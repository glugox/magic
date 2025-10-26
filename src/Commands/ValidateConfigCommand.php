<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Validation\MagicConfigValidator;
use Illuminate\Support\Facades\File;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

class ValidateConfigCommand extends MagicBaseCommand
{
    protected $signature = 'magic:validate
        {--config= : Path to the JSON config file to validate}
        {--schema= : Optional path to the JSON schema file to use for structural validation}';

    protected $description = 'Validate a Magic JSON configuration file.';

    public function handle(): int
    {
        $configOption = (string) ($this->option('config') ?? '');

        if ($configOption === '') {
            $this->error('The --config option is required.');

            return CommandAlias::FAILURE;
        }

        $configPath = Config::ensureBasePath($configOption);

        if (! is_file($configPath)) {
            $this->error("Configuration file not found at {$configPath}.");

            return CommandAlias::FAILURE;
        }

        try {
            $json = File::get($configPath);
        } catch (Throwable $exception) {
            $this->error('Failed to read configuration file: '.$exception->getMessage());

            return CommandAlias::FAILURE;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('The configuration file contains invalid JSON: '.$exception->getMessage());

            return CommandAlias::FAILURE;
        }


        $schemaPath = $this->resolveSchemaPath();
        if ($schemaPath !== null) {
            try {
                MagicConfigValidator::validateJsonSchema($json, $schemaPath);
            } catch (Throwable $exception) {
                $this->error('JSON Schema validation failed: '.$exception->getMessage());
                return CommandAlias::FAILURE;
            }
        } else {
            $this->warn('No JSON schema file found. Skipping structural validation.');
        }


        $autoFixWasEnabled = MagicConfigValidator::$autoFixEnabled;
        MagicConfigValidator::disableAutoFix();

        try {
            Config::fromJson($json);
        } catch (Throwable $exception) {
            $this->error('Magic validation failed: '.$exception->getMessage());

            return CommandAlias::FAILURE;
        } finally {
            if ($autoFixWasEnabled) {
                MagicConfigValidator::enableAutoFix();
            } else {
                MagicConfigValidator::disableAutoFix();
            }
        }

        $this->info('Configuration file is valid.');

        return CommandAlias::SUCCESS;
    }

    private function resolveSchemaPath(): ?string
    {
        $schemaOption = $this->option('schema');

        if (is_string($schemaOption) && $schemaOption !== '') {
            $schemaPath = Config::ensureBasePath($schemaOption);

            return is_file($schemaPath) ? $schemaPath : null;
        }

        $candidates = [
            Config::ensureBasePath('json-schema.json'),
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'json-schema.json',
            getcwd().DIRECTORY_SEPARATOR.'json-schema.json',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
