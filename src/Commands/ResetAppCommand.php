<?php

namespace Glugox\Magic\Commands;

use Exception;
use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\ConsoleBlock;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use ReflectionException;

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
    private Config $config;

    private ConsoleBlock $block;

    /**
     * Constructor
     */
    public function __construct(protected CodeGenerationHelper $codeHelper)
    {
        parent::__construct();
    }

    /**
     * Main handler
     * throws \ReflectionException
     * throws \Exception
     * throws \JsonException
     *
     * @throws JsonException
     */
    public function handle(): int
    {

        // Resolve config
        try {
            $this->config = app(ResolveAppConfigAction::class)($this->options());
        } catch (JsonException $e) {
            $this->error('Failed to parse JSON config file: '.$e->getMessage());
        } catch (ReflectionException $e) {
            $this->error('Reflection exception: '.$e->getMessage());
        } catch (Exception $e) {
            $this->error('Error resolving config: '.$e->getMessage());
        }

        // Build new BuildContext with resolved config
        // $context = BuildContext::fromOptions($this->options())->setConfig($this->config);

        $this->initializeConsole();

        // Delete generated enum files
        $this->logInfo('Resetting enums...');
        $enumsPath = app_path('Enums');
        if (is_dir($enumsPath)) {
            foreach ($this->config->entities as $entity) {
                foreach ($entity->getFields() as $field) {
                    if ($field->isEnum()) {
                        // PHP Enums
                        $enumClassName = Str::studly($entity->getName()).Str::studly($field->name).'Enum';
                        $this->deleteFile($enumsPath.'/'.$enumClassName.'.php', 'Enum', $enumClassName);

                        // TS Enums
                        $tsEnumPath = resource_path('js/enums');
                        $this->deleteFile($tsEnumPath.'/'.$enumClassName.'.ts', 'TS Enum', $enumClassName);
                    }
                }
            }
        } else {
            $this->logWarning('Enums directory does not exist. Nothing to delete.');
        }

        // Delete migrations
        // This could delete create user migration, so make sure it is run before resetLaravelApp
        $this->resetMigrations();

        // Delete model meta files
        $this->resetModelMeta();

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

        // All modified Laravel files are reverted, by copying original Laravel files to app root
        $this->resetLaravelApp();

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

        // TODO: FEATURES
        // Delete Features table migrations
        $migrationFiles = File::glob(database_path('migrations/*_create_attachments_table.php'));
        foreach ($migrationFiles as $file) {
            $this->deleteFile($file, 'Migration');
        }

        // Remove calls in DatabaseSeeder
        $this->codeHelper->removeRegion(database_path('seeders/DatabaseSeeder.php'));

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
     * Reset model meta files by deleting them.
     */
    private function resetModelMeta(): void
    {
        $this->logInfo('Resetting Model Meta files...');
        foreach ($this->config->entities as $entity) {
            $this->deleteFile(app_path("Meta/Models/{$entity->getName()}Meta.php"), 'Model Meta', $entity->getName());
        }
        $this->logInfo('Model Meta files reset successfully!');
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

            // Also delete API controller if exists
            $this->deleteFile(app_path("Http/Controllers/Api/{$entity->getName()}ApiController.php"), 'API Controller', $entity->getName());

            // Delete controllers for relations
            foreach ($entity->getRelations() as $relation) {
                $controllerFQN = $relation->getControllerFullQualifiedName();

                // Convert FQN to path: App\Http\Controllers\Api\RelationNameController -> app/Http/Controllers/Api/RelationNameController.php
                $controllerPath = app_path(str_replace('\\', '/', mb_ltrim($controllerFQN, '\\')));
                // Also remove the /App/ prefix if present
                $controllerPath = str_replace('App/', '', $controllerPath);

                $this->deleteFile($controllerPath, 'Relation Controller', $relation->getRelationName());

                // Try to remove the directory if empty
                $controllerDir = dirname($controllerPath);
                try {
                    $this->logInfo(">>>> Attempting to remove directory {$controllerDir} if empty...");
                    File::deleteDirectory($controllerDir);
                    $this->logInfo(">>>> Removing directory {$controllerDir} if empty...");

                } catch (Exception $e) {
                    $this->logWarning("Could not remove directory {$controllerDir}. It may not be empty. Error: ".$e->getMessage());
                }
            }
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

        // Remove the include line from web.php
        $webRoutesPath = base_path('routes/web.php');
        if (file_exists($webRoutesPath)) {
            $content = file_get_contents($webRoutesPath);
            if ($content === false) {
                $this->logWarning('Unable to read web.php. Skipping removal of app.php include.');

                return;
            }

            $pattern = "/require __DIR__\s*\.\s*'\/app\.php';\s*/";
            $newContent = preg_replace($pattern, '', $content);
            if ($newContent !== null && $newContent !== $content) {
                file_put_contents($webRoutesPath, $newContent);
                $this->logInfo('Removed app.php include from web.php');
            } else {
                $this->logWarning('No app.php include found in web.php or failed to modify file.');
            }
        } else {
            $this->logWarning('web.php file does not exist. Cannot remove app.php include.');
        }

        // Remove API routes if added in api.php
        // Deprecated: API routes will be reset by copying original Laravel files to app root in resetLaravelApp()

        // Remove routes/app directory if empty
        $appRoutesDir = base_path('routes/app');
        if (is_dir($appRoutesDir)) {
            try {
                File::deleteDirectory($appRoutesDir);
                $this->logInfo('Removed routes/app directory if empty.');
            } catch (Exception $e) {
                $this->logWarning("Could not remove directory {$appRoutesDir}. It may not be empty. Error: ".$e->getMessage());
            }
        } else {
            $this->logWarning('routes/app directory does not exist. Nothing to delete.');
        }
    }

    /**
     * Delete pivot seeders for the given entity's relations.
     */
    private function deletePivotSeeders($entity): void
    {
        foreach ($entity->getRelations() as $relation) {
            if (! $relation->requiresPivotTable()) {
                continue;
            }
            $pivotNameStudly = Str::studly($relation->getPivotName());
            $this->deleteFile(database_path("seeders/{$pivotNameStudly}PivotSeeder.php"), 'Pivot Seeder', $pivotNameStudly);
        }
    }

    /**
     * Reset TypeScript support files and helper files.
     */
    private function resetTypeScriptFiles(): void
    {
        // TODO: Get paths from config
        $this->deleteFile(resource_path('js/types/entities.ts'), 'TypeScript support file');
        $this->deleteFile(resource_path('js/types/support.ts'), 'TypeScript support file');
        $this->deleteFile(resource_path('js/lib/app.ts'), 'JavaScript lib');

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

            // Try to remove the entity directory if empty
            try {
                File::deleteDirectory($entityDir);
            } catch (Exception $e) {
                $this->logWarning("Could not remove directory {$entityDir}. It may not be empty. Error: ".$e->getMessage());
            }

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
