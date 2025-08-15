<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\ConfigLoader;
use Glugox\Magic\Support\ConsoleBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Command to reset the Laravel application by removing generated files
 * and resetting migrations, models, seeders, controllers, TypeScript support files,
 * and other related components.
 */
class ResetAppCommand extends Command
{
    protected $signature = 'magic:reset {--config= : Path to JSON config file} {--starter= : Starter template to use}';

    protected $description = 'Build Laravel app parts from JSON config';

    /**
     * Console block for structured output.
     *
     * ex output:
     *
     * ```
     *
     * Resetting migrations...
     *
     * ✅ Migrations reset completed!
     *
     * ```
     */
    private ConsoleBlock $block;

    public function handle()
    {
        // Initialize console block for structured output
        $this->block = new ConsoleBlock($this);
        $this->block->info('Resetting Magic...');

        $configPath = $this->option('config') ?? config('magic.config_path');
        $starterTemplate = $this->option('starter');
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

        // Check if the starter template option is provided
        if ($starterTemplate) {
            Log::channel('magic')->info("Using starter template: {$starterTemplate}");
            // Copy starter template files from stubs/samples/
            $source = __DIR__ . "/../../stubs/samples/{$starterTemplate}.json";
            $destination = base_path("{$starterTemplate}.json");

            if (File::exists($source)) {
                File::copy($source, $destination);
                Log::channel('magic')->info("Copied starter template to: {$destination}");

                // Update config path to the new starter template
                $configPath = $destination;
            } else {
                Log::channel('magic')->error("Starter template file not found: {$source}");
                return 1;
            }
        } else {
            Log::channel('magic')->info("No starter template specified, using default.");
        }

        Log::channel('magic')->info("Loading config from: {$configPath}");

        try {
            $config = ConfigLoader::load($configPath);
        } catch (\Exception $e) {
            Log::channel('magic')->error("Failed to load config: " . $e->getMessage());
            return 1;
        }

        // Reset migrations
        Log::channel('magic')->info("Resetting migrations...");
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
                $fileRelative = str_replace(database_path('migrations') . '/', '', $file);
                if (File::exists($file)) {
                    File::delete($file);
                    Log::channel('magic')->info("Migration file deleted: {$fileRelative}");
                } else {
                    Log::channel('magic')->warning("Migration file does not exist: {$fileRelative} . Nothing to delete.");
                }
            }
        }
        Log::channel('magic')->info("Migrations reset successfully!");

        // Remove calls in DatabaseSeeder
        CodeGenerationHelper::removeRegion($databaseSeederPath);

        // Reset models and seeders
        foreach ($config->getEntities() as $entity) {

            // Reset models
            Log::channel('magic')->info("Resetting model: " . $entity->getName());
            $modelPath = app_path('Models/' . $entity->getName() . '.php');
            if (file_exists($modelPath)) {
                unlink($modelPath);
                Log::channel('magic')->info("Model deleted: {$entity->getName()}");
            } else {
                Log::channel('magic')->warning("Model does not exist: {$entity->getName()}. Nothing to delete.");
            }

            // Reset seeders
            Log::channel('magic')->info("Resetting seeder for: " . $entity->getName());

            $seederPath = database_path('seeders/' . $entity->getName() . 'Seeder.php');
            if (file_exists($seederPath)) {
                unlink($seederPath);
                Log::channel('magic')->info("Seeder deleted for {$entity->getName()}");
            } else {
                Log::channel('magic')->warning("Seeder does not exist for: {$entity->getName()}. Nothing to delete.");
            }
            // Remove pivot seeders if they exist by checking for related entities
            foreach ($entity->getRelations() as $relation) {
                $pivotNameStudly = \Str::studly($relation->getPivotName());
                $pivotSeederPath = database_path('seeders/' . $pivotNameStudly . 'PivotSeeder.php');
                if (file_exists($pivotSeederPath)) {
                    unlink($pivotSeederPath);
                    Log::channel('magic')->info("Pivot seeder deleted for: {$pivotNameStudly}");
                } else {
                    Log::channel('magic')->warning("Pivot seeder does not exist for: {$pivotNameStudly}. Nothing to delete.");
                }
            }

            // Reset factories
            Log::channel('magic')->info("Resetting factory for: " . $entity->getName());
            $factoryPath = database_path('factories/' . $entity->getName() . 'Factory.php');
            if (file_exists($factoryPath)) {
                unlink($factoryPath);
                Log::channel('magic')->info("Factory deleted for: {$entity->getName()}");
            } else {
                Log::channel('magic')->warning("Factory does not exist for: {$entity->getName()}. Nothing to delete.");
            }

            // Reset controllers
            Log::channel('magic')->info("Resetting controller for: " . $entity->getName());
            $controllerPath = app_path('Http/Controllers/' . $entity->getName() . 'Controller.php');
            if (file_exists($controllerPath)) {
                unlink($controllerPath);
                Log::channel('magic')->info("Controller deleted for: {$entity->getName()}");
            } else {
                Log::channel('magic')->warning("Controller does not exist for: {$entity->getName()}. Nothing to delete.");
            }
        }

        // Reset TypeScript support files
        Log::channel('magic')->info("Resetting TypeScript support files...");
        $tsPath = resource_path('js/types/app.ts');
        if (file_exists($tsPath)) {
            unlink($tsPath);
            Log::channel('magic')->info("TypeScript support file deleted successfully!");
        } else {
            Log::channel('magic')->warning("TypeScript support file does not exist. Nothing to delete.");
        }

        // Remove lib files
        $libPath = resource_path('js/lib/app.ts');
        if (file_exists($libPath)) {
            unlink($libPath);
            Log::channel('magic')->info("JavaScript lib deleted successfully!");
        } else {
            Log::channel('magic')->warning("JavaScript lib does not exist. Nothing to delete.");
        }

        // Remove helper files, all files in the helpers directory
        $helpersPath = resource_path('js/helpers');
        if (is_dir($helpersPath)) {
            $files = glob($helpersPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $fileRelative = str_replace(resource_path() . '/', '', $file);
                    Log::channel('magic')->info("Helper file {$fileRelative} deleted successfully!");
                }
            }
        } else {
            Log::channel('magic')->warning("Helpers directory does not exist. Nothing to delete.");
        }

        // Reset Laravel app parts
        Log::channel('magic')->info("Resetting Laravel app parts...");
        $this->call('magic:reset-laravel');
        Log::channel('magic')->info("Laravel app parts reset successfully!");

        // Call migrate:reset to ensure database is clean
        $this->call('migrate:fresh', ['--force' => true]);
        Log::channel('magic')->info("Database migrations reset successfully!");


        Log::channel('magic')->info("Reset complete!");

        return 0;
    }
}
