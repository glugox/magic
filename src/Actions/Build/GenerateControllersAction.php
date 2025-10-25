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
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

#[ActionDescription(
    name: 'generate_controllers',
    description: 'Generates controller classes for all entities defined in the given Config.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateControllersAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * Context with config
     */
    protected BuildContext $context;

    /**
     * Controller path (e.g., app/Http/Controllers)
     */
    protected string $controllerPath;

    /**
     * Routes file path (e.g., routes/web.php)
     */
    protected string $routesFilePath;

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
        $this->controllerPath = MagicPaths::app('Http/Controllers');
        $this->routesFilePath = MagicPaths::routes('app.php');
        if (! File::exists($this->controllerPath)) {
            File::makeDirectory($this->controllerPath, 0755, true);
        }
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        $this->context = $context;

        $this->generateControllers();
        $this->generateRoutes();

        return $this->context;
    }

    /**
     * Generate controllers for all entities defined in the config.
     */
    public function generateControllers(): void
    {
        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateController($entity);
            $this->generateApiController($entity);

            // Generate relation controllers if needed
            foreach ($entity->getRelations() as $relation) {
                $this->generateRelationControllers($entity, $relation);
            }
        }
    }

    public function generateRelationControllers(Entity $entity, Relation $relation): void
    {
        if (! $relation->hasRoute()) {
            return;
        }

        $template = $this->buildRelationControllers($entity, $relation);
        if (empty($template)) {
            return;
        }

        // Example file path: app/Http/Controllers/User/UserRolesController.php
        $filePath = $this->controllerPath.'/'.$entity->getSingularName().'/'.Str::studly($entity->getName()).Str::studly($relation->getRelatedEntityName()).'Controller.php';
        app(GenerateFileAction::class)($filePath, $template);
        $this->context->registerGeneratedFile($filePath);
    }

    /**
     * Each resource, eg. User, can have relations. In order to manage them,
     * we will have to generate additional controllers.
     * For example, if User hasMany Posts, we need a UserPostsController.
     * This method will generate such controllers.
     */
    public function buildRelationControllers(Entity $entity, Relation $relation): string
    {
        $relatedEntityName = $relation->getRelatedEntityName();
        if (! $relatedEntityName) {
            return '';
        }

        $relatedEntity = $this->context->getConfig()->getEntityByName($relatedEntityName);
        if (! $relatedEntity) {
            return '';
        }

        $parentModelClass = $entity->getClassName();
        $parentModelClassLower = Str::camel($parentModelClass);
        $relatedModelClass = $relatedEntity->getClassName();
        $relatedModelClassLower = Str::lower($relatedModelClass);
        $relatedTableName = $relatedEntity->getTableName();
        $controllerClass = Str::studly($entity->getName()).Str::studly($relatedEntity->getSingularName()).'Controller';
        $relationName = $relation->getRelationName();
        $parentModelFolderName = $entity->getFolderName();

        // Add resource class names
        $relatedModelResourceClass = Str::studly($relatedModelClass).'Resource';
        $parentModelResourceClass = Str::studly($parentModelClass).'Resource';

        // Add selected IDs for belongsToMany / morphToMany
        $selectedIdsCode = in_array($relation->getType(), [RelationType::BELONGS_TO_MANY, RelationType::MORPH_MANY])
            ? '$'.$parentModelClassLower.'->'.$relationName.'->pluck(\'id\')'
            : 'null';

        // Convert relation type enum to kebab-case for stub file
        $relationType = $relation->type->value ?? null;
        if (! $relationType) {
            return '';
        }

        $stubFile = Str::kebab($relationType).'.stub';
        $stubPath = $this->stubsPath."/controllers/relation/{$stubFile}";

        if (! File::exists($stubPath)) {
            return '';
        }
        $stub = StubHelper::replaceBaseNamespace(File::get($stubPath));

        $replacements = [
            '{{classDescription}}' => "Controller for managing {$relatedEntity->getPluralName()} related to a {$entity->getSingularName()} ( {$relation->getType()->value} )",
            '{{entitySingularName}}' => $entity->getSingularName(),
            '{{parentModelClassFull}}' => $entity->getFullyQualifiedModelClass(),
            '{{relatedModelClassFull}}' => $relatedEntity->getFullyQualifiedModelClass(),
            '{{controllerClass}}' => $controllerClass,
            '{{parentModelClass}}' => $parentModelClass,
            '{{parentModelClassLower}}' => $parentModelClassLower,
            '{{relationName}}' => $relationName,
            '{{parentModelFolderName}}' => $parentModelFolderName,
            '{{relatedModelClass}}' => $relatedModelClass,
            '{{relatedModelClassLower}}' => $relatedModelClassLower,
            '{{relatedTableName}}' => $relatedTableName,
            '{{foreignKey}}' => $relation->getForeignKey(),
            '{{relatedModelResourceClass}}' => $relatedModelResourceClass,
            '{{parentModelResourceClass}}' => $parentModelResourceClass,
            '{{selectedIdsCode}}' => $selectedIdsCode,
            '{{searchQueryString}}' => $this->context->getConfig()->getConfigValue('naming.search_query_string', 'search')
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Generate a controller for a given entity.
     */
    protected function generateController(Entity $entity): void
    {
        $modelClass = $entity->getClassName();
        $modelClassFull = $entity->getFullyQualifiedModelClass();
        $modelClassCamel = Str::camel($modelClass);
        $modelClassPlural = Str::plural($modelClass);
        $controllerClass = Str::studly(Str::singular($entity->getName())).'Controller';

        // Table ( db ) name
        $tableName = $entity->getTableName();

        $stubPath = $this->stubsPath.'/controllers/controller.stub';
        $template = File::get($stubPath);

        // Add Resource imports
        $resourceClass = $modelClass.'Resource';

        $replacements = [
            '{{classDescription}}' => "Controller for managing {$entity->getSingularName()}",
            '{{modelClass}}' => $modelClass,
            '{{modelClassFull}}' => $modelClassFull,
            '{{modelClassCamel}}' => $modelClassCamel,
            '{{modelClassPlural}}' => $modelClassPlural,
            '{{controllerClass}}' => $controllerClass,
            '{{tableName}}' => $tableName,
            '{{folderName}}' => $entity->getFolderName(),
            '{{routeName}}' => $entity->getRouteName(),
            '{{resourceClass}}' => $resourceClass,
            '{{searchQueryString}}' => $this->context->getConfig()->getConfigValue('naming.search_query_string', 'search'),
        ];

        $template = str_replace(array_keys($replacements), array_values($replacements), $template);

        $filePath = $this->controllerPath.'/'.$controllerClass.'.php';
        app(GenerateFileAction::class)($filePath, $template);
        $this->context->registerGeneratedFile($filePath);

        $relPath = str_replace(MagicPaths::app('Http/Controllers/'), '', $filePath);
        Log::channel('magic')->info("Controller created: {$relPath}");
    }

    /**
     * Generate routes for all entities and save to app.php
     * This app.php file will be required by web.php
     *
     * @see ensureWebPhpRequiresAppPhp
     */
    protected function generateRoutes(): void
    {
        // Paths to stubs
        $mainStubPath = $this->stubsPath.'/routes/main.stub';
        $relationDefaultStubPath = $this->stubsPath.'/routes/relation/default.stub';

        if (! File::exists($mainStubPath)) {
            throw new RuntimeException("Missing stub: $mainStubPath");
        }
        if (! File::exists($relationDefaultStubPath)) {
            throw new RuntimeException("Missing stub: $relationDefaultStubPath");
        }

        $mainStub = StubHelper::replaceBaseNamespace(File::get($mainStubPath));
        $relationDefaultStub = StubHelper::replaceBaseNamespace(File::get($relationDefaultStubPath));

        $importedControllers = [];
        $mainRoutes = [];
        $relationRoutes = [];

        // --- Collect main resource routes ---
        foreach ($this->context->getConfig()->entities as $entity) {
            $name = $entity->getRouteName();
            $controllerFQCN = MagicNamespaces::httpControllers(Str::studly(Str::singular($name)).'Controller');
            $controllerShort = class_basename($controllerFQCN);

            if (! in_array($controllerFQCN, $importedControllers)) {
                $importedControllers[] = $controllerFQCN;
            }

            $replacements = [
                '{{routeName}}' => $name,
                '{{controllerClass}}' => $controllerShort,
            ];

            $mainRoutes[] = mb_trim(str_replace(array_keys($replacements), array_values($replacements), $mainStub));
        }

        // --- Collect relation routes ---
        foreach ($this->context->getConfig()->entities as $entity) {
            $relations = $entity->getRelationsWithValidEntity();
            if (empty($relations)) {
                continue;
            }

            $relationRoutes[] = ''; // Blank line before each relation section
            $relationRoutes[] = " // Routes for entity: {$entity->getName()} relations";

            foreach ($relations as $relation) {

                // Check if relation does not have a route (e.g., belongsTo)
                if (! $relation->hasRoute()) {
                    continue;
                }

                // Convert relation type enum to kebab-case for stub file
                /** @var string $relationType */
                $relationType = $relation->type->value ?? null;
                $relationStubFile = Str::kebab($relationType).'.stub';
                $relationStubPath = $this->stubsPath."/routes/relation/{$relationStubFile}";
                if (File::exists($relationStubPath)) {
                    $relationStub = StubHelper::replaceBaseNamespace(File::get($relationStubPath));
                } else {
                    $relationStub = $relationDefaultStub;
                }

                $relatedEntity = $relation->getRelatedEntity();
                $controllerFQCN = ltrim($relation->getControllerFullQualifiedName(), '\\');
                $controllerShort = class_basename($controllerFQCN);

                if (! in_array($controllerFQCN, $importedControllers)) {
                    $importedControllers[] = $controllerFQCN;
                }

                // For many-to-many relations we need updateSelection method
                $updateSelectionRoute = '';
                if (in_array($relation->getType(), [RelationType::BELONGS_TO_MANY, RelationType::MORPH_MANY])) {
                    $updateSelectionRoute = "Route::post('{{entityRouteName}}/{{{entitySingularLower}}}/{{relationRoute}}/updateSelection', [{{controllerClass}}::class, 'updateSelection'])->name('{{entityRouteName}}.{{relationName}}.update-selection');";
                }

                $replacements = [
                    '{{entityRouteName}}' => $entity->getRouteName(),
                    '{{relationName}}' => $relation->getRelationName(),
                    '{{relationRoute}}' => $relatedEntity->getRouteName(),
                    '{{controllerClass}}' => $controllerShort,
                    '{{relationType}}' => $relation->type->value,
                    '{{entitySingularLower}}' => Str::camel(Str::singular($entity->getName())),
                ];

                $currentRelationStub = $relationStub;
                if (! empty($updateSelectionRoute)) {
                    $currentRelationStub .= "\n".$updateSelectionRoute;
                }
                $relationRoutes[] = mb_trim(str_replace(array_keys($replacements), array_values($replacements), $currentRelationStub));
            }
        }

        // --- Generate imports at top ---
        $routeLines = ['<?php', '', "use Illuminate\Support\Facades\Route;"];
        foreach ($importedControllers as $fqcn) {
            $routeLines[] = "use {$fqcn};";
        }

        $routeLines[] = ''; // Blank line before routes

        // Merge main routes WITHOUT extra newlines between them
        $routeLines = array_merge($routeLines, $mainRoutes);

        // Add a single blank line between main routes and relation routes
        if (! empty($relationRoutes)) {
            $routeLines[] = '';
            $routeLines = array_merge($routeLines, $relationRoutes);
        }

        // --- Save generated file ---
        $routesContent = implode("\n", $routeLines)."\n";
        app(GenerateFileAction::class)($this->routesFilePath, $routesContent);
        $this->context->registerGeneratedFile($this->routesFilePath);

        // Generate API routes
        $this->generateApiRoutes();

        Log::channel('magic')->info("Routes generated and saved to: {$this->routesFilePath}");
        $this->ensureWebPhpRequiresAppPhp();
    }

    /**
     * Generate API controller for a given entity.
     */
    protected function generateApiController(Entity $entity): void
    {
        $modelClass = $entity->getClassName();
        $modelClassFull = $entity->getFullyQualifiedModelClass();
        $modelClassCamel = Str::camel($modelClass);
        $controllerClass = Str::studly(Str::singular($entity->getName())).'ApiController';

        // Resource class names
        $resourceClass = $modelClass.'Resource';               // 'UserResource'
        $resourceClassFull = MagicNamespaces::httpResources($resourceClass); // e.g. Vendor\Package\Http\Resources\UserResource

        // Load stub
        $stubPath = $this->stubsPath.'/controllers/api_controller.stub';
        $template = StubHelper::replaceBaseNamespace(File::get($stubPath));

        // Replace placeholders
        $replacements = [
            '{{classDescription}}' => "API Controller for managing {$entity->getSingularName()}",
            '{{modelClass}}' => $modelClass,
            '{{modelClassFull}}' => $modelClassFull,
            '{{modelClassCamel}}' => $modelClassCamel,
            '{{controllerClass}}' => $controllerClass,
            '{{resourceClass}}' => $resourceClass,
            '{{resourceClassFull}}' => $resourceClassFull,
            '{{searchQueryString}}' => $this->context->getConfig()->getConfigValue('naming.search_query_string', 'search'),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Save generated file
        $apiControllerPath = $this->controllerPath.'/Api';
        if (! File::exists($apiControllerPath)) {
            File::makeDirectory($apiControllerPath, 0755, true);
        }

        $filePath = $apiControllerPath.'/'.$controllerClass.'.php';
        app(GenerateFileAction::class)($filePath, $content);
        $this->context->registerGeneratedFile($filePath);

        $relPath = str_replace(MagicPaths::app('Http/Controllers/'), '', $filePath);
        Log::channel('magic')->info("API Controller created: {$relPath}");
    }

    /**
     * Generate API routes for all entities using apiResource with prefixed names
     */
    protected function generateApiRoutes(): void
    {
        $stubPath = $this->stubsPath.'/routes/api.stub';

        if (! File::exists($stubPath)) {
            throw new RuntimeException("Missing stub: $stubPath");
        }

        $apiStub = StubHelper::replaceBaseNamespace(File::get($stubPath));

        $appApiPath = MagicPaths::routes('app/api.php');
        if (! File::exists(dirname($appApiPath))) {
            File::makeDirectory(dirname($appApiPath), 0755, true);
        }

        $routeLines = ['<?php', '', "use Illuminate\Support\Facades\Route;", ''];

        $importedControllers = [];

        foreach ($this->context->getConfig()->entities as $entity) {
            $name = $entity->getRouteName();
            $controllerFQCN = MagicNamespaces::httpControllers('Api\\'.Str::studly(Str::singular($name)).'ApiController');

            // Add use statement only once
            if (! in_array($controllerFQCN, $importedControllers)) {
                $routeLines[] = "use {$controllerFQCN};";
                $importedControllers[] = $controllerFQCN;
            }
        }

        $routeLines[] = ''; // empty line after imports

        foreach ($this->context->getConfig()->entities as $entity) {
            $name = $entity->getRouteName();
            $controllerFQCN = MagicNamespaces::httpControllers('Api\\'.Str::studly(Str::singular($name)).'ApiController');
            $controllerShort = class_basename($controllerFQCN);

            $replacements = [
                '{{routeName}}' => $name,
                '{{controllerClass}}' => $controllerShort,
                '{{entityName}}' => $entity->getName(),
            ];
            $routeLines[] = str_replace(array_keys($replacements), array_values($replacements), $apiStub);

            $routeLines[] = '';
        }

        $routesContent = implode("\n", $routeLines)."\n";
        app(GenerateFileAction::class)($appApiPath, $routesContent);
        $this->context->registerGeneratedFile($appApiPath);

        Log::channel('magic')->info("API routes generated and saved to: {$appApiPath}");

        // Ensure routes/api.php requires routes/app/api.php
        $this->ensureApiPhpRequiresAppApiPhp();
    }

    /**
     * Ensure web.php requires app.php
     */
    protected function ensureWebPhpRequiresAppPhp(): void
    {
        $webPhpPath = MagicPaths::routes('web.php');
        $requireLine = "require __DIR__.'/app.php';";

        if (! File::exists($webPhpPath)) {
            // Create minimal web.php if missing
            app(GenerateFileAction::class)($webPhpPath, "<?php\n\n$requireLine\n");
            Log::channel('magic')->info('Created routes/web.php and added require for app.php');

            return;
        }

        $webPhpContent = File::get($webPhpPath);

        if (! str_contains($webPhpContent, $requireLine)) {
            // Append require line at the end
            File::append($webPhpPath, "\n$requireLine\n");
            $this->context->registerUpdatedFile($webPhpPath);
            Log::channel('magic')->info('Added require to routes/web.php');
        }
    }

    /**
     * Ensure routes/api.php requires app/api.php
     */
    protected function ensureApiPhpRequiresAppApiPhp(): void
    {
        $apiPhpPath = MagicPaths::routes('api.php');
        $requireLine = "require __DIR__.'/app/api.php';";

        if (! File::exists($apiPhpPath)) {
            // Create a new api.php with the PHP tag and require line
            app(GenerateFileAction::class)($apiPhpPath, "<?php\n\n$requireLine\n");
            Log::channel('magic')->info('Created routes/api.php and added require for app/api.php');

            return;
        }

        $apiPhpContent = File::get($apiPhpPath);

        // Ensure <?php tag exists
        if (! str_starts_with(mb_trim($apiPhpContent), '<?php')) {
            $apiPhpContent = "<?php\n\n".$apiPhpContent;
        }

        // Append require line if missing
        if (! str_contains($apiPhpContent, $requireLine)) {
            $apiPhpContent .= "\n$requireLine\n";
            File::put($apiPhpPath, $apiPhpContent);
            $this->context->registerUpdatedFile($apiPhpPath);
            Log::channel('magic')->info('Added require to routes/api.php for app/api.php');
        }
    }
}
