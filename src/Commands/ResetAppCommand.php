<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\ConfigLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ResetAppCommand extends Command
{
    protected $signature = 'magic:reset {--config= : Path to JSON config file} {--starter= : Starter template to use}';

    protected $description = 'Build Laravel app parts from JSON config';

    public function handle()
    {
        $configPath = $this->option('config') ?? config('magic.config_path');
        $starterTemplate = $this->option('starter');
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

        // Check if the starter template option is provided
        if ($starterTemplate) {
            $this->info("Using starter template: {$starterTemplate}");
            // Copy starter template files from stubs/samples/
            $source = __DIR__."/../../stubs/samples/{$starterTemplate}.json";
            $destination = base_path("{$starterTemplate}.json");

            if (File::exists($source)) {
                File::copy($source, $destination);
                $this->info("Copied starter template to: {$destination}");

                // Update config path to the new starter template
                $configPath = $destination;
            } else {
                $this->error("Starter template file not found: {$source}");

                return 1;
            }
        } else {
            $this->info('No starter template specified, using default.');
        }

        $this->info("Loading config from: {$configPath}");

        try {
            $config = ConfigLoader::load($configPath);
        } catch (\Exception $e) {
            $this->error('Failed to load config: '.$e->getMessage());

            return 1;
        }
        $this->info('Config loaded successfully!');

        // Reset migrations
        $this->info('Resetting migrations...');
        foreach ($config->getEntities() as $entity) {
            $tableName = $entity->getTableName();

            // Regular create/update migrations for the entity's table
            $migrationCreateFiles = File::glob(database_path("migrations/*_create_{$tableName}_table.php"));
            $migrationUpdateFiles = File::glob(database_path("migrations/*_update_{$tableName}_table.php"));

            // Pivot table migrations (any table with entity name + underscore or underscore + entity name)
            $tableNameSingular = \Str::singular($tableName);
            $migrationPivotFiles = array_merge(
                File::glob(database_path("migrations/*_create_*_{$tableNameSingular}_table.php")),
                File::glob(database_path("migrations/*_create_{$tableNameSingular}_*_table.php"))
            );

            // Merge and delete all relevant migrations
            foreach (array_merge($migrationCreateFiles, $migrationUpdateFiles, $migrationPivotFiles) as $file) {
                if (File::exists($file)) {
                    File::delete($file);
                    $this->info("Migration file {$file} deleted successfully!");
                } else {
                    $this->warn("Migration file {$file} does not exist.");
                }
            }
        }
        $this->info('Migrations reset successfully!');

        // Remove calls in DatabaseSeeder
        CodeGenerationHelper::removeRegion($databaseSeederPath);

        // Reset models and seeders
        foreach ($config->getEntities() as $entity) {

            // Reset models
            $this->info('Resetting model: '.$entity->getName());
            $modelPath = app_path('Models/'.$entity->getName().'.php');
            if (file_exists($modelPath)) {
                unlink($modelPath);
                $this->info("Model {$entity->getName()} deleted successfully!");
            } else {
                $this->warn("Model {$entity->getName()} does not exist.");
            }

            // Reset seeders
            $this->info('Resetting seeder for: '.$entity->getName());

            $seederPath = database_path('seeders/'.$entity->getName().'Seeder.php');
            if (file_exists($seederPath)) {
                unlink($seederPath);
                $this->info("Seeder for {$entity->getName()} deleted successfully!");
            } else {
                $this->warn("Seeder for {$entity->getName()} does not exist.");
            }
            // Remove pivot seeders if they exist by checking for related entities
            foreach ($entity->getRelations() as $relation) {
                $pivotNameStudly = \Str::studly($relation->getPivotName());
                $pivotSeederPath = database_path('seeders/'.$pivotNameStudly.'PivotSeeder.php');
                if (file_exists($pivotSeederPath)) {
                    unlink($pivotSeederPath);
                    $this->info("Pivot seeder for {$pivotNameStudly} deleted successfully!");
                } else {
                    $this->warn("Pivot seeder for {$pivotNameStudly} does not exist.");
                }
            }

            // Reset factories
            $this->info('Resetting factory for: '.$entity->getName());
            $factoryPath = database_path('factories/'.$entity->getName().'Factory.php');
            if (file_exists($factoryPath)) {
                unlink($factoryPath);
                $this->info("Factory for {$entity->getName()} deleted successfully!");
            } else {
                $this->warn("Factory for {$entity->getName()} does not exist.");
            }

            // Reset controllers
            $this->info('Resetting controller for: '.$entity->getName());
            $controllerPath = app_path('Http/Controllers/'.$entity->getName().'Controller.php');
            if (file_exists($controllerPath)) {
                unlink($controllerPath);
                $this->info("Controller for {$entity->getName()} deleted successfully!");
            } else {
                $this->warn("Controller for {$entity->getName()} does not exist.");
            }
        }

        // Reset TypeScript support files
        $this->info('Resetting TypeScript support files...');
        $tsPath = resource_path('js/types/app.ts');
        if (file_exists($tsPath)) {
            unlink($tsPath);
            $this->info('TypeScript support file deleted successfully!');
        } else {
            $this->warn('TypeScript support file does not exist.');
        }

        // Remove lib files
        $libPath = resource_path('js/lib/app.ts');
        if (file_exists($libPath)) {
            unlink($libPath);
            $this->info('JavaScript lib deleted successfully!');
        } else {
            $this->warn('JavaScript lib does not exist.');
        }

        // Remove helper files, all files in the helpers directory
        $helpersPath = resource_path('js/helpers');
        if (is_dir($helpersPath)) {
            $files = glob($helpersPath.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $this->info("Helper file {$file} deleted successfully!");
                }
            }
        } else {
            $this->warn('Helpers directory does not exist.');
        }

        // Reset Laravel app parts
        $this->info('Resetting Laravel app parts...');
        $this->call('magic:reset-laravel');
        $this->info('Laravel app parts reset successfully!');

        // Call migrate:reset to ensure database is clean
        $this->call('migrate:fresh', ['--force' => true]);
        $this->info('Database migrations reset successfully!');

        $this->info('Reset complete!');

        return 0;
    }
}
