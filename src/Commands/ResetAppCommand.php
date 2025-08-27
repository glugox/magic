<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\ConsoleBlock;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResetAppCommand extends MagicBaseCommand
{
    protected $signature = 'magic:reset
        {--config= : Path to JSON config file}
        {--starter= : Starter template to use}
        {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'Reset Laravel app by removing generated files and resetting migrations, models, seeders, controllers, and TypeScript files.';

    /**
     * Config object
     */
    private $config;

    private ConsoleBlock $block;

    /**
     * Main handler
     * throws \ReflectionException
     * throws \Exception
     * throws \JsonException
     * @throws \JsonException
     */
    public function handle(): int
    {

        // Resolve config
        try {
            $this->config = app(ResolveAppConfigAction::class)($this->options());
        } catch (\JsonException $e) {
            $this->error('Failed to parse JSON config file: '.$e->getMessage());
        } catch (\ReflectionException $e) {
            $this->error('Reflection exception: '.$e->getMessage());
        } catch (\Exception $e) {
            $this->error('Error resolving config: '.$e->getMessage());
        }

        // Build new BuildContext with resolved config
        //$context = BuildContext::fromOptions($this->options())->setConfig($this->config);

        $this->initializeConsole();

        // Delete migrations
        // This could delete create user migration, so make sure it is run before resetLaravelApp
        $this->resetMigrations();

        // All modified Laravel files are reverted, by copying original Laravel files to app root
        $this->resetLaravelApp();

        // Delete seeders, factories and controllers
        $this->resetModelsSeedersFactoriesControllers();

        // Remove routes/app.php that is added by Magic
        // Ref to the app.php in web.php is removed by resetLaravelApp()
        $this->resetRoutes();

        // Remove all *.ts files added by Magic
        $this->resetTypeScriptFiles();

        // Delete vue pages for entities including their folders
        $this->resetJsPages();

        // Delete all added vue component by Magic
        $this->resetComponents();

        // TODO : This line will probably be only needed besides the below resetDatabase()
        FilesGenerationUpdate::deleteGeneratedFiles();

        // After resetting migrations , now we can apply fresh migrations that do not contain Magic migrations, so db will be clean.
        $this->resetDatabase();

        $this->logInfo('Reset complete!');

        return 0;
    }

    /**
     * Initialize console block for structured output.
     */
    private function initializeConsole(): void
    {
        $this->block = new ConsoleBlock($this);
        $this->block->info('Resetting Magic...');
    }

    /*
     * Reset migrations by deleting migration files and removing calls in DatabaseSeeder.
     */
    private function resetMigrations(): void
    {
        $this->logInfo('Resetting migrations...');

        foreach ($this->config->entities as $entity) {
            $this->deleteEntityMigrations($entity);
        }

        // Remove calls in DatabaseSeeder
        CodeGenerationHelper::removeRegion(database_path('seeders/DatabaseSeeder.php'));

        $this->logInfo('Migrations reset successfully!');
    }

    /**
     * Delete migration files related to the given entity.
     */
    private function deleteEntityMigrations($entity): void
    {
        $tableName = $entity->getTableName();
        $tableNameSingular = Str::singular($tableName);

        $migrationFiles = array_merge(
            File::glob(database_path("migrations/*_create_{$tableName}_table.php")),
            File::glob(database_path("migrations/*_update_{$tableName}_table.php")),
            File::glob(database_path("migrations/*_create_*_{$tableNameSingular}_table.php")),
            File::glob(database_path("migrations/*_create_{$tableNameSingular}_*_table.php"))
        );

        foreach ($migrationFiles as $file) {
            $this->deleteFile($file, 'Migration');
        }
    }

    /**
     * Reset models, seeders, factories, and controllers by deleting their files.
     */
    private function resetModelsSeedersFactoriesControllers(): void
    {
        foreach ($this->config->entities as $entity) {
            $this->deleteFile(app_path("Models/{$entity->getName()}.php"), 'Model', $entity->getName());
            $this->deleteFile(database_path("seeders/{$entity->getName()}Seeder.php"), 'Seeder', $entity->getName());
            $this->deletePivotSeeders($entity);
            $this->deleteFile(database_path("factories/{$entity->getName()}Factory.php"), 'Factory', $entity->getName());
            $this->deleteFile(app_path("Http/Controllers/{$entity->getName()}Controller.php"), 'Controller', $entity->getName());
        }
    }

    /**
     * Reset routes definitions
     */
    private function resetRoutes(): void
    {
        // Delete app.php from routes dir
        $this->logInfo('Resetting routes...');
        $this->deleteFile(base_path('routes/app.php'), 'Routes');
    }

    /**
     * Delete pivot seeders for the given entity's relations.
     */
    private function deletePivotSeeders($entity): void
    {
        foreach ($entity->getRelations() as $relation) {
            $pivotNameStudly = Str::studly($relation->getPivotName());
            $this->deleteFile(database_path("seeders/{$pivotNameStudly}PivotSeeder.php"), 'Pivot Seeder', $pivotNameStudly);
        }
    }

    /**
     * Reset TypeScript support files and helper files.
     */
    private function resetTypeScriptFiles(): void
    {
        $this->deleteFile(resource_path('js/types/app.ts'), 'TypeScript support file');
        $this->deleteFile(resource_path('js/lib/app.ts'), 'JavaScript lib');
        $this->deleteFile(resource_path('js/types/magic.ts'), 'TypeScript support file');

        $helpersPath = resource_path('js/helpers');
        if (is_dir($helpersPath)) {
            foreach (glob($helpersPath.'/*') as $file) {
                if (is_file($file)) {
                    $this->deleteFile($file, 'Helper file');
                }
            }
        } else {
            $this->logWarning('Helpers directory does not exist. Nothing to delete.');
        }
    }

    /**
     * Reset JS pages by deleting generated page files for each entity.
     */
    private function resetJsPages(): void
    {
        $jsPagesPath = resource_path('js/pages');
        if (! is_dir($jsPagesPath)) {
            $this->logWarning('JS pages directory does not exist. Nothing to delete.');

            return;
        }

        foreach ($this->config->entities as $entity) {
            $entityDir = $jsPagesPath.'/'.$entity->getDirectoryName();
            if (! is_dir($entityDir)) {
                $this->logWarning("JS pages directory for {$entity->getName()} does not exist. Nothing to delete.");

                continue;
            }

            foreach (glob($entityDir.'/*') as $file) {
                if (is_file($file)) {
                    $this->deleteFile($file, 'JS page');
                }
            }

            rmdir($entityDir);
            $this->logInfo("JS pages directory for {$entity->getName()} deleted successfully!");
        }
    }

    /**
     * resetComponents
     */
    private function resetComponents(): void
    {
        $addedComponents = [
            'ResourceForm.vue',
            'ResourceTable.vue',
            'Avatar.vue',
        ];

        foreach ($addedComponents as $component) {
            $componentsPath = resource_path('js/components/'.$component);
            if (! file_exists($componentsPath)) {
                continue;
            }
            Log::channel('magic')->info("Removing {$component} component from {$componentsPath}");
            unlink($componentsPath);
        }
    }

    /**
     * Reset Laravel app parts by calling the magic:reset-laravel command.
     */
    private function resetLaravelApp(): void
    {
        $this->logInfo('Resetting Laravel app parts...');
        $this->call('magic:reset-laravel');
        $this->logInfo('Laravel app parts reset successfully!');
    }

    /**
     * Reset the database by running migrate:fresh.
     */
    private function resetDatabase(): void
    {
        $this->call('migrate:fresh', ['--force' => true]);
        $this->logInfo('Database migrations reset successfully!');
    }

    /**
     * Delete a file and log the action.
     */
    private function deleteFile(string $path, string $type, string $name = ''): void
    {
        $fileRelative = str_replace(base_path().'/', '', $path);
        if (file_exists($path)) {
            unlink($path);
            $this->logInfo("{$type} deleted".($name ? " for {$name}" : '').": {$fileRelative}");
        } else {
            $this->logWarning("{$type}".($name ? " for {$name}" : '')." does not exist. Nothing to delete. ( $path )");
        }
    }

    /**
     * Log an info message to both the log file and console block.
     */
    private function logInfo(string $message): void
    {
        Log::channel('magic')->info($message);
    }

    /**
     * Log a warning message to both the log file and console block.
     */
    private function logWarning(string $message): void
    {
        Log::channel('magic')->warning($message);
    }
}
