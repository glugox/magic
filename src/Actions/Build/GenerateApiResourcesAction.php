<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

#[ActionDescription(
    name: 'generate_api_resources',
    description: 'Generates API Resource and Collection classes for all entities.'
)]
class GenerateApiResourcesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected BuildContext $context;

    protected string $stubsPath;

    public function __construct()
    {
        // adjust if your stubs live in different folder
        $this->stubsPath = __DIR__.'/../../../stubs/api-resources';
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        $this->context = $context;

        $this->generateResources();

        return $this->context;
    }

    protected function generateResources(): void
    {
        // required stubs
        $resourceStubPath = $this->stubsPath.'/api-resource.stub';
        $collectionStubPath = $this->stubsPath.'/api-resource-collection.stub';

        if (! File::exists($resourceStubPath)) {
            throw new RuntimeException("Missing stub: {$resourceStubPath}");
        }
        if (! File::exists($collectionStubPath)) {
            throw new RuntimeException("Missing stub: {$collectionStubPath}");
        }

        $resourceStub = File::get($resourceStubPath);
        $collectionStub = File::get($collectionStubPath);

        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateResource($entity, $resourceStub);
            $this->generateResourceCollection($entity, $collectionStub);
        }
    }

    protected function generateResource(Entity $entity, string $resourceStub): void
    {
        $resourceClass = Str::studly(Str::singular($entity->getName())).'Resource';
        $modelClassFull = $entity->getFullyQualifiedModelClass();

        // Determine fields to include in resource
        // Prefer table-visible fields (skip relation columns)
        $fields = $entity->getFormFieldsNames();
        if (empty($fields)) {
            // fallback: use all declared name fields or id
            $fields = $entity->getNameFieldsNames() ?: ['id'];
        }

        // Build lines for fields
        $fieldsLines = [];
        foreach ($fields as $f) {
            $fieldsLines[] = "            '{$f}' => \$this->{$f},";
        }
        $fieldsBlock = implode("\n", $fieldsLines);

        // Ensure 'name' present: check if field exists or model has getNameAttribute
        $hasNameColumn = in_array('name', $fields);
        $hasNameAccessor = false;
        if ($modelClassFull && class_exists($modelClassFull)) {
            // method_exists on class name checks if accessor method exists
            $hasNameAccessor = method_exists($modelClassFull, 'getNameAttribute');
        }
        $nameLine = '';
        if (! $hasNameColumn && $hasNameAccessor) {
            $nameLine = "            'name' => \$this->name,";
        }

        // Build lines for relations
        $relationsLines = [];
        foreach ($entity->getRelations(RelationType::BELONGS_TO) as $relation) {
            $relatedEntity = $relation->getRelatedEntity();
            $relatedResourceClass = Str::studly(Str::singular($relatedEntity->getName())).'Resource';
            $relationsLines[] = "            '{$relation->getRelationName()}' => new {$relatedResourceClass}(\$this->whenLoaded('{$relation->getRelationName()}')),";
        }
        $relationsFieldsBlock = implode("\n", $relationsLines);

        // Prepare replacements (clear and explicit)
        $replacements = [
            '{{resourceClass}}' => $resourceClass,
            '{{fields}}' => $fieldsBlock,
            '{{nameField}}' => $nameLine,
            '{{relationFields}}' => $relationsFieldsBlock
        ];

        $content = StubHelper::applyReplacements($resourceStub, $replacements);

        // Ensure directory exists
        $dir = app_path('Http/Resources');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir.'/'.$resourceClass.'.php';
        app(GenerateFileAction::class)($filePath, $content);
        $this->context->registerGeneratedFile($filePath);

        Log::channel('magic')->info("API Resource created: {$resourceClass}");
    }

    protected function generateResourceCollection(Entity $entity, string $collectionStub): void
    {
        $resourceClass = Str::studly(Str::singular($entity->getName())).'Resource';
        $collectionClass = Str::studly(Str::singular($entity->getName())).'Collection';

        $replacements = [
            '{{resourceCollectionClass}}' => $collectionClass,
            '{{resourceClass}}' => $resourceClass,
        ];

        $content = StubHelper::applyReplacements($collectionStub, $replacements);

        // Ensure directory exists
        $dir = app_path('Http/Resources');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir.'/'.$collectionClass.'.php';
        app(GenerateFileAction::class)($filePath, $content);
        $this->context->registerGeneratedFile($filePath);

        Log::channel('magic')->info("API Resource Collection created: {$collectionClass}");
    }
}
