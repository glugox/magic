<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SeederBuilderService
{
    protected string $factoriesPath;

    protected string $seedersPath;

    protected array $generatedPivotSeeders = []; // track already generated pivot seeders

    public function __construct(
        protected Filesystem $files,
        protected Config $config
    ) {
        // Ensure the factories directory exists
        $this->factoriesPath = database_path('factories');
        if (! File::exists($this->factoriesPath)) {
            File::makeDirectory($this->factoriesPath, 0755, true);
        }

        // Ensure the seeders directory exists
        $this->seedersPath = database_path('seeders');
        if (! File::exists($this->seedersPath)) {
            File::makeDirectory($this->seedersPath, 0755, true);
        }
    }

    /**
     * Build all models based on the configuration.
     */
    public function build(): void
    {
        // Add seeding code to create admin user
        $this->generateAdminUserSeeder();

        foreach ($this->config->entities as $entity) {
            $this->generateFactory($entity);
            $this->generateSeeder($entity);
        }
    }

    /**
     * Generate a model class for the given entity.
     */
    protected function generateFactory(Entity $entity): void
    {
        $entityName = $entity->getName();

        $className = $entityName.'Factory';
        $namespace = 'Database\Factories';

        $fakerFields = $this->buildFakerFields($entity);

        $stub = <<<PHP
        <?php

        namespace $namespace;

        use App\Models\\$entityName;
        use Illuminate\Database\Eloquent\Factories\Factory;

        class $className extends Factory
        {
            protected \$model = $entityName::class;

            public function definition(): array
            {
                return [
        $fakerFields
                ];
            }
        }
        PHP;

        $path = database_path("factories/{$className}.php");
        $this->files->put($path, $stub);
    }

    /**
     * Generate a seeder class for the given entity.
     */
    protected function generateSeeder(Entity $entity): void
    {
        $entityName = $entity->getName();
        $seedCount = $this->config->dev->seedCount;
        $className = $entityName.'Seeder';
        $namespace = 'Database\Seeders';

        $hasManyLines = [];
        $pivotLines = [];

        foreach ($entity->getRelations() as $relation) {
            if ($relation->isHasMany()) {
                // Generate hasMany relation seeder code
            }

            if ($relation->isBelongsToMany()) {
                // Generate belongsToMany relation seeder code
            }
        }

        $relationsCode = implode("\n\n", array_merge($hasManyLines, $pivotLines));

        $stub = <<<PHP
<?php

namespace $namespace;

use Illuminate\Database\Seeder;
use App\Models\\$entityName;

class $className extends Seeder
{
    public function run(): void
    {
        \$items = $entityName::factory()->count({$seedCount})->create();

$relationsCode
    }
}
PHP;

        $path = $this->seedersPath."/{$className}.php";
        $this->files->put($path, $stub);

        // Log the seeder creation
        $pathRelative = str_replace($this->seedersPath.'/', '', $path);
        Log::channel('magic')->info("Seeder created: {$pathRelative}");

        // Insert call to DatabaseSeeder
        $this->insertSeederCall($className);

        // Generate pivot seeders for belongsToMany
        foreach ($entity->getRelations() as $relation) {
            if ($relation->isBelongsToMany()) {
                $this->generatePivotSeeder($entity, $relation);
            }
        }
    }

    /**
     * Generate a seeder for belongsToMany pivot tables
     */
    protected function generatePivotSeeder(Entity $entity, $relation): void
    {

        $pivotTable = $relation->getPivotName();
        $seedCount = $this->config->dev->seedCount;

        // Skip if we already generated this pivot table seeder
        if (in_array($pivotTable, $this->generatedPivotSeeders)) {
            return;
        }

        $this->generatedPivotSeeders[] = $pivotTable;

        $entityName = $entity->getName();
        $relatedEntity = $relation->getEntityName();
        $relationMethod = $relation->getRelationName();

        $seederClass = \Str::studly($pivotTable).'PivotSeeder';
        $namespace = 'Database\Seeders';

        $stub = <<<PHP
<?php

namespace $namespace;

use Illuminate\Database\Seeder;
use App\Models\\$entityName;
use App\Models\\$relatedEntity;

class $seederClass extends Seeder
{
    public function run(): void
    {
        \$items = $entityName::all();
        \$relatedItems = $relatedEntity::all();

        foreach (\$items as \$item) {
            \$item->{$relationMethod}()->attach(
                \$relatedItems->random(rand(1, {$seedCount}))->pluck('id')->toArray()
            );
        }
    }
}
PHP;

        $path = $this->seedersPath."/{$seederClass}.php";
        $this->files->put($path, $stub);

        // Log the pivot seeder creation
        $pathRelative = str_replace($this->seedersPath.'/', '', $path);
        Log::channel('magic')->info("Pivot seeder created: {$pathRelative}");

        // Append pivot seeder call at the **end** of DatabaseSeeder
        $filePath = $this->seedersPath.'/DatabaseSeeder.php';
        CodeGenerationHelper::appendCodeBlock(
            $filePath,
            'run',
            [
                "// Pivot seeder for {$entityName} <-> {$relatedEntity}",
                "\$this->call({$seederClass}::class);",
            ],
            'pivot_seeders'
        );
    }

    /**
     * Insert a call to the seeder in the DatabaseSeeder class.
     */
    private function insertSeederCall($seederClass)
    {

        $filePath = $this->seedersPath.'/DatabaseSeeder.php';

        CodeGenerationHelper::appendCodeBlock(
            $filePath,
            'run',
            [
                "// Call the {$seederClass} seeder",
                "\$this->call({$seederClass}::class);",
            ],
            'seeders',
        );
    }

    /**
     * Guess the Faker type based on the field type.
     */
    protected function buildFakerFields(Entity $entity): string
    {
        $lines = [];

        foreach ($entity->getFields() as $field) {
            if (in_array($field->getName(), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Check if field is a belongsTo relation
            $belongsTo = collect($entity->getRelations())
                ->first(fn ($rel) => $rel->isBelongsTo() && $rel->getForeignKey() === $field->getName());

            if ($belongsTo) {
                $relatedEntity = $belongsTo->getEntityName();
                $lines[] = "            '{$field->getName()}' => \\App\\Models\\{$relatedEntity}::inRandomOrder()->first()?->id ?? \\App\\Models\\{$relatedEntity}::factory(),";

                continue;
            }

            $fakerType = $this->guessFakerType($field);
            $lines[] = "            '{$field->getName()}' => \$this->faker->{$fakerType},";
        }

        return implode("\n", $lines);
    }

    /**
     * Guess the Faker type based on the field type and name.
     */
    protected function guessFakerType(Field $field): string
    {
        $name = strtolower($field->getName());
        $type = strtolower($field->getType());

        $mappings = config('magic.faker_mappings', []);

        foreach ($mappings as $key => $faker) {
            if (str_starts_with($key, 'type:')) {
                // Type-based match
                $expectedType = substr($key, 5);
                if ($type === $expectedType) {
                    return $faker;
                }
            } else {
                // Name-based partial match
                if (str_contains($name, $key)) {
                    return $faker;
                }
            }
        }

        // Check for date fields
        if ($field->isDate()) {
            return 'date()'; // default date format
        }

        // Check for datetime fields
        if ($field->isDateTime()) {
            return 'dateTime()'; // default datetime format
        }

        // Check if the field is enum
        if ($field->isEnum()) {
            return 'randomElement('.json_encode($field->getValues()).')';
        }

        // Default fallback if no match found

        return 'word()'; // default
    }

    /**
     * Generate the seeder for creating an admin user.
     */
    private function generateAdminUserSeeder()
    {
        // Generate creating of admin user
        CodeGenerationHelper::appendCodeBlock(
            $this->seedersPath.'/DatabaseSeeder.php',
            'run',
            [
                '// Create admin user',
                "User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);",
            ],
            'seeders'
        );
    }
}
