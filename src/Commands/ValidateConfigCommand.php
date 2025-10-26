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

        $structureErrors = $this->validateStructure($decoded, $this->resolveSchemaPath());

        if (! empty($structureErrors)) {
            foreach ($structureErrors as $error) {
                $this->error($error);
            }

            return CommandAlias::FAILURE;
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

    private function validateStructure(array $data, ?string $schemaPath = null): array
    {
        $errors = [];

        if (! isset($data['app']) || ! is_array($data['app'])) {
            $errors[] = 'The configuration must include an "app" object.';
        } else {
            $errors = array_merge($errors, $this->validateAppSection($data['app']));
        }

        if (! isset($data['entities']) || ! is_array($data['entities']) || $data['entities'] === []) {
            $errors[] = 'The configuration must include at least one entity.';
        } else {
            foreach ($data['entities'] as $index => $entity) {
                if (! is_array($entity)) {
                    $errors[] = "Entity at index {$index} must be an object.";

                    continue;
                }

                $errors = array_merge($errors, $this->validateEntity($entity, $index));
            }
        }

        if ($schemaPath === null) {
            $this->warn('JSON schema file not found. Falling back to built-in validation rules.');
        }

        return $errors;
    }

    private function validateAppSection(array $app): array
    {
        $errors = [];

        foreach (['name', 'seedEnabled', 'seedCount'] as $key) {
            if (! array_key_exists($key, $app)) {
                $errors[] = "The app section must define the \"{$key}\" property.";
            }
        }

        if (isset($app['seedEnabled']) && ! is_bool($app['seedEnabled'])) {
            $errors[] = 'The app.seedEnabled property must be a boolean value.';
        }

        if (isset($app['seedCount']) && ! is_int($app['seedCount'])) {
            $errors[] = 'The app.seedCount property must be an integer.';
        }

        if (isset($app['fakerMappings']) && ! is_array($app['fakerMappings'])) {
            $errors[] = 'The app.fakerMappings property must be an object.';
        }

        return $errors;
    }

    private function validateEntity(array $entity, int $index): array
    {
        $errors = [];
        $prefix = "entities[{$index}]";

        if (! isset($entity['name']) || ! is_string($entity['name']) || $entity['name'] === '') {
            $errors[] = "{$prefix}.name must be a non-empty string.";
        }

        if (! isset($entity['fields']) || ! is_array($entity['fields']) || $entity['fields'] === []) {
            $errors[] = "{$prefix}.fields must be a non-empty array.";
        } else {
            foreach ($entity['fields'] as $fieldIndex => $field) {
                if (! is_array($field)) {
                    $errors[] = "{$prefix}.fields[{$fieldIndex}] must be an object.";

                    continue;
                }

                if (! isset($field['name']) || ! is_string($field['name']) || $field['name'] === '') {
                    $errors[] = "{$prefix}.fields[{$fieldIndex}].name must be a non-empty string.";
                }

                if (! isset($field['type']) || ! is_string($field['type']) || $field['type'] === '') {
                    $errors[] = "{$prefix}.fields[{$fieldIndex}].type must be a non-empty string.";
                }
            }
        }

        if (isset($entity['relations'])) {
            if (! is_array($entity['relations'])) {
                $errors[] = "{$prefix}.relations must be an array.";
            } else {
                foreach ($entity['relations'] as $relationIndex => $relation) {
                    if (! is_array($relation)) {
                        $errors[] = "{$prefix}.relations[{$relationIndex}] must be an object.";

                        continue;
                    }

                    if (! isset($relation['type']) || ! is_string($relation['type']) || $relation['type'] === '') {
                        $errors[] = "{$prefix}.relations[{$relationIndex}].type must be a non-empty string.";
                    }

                    if (! isset($relation['relatedEntityName']) || ! is_string($relation['relatedEntityName']) || $relation['relatedEntityName'] === '') {
                        $errors[] = "{$prefix}.relations[{$relationIndex}].relatedEntityName must be a non-empty string.";
                    }

                    if (($relation['type'] ?? null) === 'belongsToMany' && empty($relation['pivot'])) {
                        $errors[] = "{$prefix}.relations[{$relationIndex}].pivot is required for belongsToMany relations.";
                    }
                }
            }
        }

        if (isset($entity['filters'])) {
            if (! is_array($entity['filters'])) {
                $errors[] = "{$prefix}.filters must be an array.";
            } else {
                foreach ($entity['filters'] as $filterIndex => $filter) {
                    if (! is_array($filter)) {
                        $errors[] = "{$prefix}.filters[{$filterIndex}] must be an object.";

                        continue;
                    }

                    if (! isset($filter['field']) || ! is_string($filter['field']) || $filter['field'] === '') {
                        $errors[] = "{$prefix}.filters[{$filterIndex}].field must be a non-empty string.";
                    }

                    if (! isset($filter['type']) || ! is_string($filter['type']) || $filter['type'] === '') {
                        $errors[] = "{$prefix}.filters[{$filterIndex}].type must be a non-empty string.";
                    }
                }
            }
        }

        if (isset($entity['actions'])) {
            if (! is_array($entity['actions'])) {
                $errors[] = "{$prefix}.actions must be an array.";
            } else {
                foreach ($entity['actions'] as $actionIndex => $action) {
                    if (! is_array($action)) {
                        $errors[] = "{$prefix}.actions[{$actionIndex}] must be an object.";

                        continue;
                    }

                    if (! isset($action['name']) || ! is_string($action['name']) || $action['name'] === '') {
                        $errors[] = "{$prefix}.actions[{$actionIndex}].name must be a non-empty string.";
                    }

                    if (($action['type'] ?? null) === 'command' && (! isset($action['command']) || ! is_string($action['command']) || $action['command'] === '')) {
                        $errors[] = "{$prefix}.actions[{$actionIndex}].command is required when type is command.";
                    }
                }
            }
        }

        return $errors;
    }
}
