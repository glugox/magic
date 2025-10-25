<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_crud_tests',
    description: 'Generates Pest CRUD tests for all entities defined in the configuration.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateCrudTestsAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * Context with config
     */
    protected BuildContext $context;

    /**
     * Tests path (e.g., tests)
     */
    protected string $testsPath;

    /**
     * Path to stubs
     */
    private string $stubsPath;

    /**
     * Constructor
     */
    public function __construct(protected ValidationHelper $validationHelper)
    {
        $this->stubsPath = __DIR__.'/../../../stubs';
        $this->testsPath = MagicPaths::tests('Browser');
        if (! File::exists($this->testsPath)) {
            File::makeDirectory($this->testsPath, 0755, true);
        }
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        $this->context = $context;

        $this->generatetests();

        return $this->context;
    }

    /**
     * Generate tests for all entities defined in the config.
     */
    public function generateTests(): void
    {
        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateCrudTestForEntity($entity);
        }
    }

    /**
     * Generate CRUD tests for a given entity.
     */
    protected function generateCrudTestForEntity(Entity $entity): void
    {
        $uses = [];
        $modelClass = $entity->getClassName();
        $modelClassFull = $entity->getFullyQualifiedModelClass();
        $modelHref = $entity->getHref(); // e.g., '/users'
        $modelClassPlural = Str::plural($modelClass);
        $indexPageTitle = $entity->getIndexPageTitle();
        $testClass = Str::studly(Str::singular($entity->getName())).'Test';
        $belongsToRels = $entity->getRelations(RelationType::BELONGS_TO);

        $usesFqcns = array_map(function (Relation $rel) {
            // If entity is User, skip it
            return $rel->getRelatedEntity()->getFullyQualifiedModelClass();
        }, $belongsToRels);

        // Remove if there is User model
        $usesFqcns = array_filter($usesFqcns, function ($fqn) {
            return ! str_ends_with($fqn, '\User');
        });

        $uses = array_map(function (string $useClass) {
            return "use {$useClass};";
        }, array_unique($usesFqcns));

        // Pest browser test's filling code when creating a record
        $formFieldsCodeCreate = StubHelper::writePestFormFields($entity);
        $runFactoriesCode = StubHelper::writeFactoriesCallFor($entity);

        // Table ( db ) name
        $modelDbTable = $entity->getTableName();
        $usesStr = implode("\n", $uses);

        $stubPath = $this->stubsPath.'/tests/crud-test.stub';
        $template = StubHelper::replaceBaseNamespace(File::get($stubPath));

        $replacements = [
            '{{classDescription}}' => "CRUD test for model {$entity->getSingularName()}",
            '{{modelClassFull}}' => $modelClassFull,
            '{{modelClass}}' => $modelClass,
            '{{modelClassPlural}}' => $modelClassPlural,
            '{{modelClassLower}}' => Str::lower($modelClass),
            '{{modelHref}}' => $modelHref,
            '{{indexPageTitle}}' => $indexPageTitle,
            '{{testClass}}' => $testClass,
            '{{modelDbTable}}' => $modelDbTable,
            '{{formFieldsCodeCreate}}' => $formFieldsCodeCreate,
            '{{uses}}' => $usesStr,
            '{{runFactoriesCode}}' => $runFactoriesCode,
            // '{{folderName}}' => $entity->getFolderName()
        ];

        $template = str_replace(array_keys($replacements), array_values($replacements), $template);

        $filePath = $this->testsPath.'/'.$testClass.'.php';
        app(GenerateFileAction::class)($filePath, $template);
        $this->context->registerGeneratedFile($filePath);

        $relPath = str_replace(MagicPaths::tests().DIRECTORY_SEPARATOR, '', $filePath);
        Log::channel('magic')->info("test created: {$relPath}");
    }
}
