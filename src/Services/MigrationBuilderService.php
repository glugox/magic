<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MigrationBuilderService
{
    public function __construct(
        protected Config $config
    ) {}

    public function build()
    {
        foreach ($this->config->entities as $entity) {
            $this->generateMigrationForEntity($entity);
            $this->generatePivotTables($entity);
        }
    }

    protected function generateMigrationForEntity(Entity $entity)
    {
        $tableName = $entity->getTableName();
        $isUpdate = \Schema::hasTable($tableName);

        // 1. Check if migration already exists
        $migrationFiles = File::glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (! $isUpdate && ! empty($migrationFiles)) {
            Log::channel('magic')->info("Skipping migration for '$tableName' — already exists.");

            return;
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
            $template = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('$tableName', function (Blueprint \$table) {
$columnsCode
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('$tableName');
    }
};
PHP;
        }

        File::put($migrationPath, $template);

        $migrationPathRelative = str_replace(database_path('migrations/'), '', $migrationPath);
        Log::channel('magic')->info(($isUpdate ? 'Update' : 'Create')." migration created: $migrationPathRelative");
    }

    /**
     * @return void Generate pivot tables for many-to-many relations.
     */
    protected function generatePivotTables(Entity $entity)
    {

        foreach ($entity->getRelations() as $relation) {
            if ($relation->isManyToMany()) {
                $pivotTableName = $relation->getPivotName();
                $fileName = date('Y_m_d_His')."_create_{$pivotTableName}_table.php";

                $migrationPath = database_path('migrations/'.$fileName);

                // 1. Check if migration already exists
                $migrationFiles = File::glob(database_path("migrations/*_create_{$pivotTableName}_table.php"));
                if (! empty($migrationFiles)) {
                    Log::channel('magic')->info("Skipping migration for '$pivotTableName' — already exists.");

                    return;
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

                File::put($migrationPath, $template);

                $migrationPathRelative = str_replace(database_path('migrations/'), '', $migrationPath);
                Log::channel('magic')->info("Pivot migration created: $migrationPathRelative");
            }
        }
    }

    /**
     * Build the migration columns code for an update operation.
     */
    protected function buildColumnsCodeForUpdate(Entity $entity): string
    {
        $codeLines = [];

        // Find existing columns
        $existingColumns = \Schema::getColumnListing($entity->getTableName());
        foreach ($entity->getFields() as $col) {
            $name = $col->getName();
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
     *
     * @param  Field[]  $columns
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
    protected function buildColumnCode(Field $col): string
    {

        $line = '';
        $type = $col->getType();
        $name = $col->getName();

        // Start building the line
        if (in_array($type, ['timestamps', 'timestampsTz'])) {
            // timestamps don’t take column name or params
            return "\$table->{$type}();";
        } elseif (in_array($type, ['softDeletes', 'softDeletesTz'])) {
            return "\$table->{$type}();";
        } else {
            $args = ["'$name'"];

            // Add length if exists and type supports it
            if (method_exists($col, 'getLength') && $col->getLength() !== null) {
                $args[] = $col->getLength();
            }

            // Add precision and scale if exist and type supports it
            if (method_exists($col, 'getPrecision') && method_exists($col, 'getScale')) {
                if ($col->getPrecision() !== null && $col->getScale() !== null) {
                    $args[] = $col->getPrecision();
                    $args[] = $col->getScale();
                }
            }

            // Add type-specific arguments
            // Enum type
            if ($col->isEnum() && ! empty($col->getValues())) {
                $values = '['.implode(', ', array_map(
                    fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE),
                    array_values($col->getValues())
                )).']';
                $args[] = $values;
            }

            $line .= "\$table->{$type}(".implode(', ', $args).')';

            // Nullable
            if (method_exists($col, 'isNullable') && $col->isNullable()) {
                $line .= '->nullable()';
            }
            // Default value
            if (method_exists($col, 'getDefault') && $col->getDefault() !== null) {
                $default = var_export($col->getDefault(), true);
                $line .= "->default({$default})";
            }
            // Comment
            if (method_exists($col, 'getComment') && $col->getComment()) {
                $comment = addslashes($col->getComment());
                $line .= "->comment('{$comment}')";
            }

            return "$line;";
        }
    }

    /**
     * Generate the pivot table name based on entity and target.
     */
    protected function getPivotTableName(string $entityName, string $targetEntityName): string
    {
        $tables = [
            \Str::snake(\Str::plural($entityName)),
            \Str::snake(\Str::plural($targetEntityName)),
        ];
        sort($tables); // alphabetical order

        return implode('_', $tables);
    }
}
