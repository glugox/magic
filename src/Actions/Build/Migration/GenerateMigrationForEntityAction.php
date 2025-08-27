<?php

namespace Glugox\Magic\Actions\Build\Migration;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

#[ActionDescription(
    name: 'generate_migration_for_entity',
    description: 'Generates a migration file for a single entity into /database/migrations',
    parameters: ['entity' => 'The entity configuration']
)]
class GenerateMigrationForEntityAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(Entity $entity): FilesGenerationUpdate
    {
        $filesGenerationUpdate = $this->generateMigrationForEntity($entity);
        $pivotTableUpdate = $this->generatePivotTables($entity);

        return $filesGenerationUpdate->merge($pivotTableUpdate);
    }

    protected function generateMigrationForEntity(Entity $entity): FilesGenerationUpdate
    {
        // We will return the list of generated/updated/deleted files
        $update = new FilesGenerationUpdate;

        $tableName = $entity->getTableName();
        $isUpdate = Schema::hasTable($tableName);

        // 1. Check if migration already exists
        $migrationFiles = File::glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (! $isUpdate && ! empty($migrationFiles)) {
            // Delete the existing migration files for create (they are obsolete)
            foreach ($migrationFiles as $file) {
                File::delete($file);
                $update->addDeleted($file);
                $fileRelative = str_replace(database_path('migrations/'), '', $file);
                Log::channel('magic')->debug("Deleted obsolete migration file: $fileRelative");
            }
        }

        // 2. Decide create vs update
        $updateOrCreateKey = $isUpdate ? 'update' : 'create';
        $fileName = date('Y_m_d_His').'_'.$updateOrCreateKey.'_'.$tableName.'_table.php';

        $migrationPath = database_path('migrations/'.$fileName);

        if ($isUpdate) {
            $columnsCode = $this->buildColumnsCodeForUpdate($entity);
            $template = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('$tableName', function (Blueprint \$table) {
$columnsCode
        });
    }

    public function down(): void
    {
        // Rollback logic for updated columns (optional)
    }
};
PHP;
        } else {
            $columnsCode = $this->buildColumnsCode($entity);
            // Keep db statements after the table is created
            $dbStatements = $this->buildDbStatements($entity);

            $template = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('$tableName', function (Blueprint \$table) {
$columnsCode
        });
$dbStatements
    }

    public function down(): void
    {
        Schema::dropIfExists('$tableName');
    }
};
PHP;
        }

        app(GenerateFileAction::class)($migrationPath, $template);
        $update->addCreated($migrationPath);

        $migrationPathRelative = str_replace(database_path('migrations/'), '', $migrationPath);
        Log::channel('magic')->debug(($isUpdate ? 'Update' : 'Create')." migration created: $migrationPathRelative");

        return $update;
    }

    /**
     * @return FilesGenerationUpdate Generate pivot tables for many-to-many relations.
     */
    protected function generatePivotTables(Entity $entity): FilesGenerationUpdate
    {
        $update = new FilesGenerationUpdate;

        foreach ($entity->getRelations() as $relation) {
            if ($relation->isManyToMany()) {
                $pivotTableName = $relation->getPivotName();
                $fileName = date('Y_m_d_His')."_create_{$pivotTableName}_table.php";

                $migrationPath = database_path('migrations/'.$fileName);

                // 1. Check if migration already exists
                $migrationFiles = File::glob(database_path("migrations/*_create_{$pivotTableName}_table.php"));
                if (! empty($migrationFiles)) {
                    // Delete the existing migration files for create (they are obsolete)
                    foreach ($migrationFiles as $file) {
                        File::delete($file);
                        $update->addDeleted($file);
                        $fileRelative = str_replace(database_path('migrations/'), '', $file);
                        Log::channel('magic')->debug("Deleted obsolete migration file: $fileRelative");
                    }
                }

                $columnsCode = <<<PHP
            \$table->foreignId('{$entity->getForeignKey()}')->constrained('{$entity->getTableName()}')->cascadeOnDelete();
            \$table->foreignId('{$relation->getLocalKey()}')->constrained('{$relation->getTableName()}')->cascadeOnDelete();
PHP;

                $template = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('$pivotTableName', function (Blueprint \$table) {
$columnsCode
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('$pivotTableName');
    }
};
PHP;

                app(GenerateFileAction::class)($migrationPath, $template);
                $update->addCreated($migrationPath);

                $migrationPathRelative = str_replace(database_path('migrations/'), '', $migrationPath);
                Log::channel('magic')->debug("Pivot migration created: $migrationPathRelative");
            }
        }

        return $update;
    }

    /**
     * Build the migration columns code for an update operation.
     */
    protected function buildColumnsCodeForUpdate(Entity $entity): string
    {
        $codeLines = [];

        // Find existing columns
        $existingColumns = Schema::getColumnListing($entity->getTableName());
        foreach ($entity->getFields() as $col) {
            $name = $col->name;
            if (in_array($name, $existingColumns)) {
                // Column exists, generate code to modify it
                // TODO
            } else {
                // Column does not exist, generate code to add it
                $line = $this->buildColumnCode($col);
                $codeLines[] = $line;
            }
        }

        return implode("\n", $codeLines);
    }

    /**
     * Build the migration columns code from the entity fields.
     */
    protected function buildColumnsCode(Entity $entity): string
    {
        $codeLines = [];
        foreach ($entity->getFields() as $col) {
            $line = $this->buildColumnCode($col);
            $codeLines[] = "            $line";
        }
        // Add timestamps if allowed
        if ($entity->hasTimestamps()) {
            $codeLines[] = '            $table->timestamps();';
        }

        return implode("\n", $codeLines);
    }

    /**
     * Build code for one column in the migration.
     */
    protected function buildColumnCode(Field $field): string
    {
        $line = '';
        $migrationType = $field->migrationType();
        $name = $field->name;

        // Start building the line
        $args = $field->migrationArgs();

        $line .= "\$table->{$migrationType}(".implode(', ', $args).')';

        // Nullable
        if ($field->nullable) {
            $line .= '->nullable()';
        }

        // Default value
        if ($field->default !== null) {
            $default = exportPhpValue($field->default);
            $line .= "->default({$default})";
        }

        // Comment
        if ($field->comment) {
            $comment = addslashes($field->comment);
            $line .= "->comment('{$comment}')";
        }

        // If the field is a foreign key, add the foreign key constraint
        if ($field->belongsTo()) {
            $relatedTable = $field->belongsTo()->getTableName();
            $line .= "->constrained('{$relatedTable}')->cascadeOnDelete()";
        }

        // Return main line
        return "$line;";
    }

    /**
     * Build additional DB statements after table creation.
     */
    protected function buildDbStatements(Entity $entity): string
    {
        $statements = [];

        foreach ($entity->getFields() as $field) {
            if (in_array($field->type, [FieldType::INTEGER, FieldType::FLOAT, FieldType::DECIMAL])) {
                if ($field->min > 0) {
                    $statements[] = '// Add check constraint for minimum value';
                    $statements[] = "if (config('database.default') !== 'sqlite') { DB::statement('ALTER TABLE {$entity->getTableName()} ADD CONSTRAINT chk_{$field->name}_min CHECK ({$field->name} >= {$field->min})'); }";
                }
                if ($field->max > 0) {
                    $statements[] = '// Add check constraint for maximum value';
                    $statements[] = "if (config('database.default') !== 'sqlite') { DB::statement('ALTER TABLE {$entity->getTableName()} ADD CONSTRAINT chk_{$field->name}_max CHECK ({$field->name} <= {$field->max})'); }";
                }
            }
        }

        // Return the statements as a string
        return "\n        ".implode("\n        ", $statements); // Indented for migration
    }
}
