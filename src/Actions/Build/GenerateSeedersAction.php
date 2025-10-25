<?php

namespace Glugox\Magic\Actions\Build;

use Faker\Factory;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\CodeGenerationHelper;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Faker\FakerExtension;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_seeders',
    description: 'Generates Laravel factories and seeders (including pivot seeders) based on the given entity configuration.',
    parameters: ['config' => 'The parsed configuration object (Config) containing entities, fields, and relations']
)]
class GenerateSeedersAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * The build context containing the configuration and other relevant data.
     */
    protected BuildContext $context;

    /**
     * Paths for factories
     */
    protected string $factoriesPath;

    /**
     * Paths for seeders
     */
    protected string $seedersPath;

    /**
     * Keep track of generated pivot seeders to avoid duplicates
     */
    protected array $generatedPivotSeeders = [];

    /**
     * Constructor to set up paths.
     */
    public function __construct(protected CodeGenerationHelper $codeHelper)
    {
        // Ensure the factories directory exists
        $this->factoriesPath = MagicPaths::database('factories');
        if (! File::exists($this->factoriesPath)) {
            File::makeDirectory($this->factoriesPath, 0755, true);
        }

        // Ensure the seeders directory exists
        $this->seedersPath = MagicPaths::database('seeders');
        if (! File::exists($this->seedersPath)) {
            File::makeDirectory($this->seedersPath, 0755, true);
        }
    }

    /**
     * Build all seeders based on the configuration.
     */
    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        // Store context for later use
        $this->context = $context;

        // Add seeding code to create admin user
        $this->generateAdminUserSeeder();

        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateFactory($entity);
            if ($context->getConfig()->app->seedEnabled) {
                $this->generateSeeder($entity);
            }
        }

        return $this->context;
    }

    /**
     * If no FakerExtension is found, we can try to guess the best method based on field name and type.
     */
    public function guessFakerMethod(\Faker\Generator $faker, Field $field): string
    {
        $name = mb_strtolower($field->name);
        $mapFromConfig = config('magic.faker_mappings', []);
        $mapFromJsonConfig = $this->context->getConfig()->app->fakerMappings ?? [];
        $map = array_merge($mapFromConfig, $mapFromJsonConfig);

        // If the name of the field contains a word that is associated with a Faker method,
        // we can use that method directly. For example, if the field name is "order_number",
        // we can use the "number" part to determine the Faker method.
        $wordAssocToType = [
            'color' => 'hexColor()',
            'text' => 'text()',
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
            return 'randomElement('.json_encode($field->getOptionsNames()).')';
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
                $typeInConfig = mb_substr($mapKey, 5);
                $typeFallbacks[mb_strtolower($typeInConfig)] = $item;
            }
        }

        // If the type is not found in the map, use a default type
        $fallbackValue = $typeFallbacks[mb_strtolower($typeStr)] ?? 'word';
        Log::channel('magic')->info("Using fallback Faker mapping for field '{$field->name}': {$typeStr} which is: {$fallbackValue}");

        return $fallbackValue;
    }

    /**
     * Generate Factory using stub file.
     */
    protected function generateFactory(Entity $entity): void
    {
        $entityName = $entity->getName();
        $className = "{$entityName}Factory";
        $namespace = 'Database\Factories';
        $fakerFields = $this->buildFakerFields($entity);

        $content = StubHelper::loadStub('database/factory.stub', [
            'namespace' => $namespace,
            'entity' => $entityName,
            'class' => $className,
            'fakerFields' => $fakerFields,
        ]);

        $path = MagicPaths::database("factories/{$className}.php");
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate Seeder using stub file.
     */
    protected function generateSeeder(Entity $entity): void
    {
        $entityName = $entity->getName();
        $className = "{$entityName}Seeder";
        $namespace = 'Database\\Seeders';
        $seedCount = $this->context->getConfig()->app->seedCount;

        $hasManyLines = [];
        $pivotLines = [];
        $useStatements = ['use '.MagicNamespaces::models($entityName).';'];

        foreach ($entity->getRelations() as $relation) {

            if ($relation->isBelongsTo()) {
                $relatedEntity = $relation->getRelatedEntityName(); // e.g., Order
                $foreignKey = $relation->getForeignKey();          // e.g., order_id

                // Now you know this field must be unique
            }

            if ($relation->isHasMany()) {
                // Add custom logic here later if needed
            }

            if ($relation->isBelongsToMany()) {
                // Add custom logic here later if needed
            }
        }

        $relationsCode = implode("\n\n", array_merge($hasManyLines, $pivotLines));

        // Unique or regular factory code
        $uniqueRelations = collect($entity->getRelations())
            ->map(fn (Relation $relation) => $relation->getInverseRelation())
            ->filter(fn ($rel) => $rel && $rel->isHasOne())
            ->values();
        $factoryCode = '';
        if ($uniqueRelations->isNotEmpty()) {
            $lines = [];
            foreach ($uniqueRelations as $rel) {

                // We will use getLocalEntityName down below just because the relations are inversed
                $parentVar = '$'.Str::camel($rel->getLocalEntityName()).'s';
                $foreignKey = $rel->getForeignKey();

                $useStatements[] = 'use '.MagicNamespaces::models($rel->getLocalEntityName()).';';
                // $useStatements[] = "use App\\Models\\{$entity->getClassName()};";

                // Fetch all parent entities
                $lines[] = "{$parentVar} = {$rel->getLocalEntityName()}::all();";
                $lines[] = "foreach ({$parentVar} as \$parent) {";
                $lines[] = "    {$entity->getClassName()}::factory()->create([";
                $lines[] = "        '{$foreignKey}' => \$parent->id,";
                $lines[] = '    ]);';
                $lines[] = '}';
            }

            $factoryCode = implode("\n", $lines);
        } else {
            // Standard seeding
            $factoryCode = "\${$entity->getClassName()}Items = {$entity->getClassName()}::factory()->count({$seedCount})->create();";
        }

        $useStatements = array_values(array_unique($useStatements));
        $useStatementsBlock = implode("\n", $useStatements);
        if ($useStatementsBlock !== '') {
            $useStatementsBlock .= "\n";
        }

        $content = StubHelper::loadStub('database/seeder.stub', [
            'namespace' => $namespace,
            'class' => $className,
            'entity' => $entityName,
            'seedCount' => $seedCount,
            'factoryCode' => $factoryCode,
            'relationsCode' => '',
            'useStatements' => $useStatementsBlock,
        ]);

        $path = $this->seedersPath."/{$className}.php";
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);

        Log::channel('magic')->info("Seeder created: {$className}.php");

        $this->insertSeederCall($className);

        foreach ($entity->getRelations() as $relation) {
            if ($relation->requiresPivotTable()) {
                $this->generatePivotSeeder($entity, $relation);
            }
        }
    }

    /**
     * Generate Pivot Seeder using stub file.
     */
    protected function generatePivotSeeder(Entity $entity, Relation $relation): void
    {
        $pivotTable = $relation->getPivotName();
        if (in_array($pivotTable, $this->generatedPivotSeeders)) {
            return;
        }

        $this->generatedPivotSeeders[] = $pivotTable;

        $entityName = $entity->getName();
        $relatedEntity = $relation->getRelatedEntityName();
        $relationMethod = $relation->getRelationName();
        $seederClass = Str::studly($pivotTable).'PivotSeeder';
        $namespace = 'Database\\Seeders';
        $seedCount = $this->context->getConfig()->app->seedCount;

        $content = StubHelper::loadStub('database/pivot-seeder.stub', [
            'namespace' => $namespace,
            'entity' => $entityName,
            'relatedEntity' => $relatedEntity,
            'class' => $seederClass,
            'relationMethod' => $relationMethod,
            'seedCount' => $seedCount,
            'entityUse' => 'use '.MagicNamespaces::models($entityName).';',
            'relatedEntityUse' => 'use '.MagicNamespaces::models($relatedEntity).';',
        ]);

        $path = $this->seedersPath."/{$seederClass}.php";
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);

        Log::channel('magic')->info("Pivot seeder created: {$seederClass}.php");

        $filePath = $this->seedersPath.'/DatabaseSeeder.php';
        $this->codeHelper->appendCodeBlock(
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
     * Build Faker fields for factory stub.
     */
    protected function buildFakerFields(Entity $entity): string
    {
        $lines = [];
        $typesNotForFaker = [FieldType::JSON, FieldType::JSONB, FieldType::FILE];
        $modelPresets = config('magic.model_presets', []);

        Log::channel('magic')->info("Building Faker fields for entity: {$entity->getName()}");

        if (isset($modelPresets[$entity->getName()])) {
            Log::channel('magic')->info("Using model preset for entity: {$entity->getName()}");
            $preset = $modelPresets[$entity->getName()]['default_fields'];
            if (is_array($preset)) {
                foreach ($preset as $fieldName => $presetValue) {
                    $entity->addFieldIfNotExists($presetValue);
                }
            }
        }

        foreach ($entity->getFields() as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            Log::channel('magic')->info("Processing field: {$field->name} of type {$field->type->value}");

            /** @var Relation $belongsTo */
            $belongsTo = collect($entity->getRelations())
                ->first(fn ($rel) => $rel->isBelongsTo() && $rel->getForeignKey() === $field->name);

            if ($belongsTo) {
                $relatedEntity = $belongsTo->getRelatedEntityName();
                $relatedFqcn = '\\'.MagicNamespaces::models($relatedEntity);
                $lines[] = "            '{$field->name}' => {$relatedFqcn}::inRandomOrder()->first()?->id ?? {$relatedFqcn}::factory(),";

                continue;
            }

            $fakerExtension = FakerExtension::getExtensionByField($field);
            if ($fakerExtension) {
                $lines[] = "            '{$field->name}' => {$fakerExtension->handle(Factory::create())},";

                continue;
            }

            if (in_array($field->type, $typesNotForFaker)) {
                $lines[] = "            '{$field->name}' => null, // Not suitable for Faker";

                continue;
            }

            $uniqueSuffix = $field->unique ? '->unique()' : '';

            $fakerType = $this->guessFakerType($field);
            $finalMethodCode = $this->ensureChecksInFakerType($fakerType, $field);
            $lines[] = "            '{$field->name}' => \$this->faker{$uniqueSuffix}->{$finalMethodCode},";
        }

        return implode("\n", $lines);
    }

    protected function guessFakerType(Field $field): string
    {
        return $this->guessFakerMethod(Factory::create(), $field);
    }

    private function insertSeederCall($seederClass): void
    {
        $filePath = $this->seedersPath.'/DatabaseSeeder.php';
        $this->codeHelper->appendCodeBlock(
            $filePath,
            'run',
            [
                "// Call the {$seederClass} seeder",
                "\$this->call({$seederClass}::class);",
            ],
            'seeders'
        );
    }

    private function generateAdminUserSeeder(): void
    {
        $this->codeHelper->appendCodeBlock(
            $this->seedersPath.'/DatabaseSeeder.php',
            'run',
            [
                '// Create admin user',
                "User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);",
            ],
            'seeders'
        );
    }

    private function ensureChecksInFakerType(string $fakerType, Field $field): string
    {
        if ($field->isNumeric()) {
            $min = $field->min ?? 0;
            $max = $field->max ?? ($min + 1000);
            $fakerType = str_replace('randomNumber()', "numberBetween({$min}, {$max})", $fakerType);
        }

        if (in_array($field->type, [FieldType::FLOAT, FieldType::DOUBLE, FieldType::DECIMAL])) {
            $min = $field->min ?? 0;
            $max = $field->max ?? $min + 1000;
            $fakerType = str_replace('randomFloat()', "randomFloat(2, {$min}, {$max})", $fakerType);
        }

        if ($field->type === FieldType::DATE) {
            $min = $field->min ?? 'now';
            $max = $field->max ?? '+1 year';
            $fakerType = str_replace('date()', "dateTimeBetween('{$min}', '{$max}')", $fakerType);
        }

        if ($field->type === FieldType::DATETIME) {
            $min = $field->min ?? 'now';
            $max = $field->max ?? '+1 year';
            $fakerType = str_replace('dateTime()', "dateTimeBetween('{$min}', '{$max}')", $fakerType);
        }

        return $fakerType;
    }
}
