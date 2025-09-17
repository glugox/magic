<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
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
        $this->testsPath = base_path('tests/Browser');
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
        $modelClass = $entity->getClassName();
        $modelClassFull = $entity->getFullyQualifiedModelClass();
        $modelHref = $entity->getHref(); // e.g., '/users'
        $modelClassPlural = Str::plural($modelClass);
        $indexPageTitle = $entity->getIndexPageTitle();
        $testClass = Str::studly(Str::singular($entity->getName())).'Tests';

        // Table ( db ) name
        $modelDbTable = $entity->getTableName();

        $stubPath = $this->stubsPath.'/tests/crud-test.stub';
        $template = File::get($stubPath);

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
            //'{{folderName}}' => $entity->getFolderName()
        ];

        $template = str_replace(array_keys($replacements), array_values($replacements), $template);

        $filePath = $this->testsPath.'/'.$testClass.'.php';
        app(GenerateFileAction::class)($filePath, $template);
        $this->context->registerGeneratedFile($filePath);

        $relPath = str_replace(app_path('tests/'), '', $filePath);
        Log::channel('magic')->info("test created: {$relPath}");
    }
}
