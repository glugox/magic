<?php

namespace Glugox\Magic\Services;

use Faker\Factory;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Faker\FakerExtension;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SeederBuilderService
{
    protected string $factoriesPath;

    protected string $seedersPath;

    protected array $generatedPivotSeeders = []; // track already generated pivot seeders

    private ?array $fakerMethods = null;

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
    private function insertSeederCall($seederClass): void
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
        $typesNotForFaker = [FieldType::JSON, FieldType::JSONB, FieldType::FILE];

        Log::channel('magic')->info("Building Faker fields for entity: {$entity->getName()}");

        foreach ($entity->getFields() as $field) {

            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Enforce seeder rulers from config
            if ($field->type === FieldType::PASSWORD && ! $this->config->dev->strongPasswords) {
                Log::channel('magic')->info("Using simple password for field '{$field->name}' in entity '{$entity->getName()}'");
                $passwordHash = config('magic.default_password_hash', '$2y$12$00A.1FrCk3FctOEVIHlkLu5qYNfFdBGJUCyzdMaGcvC9CPTgPoIgK');

                $lines[] = "            '{$field->name}' => '$passwordHash', // Simple password for testing ('password')";

                continue;
            }

            // Check if field is a belongsTo relation
            /** @var Relation $belongsTo */
            $belongsTo = collect($entity->getRelations())
                ->first(fn ($rel) => $rel->isBelongsTo() && $rel->getForeignKey() === $field->name);

            if ($belongsTo) {
                $relatedEntity = $belongsTo->getEntityName();
                $lines[] = "            '{$field->name}' => \\App\\Models\\{$relatedEntity}::inRandomOrder()->first()?->id ?? \\App\\Models\\{$relatedEntity}::factory(),";

                continue;
            }

            // Check for available Faker extensions
            $fakerExtension = FakerExtension::getExtensionByField($field);
            if ($fakerExtension) {
                Log::channel('magic')->info("Using Faker extension for field '{$field->name}' in entity '{$entity->getName()}'");
                $lines[] = "            '{$field->name}' => {$fakerExtension->handle(Factory::create())},";

                continue;
            }

            // Skip fields that are not suitable for Faker
            if (in_array($field->type, $typesNotForFaker)) {
                Log::channel('magic')->warning("Field '{$field->name}' of type '{$field->type->value}' is not suitable for Faker. Skipping.");
                $lines[] = "            '{$field->name}' => null, // Not suitable for Faker";

                continue;
            }

            $fakerType = $this->guessFakerType($field);
            $finalMethodCode = $this->ensureChecksInFakerType($fakerType, $field);

            $lines[] = "            '{$field->name}' => \$this->faker->{$finalMethodCode},";
        }

        return implode("\n", $lines);
    }

    /**
     * Guess the Faker type based on the field type and name.
     */
    protected function guessFakerType(Field $field): string
    {
        return $this->guessFakerMethod(Factory::create(), $field);
    }

    /**
     * Generate the seeder for creating an admin user.
     */
    private function generateAdminUserSeeder(): void
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

    public function guessFakerMethod(\Faker\Generator $faker, Field $field): string
    {

        $name = strtolower($field->name);
        $mapFromConfig = config('magic.faker_mappings', []);
        $mapFromJsonConfig = $this->config->dev->fakerMappings ?? [];
        $map = array_merge($mapFromConfig, $mapFromJsonConfig);

        // If the name of the field contains a word that is associated with a Faker method,
        // we can use that method directly. For example, if the field name is "order_number",
        // we can use the "number" part to determine the Faker method.
        $wordAssocToType = [
            'color' => 'hexColor()',
            'text' => 'text()',
            'email' => 'safeEmail()',
            'phone' => 'phoneNumber()',
            'address' => 'address()',
            'location' => 'address()',
            'name' => 'name()',
            'first_name' => 'firstName()',
            'last_name' => 'lastName()',
            'full_name' => 'name()',
            'image' => 'imageUrl(200, 200, null, true)', // true for random image
            'username' => 'userName()',
            'password' => 'password()',
            'title' => 'sentence(3)',
            'description' => 'paragraph()',
            'content' => 'text()',
            'comment' => 'sentence()',
            'body' => 'paragraph()',
            'city' => 'city()',
            'country' => 'country()',
            'postal' => 'postcode()',
            'url' => 'url()',
        ];

        $typeStr = $field->type->value;

        // 1. Check if the field name matches a predefined mapping
        if (isset($map[$name])) {
            Log::channel('magic')->info("Using predefined Faker mapping for field '{$field->name}': {$map[$name]}");

            return $map[$name];
        }

        // 2. For enum types, use the enum values directly because they are always set in json config
        // Check if the field is enum
        if ($field->isEnum()) {
            return 'randomElement('.json_encode($field->values).')';
        }

        // 3. Check if the field name has type string inside , for example: "order_number"
        // $availableTypes now contains all types like ['string', 'integer', 'boolean', etc.]
        // without the "type:" prefix
        foreach ($wordAssocToType as $word => $availableType) {
            // Check exact match
            if ($name === $word || str_ends_with($name, "_{$word}") || str_starts_with($name, "{$word}_")) {
                Log::channel('magic')->info("Using word association for field '{$field->name}': {$availableType}");

                return $availableType;
            }
        }

        // 4. Although other fields than date can end with "_at", it is kind of a convention
        // to use "dateTime" for fields ending with "_at"
        if (str_ends_with($name, '_at')) {
            Log::channel('magic')->info("Using dateTime mapping for field '{$field->name}'");

            return 'dateTime()';
        }

        // 5. Fallback to type-based mapping
        $typeFallbacks = [];

        foreach ($map as $mapKey => $item) {
            if (str_starts_with($mapKey, 'type:')) {
                $typeInConfig = substr($mapKey, 5);
                $typeFallbacks[strtolower($typeInConfig)] = $item;
            }
        }

        // If the type is not found in the map, use a default type
        $fallbackValue = $typeFallbacks[strtolower($typeStr)] ?? 'word';
        Log::channel('magic')->info("Using fallback Faker mapping for field '{$field->name}': {$typeStr} which is: {$fallbackValue}");

        return $fallbackValue;
    }

    /**
     * Ensure the Faker type is valid and add any necessary checks.
     */
    private function ensureChecksInFakerType(string $fakerType, Field $field): string
    {
        // Numeric integer types
        if ($field->isNumeric()) {
            $min = $field->min ?? 0;
            $max = $field->max ?? $min + 1000; // fallback if max not defined
            $fakerType = str_replace('randomNumber()', "numberBetween({$min}, {$max})", $fakerType);
        }

        // Float / decimal types
        if (in_array($field->type, [FieldType::FLOAT, FieldType::DOUBLE, FieldType::DECIMAL])) {
            $min = $field->min ?? 0;
            $max = $field->max ?? $min + 1000;
            $fakerType = str_replace('randomFloat()', "randomFloat(2, {$min}, {$max})", $fakerType);
        }

        // Date types
        if ($field->type === FieldType::DATE) {
            $min = $field->min ?? 0;
            $max = $field->max ?? '+1 year';
            $fakerType = str_replace('date()', "dateBetween('{$min}', '{$max}')", $fakerType);
        }

        // Datetime types
        if ($field->type === FieldType::DATETIME) {
            $min = $field->min ?? 'now';
            $max = $field->max ?? '+1 year';
            $fakerType = str_replace('dateTime()', "dateTimeBetween('{$min}', '{$max}')", $fakerType);
        }

        return $fakerType;
    }
}
