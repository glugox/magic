<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Validation\EntityRuleSet;
use Glugox\Magic\Validation\ValidationRuleSet;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_controllers',
    description: 'Generates controller classes for all entities defined in the given Config.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateControllersAction implements DescribableAction
{
    use AsDescribableAction;

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
     * Constructor
     */
    public function __construct(protected ValidationHelper $validationHelper)
    {
        $this->controllerPath = app_path('Http/Controllers');
        $this->routesFilePath = base_path('routes/app.php');
        if (! File::exists($this->controllerPath)) {
            File::makeDirectory($this->controllerPath, 0755, true);
        }
    }

    public function __invoke(BuildContext $context): BuildContext
    {
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

            // Generate relation controllers if needed
            foreach ($entity->getRelations() as $relation) {
                $this->generateRelationControllers($entity, $relation);
            }
        }
    }

    /**
     * Generate a controller for a given entity.
     */
    protected function generateController(Entity $entity): void
    {
        $modelClass = $entity->getClassName();
        $modelClassFull = $entity->getFullyQualifiedModelClass();
        $modelClassCamel = Str::camel($modelClass);
        $controllerClass = Str::studly(Str::singular($entity->getName())).'Controller';
        $vuePage = $entity->getFolderName();

        // Relations for eager loading
        $relations = $entity->getRelations(RelationType::BELONGS_TO);


        // Build array like: ['company:id,name', 'user:id,name']
        $relationsNames = array_map(
            fn ($r) => $r->getRelationName() . ':' . $r->getEagerFieldsStr(),
            $relations
        );
        $relationNamesCode = empty($relationsNames)
            ? '[]'
            : "['" . implode("', '", $relationsNames) . "']";



        // Fields visible in index listing
        $tableFieldsNames = $entity->getTableFieldsNames();
        $tableFieldsNamesStr = empty($tableFieldsNames)
            ? '[]'
            : "['".implode("', '", $tableFieldsNames)."']";

        // Searchable fields
        $searchableFields = array_filter($entity->getFields(), fn ($field) => $field->searchable);
        $searchableFieldsCode = empty($searchableFields)
            ? '[]'
            : "['".implode("', '", array_map(fn ($f) => $f->name, $searchableFields))."']";


        /** @var EntityRuleSet $validationRules */
        $validationRules = $this->validationHelper->make($entity);

        $validationRulesCreate = $validationRules->getCreateRules() ?? [];
        $validationRulesUpdate = $validationRules->getUpdateRules() ?? [];

        // Prepare for writing in php file
        $rulesArrayStrCreate = exportPhpValue($validationRulesCreate, 2);
        $rulesArrayStrUpdate = exportPhpValue($validationRulesUpdate, 2);

        $template = <<<PHP
<?php

namespace App\Http\Controllers;

use $modelClassFull;
use Illuminate\Http\Request;
use Inertia\Inertia;

class $controllerClass extends Controller
{
    /**
     * Show the $modelClass index page.
     */
    public function index()
    {
        \$request = request();

        // All relation names for eager loading
        \$relations = $relationNamesCode;

        // If the entity has searchable fields, we can use them for searching
        \$searchableFields = $searchableFieldsCode;
        // Table fields to select
        \$queryFields = $tableFieldsNamesStr;

        \$query = count(\$relations) > 0
            ? $modelClass::with(\$relations)
            : $modelClass::query();

        // Only specific fields
        if (count(\$queryFields) > 0) {
            \$query->select(\$queryFields);
        }

        // Sorting ( sortKey / sortDir )
        \$sortKey = \$request->get('sortKey', 'id');
        \$sortDir = \$request->get('sortDir', 'asc');
        if (\$sortKey && \$sortDir) {
            \$query->orderBy(\$sortKey, \$sortDir);
        } else {
            \$query->orderBy('id', 'asc'); // Default sorting
        }

        // Search
        \$search = \$request->get('search');
        if (\$search && count(\$searchableFields) > 0) {
            \$query->where(function (\$q) use (\$search, \$searchableFields) {
                foreach (\$searchableFields as \$field) {
                    \$q->orWhere(\$field, 'like', "%{\$search}%");
                }
            });
        }
        \$items = \$query->paginate(
            \$request->integer('per_page', 12),
            ['*'],
            'page',
            \$request->integer('page', 1)
        );

        return Inertia::render('$vuePage/Index', [
            'filters' => request()->only(['search', 'sortKey', 'sortDir']),
            'data' => \$items,
        ]);
    }

    /**
     * Show the form for creating a new $modelClass.
     */
    public function create()
    {
        return Inertia::render('$vuePage/Create');
    }

    /**
     * Store a newly created $modelClass in storage.
     */
    public function store(Request \$request)
    {
        \$data = \$request->validate($rulesArrayStrCreate);

        $modelClass::create(\$data);

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage created successfully.');
    }

    /**
     * Show the form for editing the specified $modelClass.
     */
    public function show($modelClass \${$modelClassCamel})
    {
        // All relation names for eager loading
        \$relations = $relationNamesCode;
        \${$modelClassCamel}->load(\$relations);

        return Inertia::render('$vuePage/Edit', [
            'item' => \${$modelClassCamel},
        ]);
    }

    /**
     * Show the form for editing the specified $modelClass.
     */
    public function edit($modelClass \${$modelClassCamel})
    {
        // All relation names for eager loading
        \$relations = $relationNamesCode;
        \${$modelClassCamel}->load(\$relations);

        return Inertia::render('$vuePage/Edit', [
            'item' => \${$modelClassCamel},
        ]);
    }

    /**
     * Update the specified $modelClass in storage.
     */
    public function update(Request \$request, $modelClass \$$modelClassCamel)
    {
        \$data = \$request->validate($rulesArrayStrUpdate);

        \${$modelClassCamel}->update(\$data);

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage updated successfully.');
    }

    /**
     * Remove the specified $modelClass from storage.
     */
    public function destroy($modelClass \${$modelClassCamel})
    {
        \${$modelClassCamel}->delete();

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage deleted successfully.');
    }
}
PHP;

        $filePath = $this->controllerPath.'/'.$controllerClass.'.php';
        app(GenerateFileAction::class)($filePath, $template);
        $this->context->registerGeneratedFile($filePath);

        $relPath = str_replace(app_path('Http/Controllers/'), '', $filePath);
        Log::channel('magic')->info("Controller created: {$relPath}");
    }

    public function generateRelationControllers(Entity $entity, Relation $relation): void
    {
        $template = $this->buildRelationControllers($entity, $relation);
        if (empty($template)) {
            return;
        }

        // Example file path: app/Http/Controllers/UserRolesController.php
        $filePath = $this->controllerPath.'/' . $entity->getSingularName() . '/' .Str::studly($entity->getName()).Str::studly($relation->getRelatedEntityName()).'Controller.php';
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
            Log::channel('magic')->warning("Related entity {$relation->getRelatedEntityName()} not found for relation in {$entity->getName()}");
            return '';
        }

        $parentModelClass = $entity->getClassName();                       // User
        $parentModelClassLower = Str::lower($parentModelClass);            // user
        $parentModelFolderName = $entity->getFolderName();                 // Users
        $parentModelClassFull = $entity->getFullyQualifiedModelClass();    // \App\Models\User

        $relatedModelClass = $relatedEntity->getClassName();               // Post
        $relatedModelClassFull = $relatedEntity->getFullyQualifiedModelClass(); // \App\Models\Post

        $controllerClass = Str::studly($entity->getName()) . Str::studly($relatedEntity->getSingularName()) . 'Controller';
        $relationName = $relation->getRelationName();                      // posts

        // Default template
        $methodBody = "// TODO: implement relation controller logic";

        // Adjust template based on relation type
        if ($relation->isHasMany()) {
            $methodBody = <<<PHP
        \${$parentModelClassLower}->load('$relationName');

        return Inertia::render('$parentModelFolderName/$relationName/Index', [
            'item' => \${$parentModelClassLower}->only(['id', 'name']),
            '$relationName' => $relatedModelClass::where('{$relation->getForeignKey()}', \${$parentModelClassLower}->id)->paginate(),
        ]);
        PHP;
        }

        if ($relation->isHasOne()) {
            $methodBody = <<<PHP
        \$related = \${$parentModelClassLower}->$relationName;

        return Inertia::render('$parentModelFolderName/$relationName/Index', [
            'item' => \${$parentModelClassLower}->only(['id', 'name']),
            '$relationName' => \$related,
        ]);
        PHP;
        }

        if ($relation->isBelongsTo()) {
            $methodBody = <<<PHP
        \$related = \${$parentModelClassLower}->$relationName;

        return Inertia::render('$parentModelFolderName/$relationName/Index', [
            'item' => \${$parentModelClassLower}->only(['id', 'name']),
            '$relationName' => \$related,
            '{$relationName}' => $relatedModelClass::paginate(['id','name']),
        ]);
        PHP;
        }

        if ($relation->isBelongsToMany()) {
            $methodBody = <<<PHP
        return Inertia::render('$parentModelFolderName/$relationName/Index', [
            'item' => \${$parentModelClassLower}->only(['id', 'name']),
            '{$relationName}' => $relatedModelClass::select(['id','name'])->paginate(),
            '{$relationName}_ids' => \${$parentModelClassLower}->$relationName()->pluck('id'),
        ]);
        PHP;
        }

        // Final template
        $template = <<<PHP
<?php

namespace App\Http\Controllers\\{$entity->getSingularName()};

use App\Http\Controllers\Controller;
use $parentModelClassFull;
use $relatedModelClassFull;
use Illuminate\Http\Request;
use Inertia\Inertia;

class $controllerClass extends Controller
{
    public function index($parentModelClass \$$parentModelClassLower)
    {
        $methodBody
    }
}
PHP;

        return $template;
    }

    /**
     * Each resource, eg. User, can have relations. In order to manage them,
     * we will have to generate additional controllers.
     * For example, if User hasMany Posts, we need a UserPostsController.
     * This method will generate such controllers.
     */
    public function buildRelationControllersOld(Entity $entity, Relation $relation): string
    {
        // Example: User hasMany Posts
        // We need to generate UserPostsController
        $relatedEntityName = $relation->getRelatedEntityName();
        if (! $relatedEntityName) {
            return '';
        }

        $relatedEntity = $this->context->getConfig()->getEntityByName($relatedEntityName);
        if (! $relatedEntity) {
            Log::channel('magic')->warning("Related entity {$relation->getRelatedEntityName()} not found for relation in {$entity->getName()}");
            return '';
        }
        // Example: User
        $parentModelClass = $entity->getClassName();
        // Example: user
        $parentModelClassLower = Str::lower($parentModelClass);
        // Example: users
        $parentModelFolderName = $entity->getFolderName();
        // Example: \App\Models\User
        $parentModelClassFull = $entity->getFullyQualifiedModelClass();
        // Example: Post
        $relatedModelClass = $relatedEntity->getClassName();
        // Example: \App\Models\Post
        $relatedModelClassFull = $relatedEntity->getFullyQualifiedModelClass();
        // Example: UserPostController
        $controllerClass = Str::studly($entity->getName()).Str::studly($relatedEntity->getSingularName()).'Controller';
        // Example: posts
        $relationName = $relation->getRelationName();
        // Example: Posts
        $relationNamePlural = $relatedEntity->getPluralName();

        // Prepare template based on relation type
        $template = <<<PHP
<?php

namespace App\Http\Controllers\\{$entity->getSingularName()};

use App\Http\Controllers\Controller;
use $parentModelClassFull;
use $relatedModelClassFull;
use Illuminate\Http\Request;
use Inertia\Inertia;

class $controllerClass extends Controller
{
    public function edit($parentModelClass \$$parentModelClassLower)
    {
        return inertia('$parentModelFolderName/$relationName/Index', [
            'item' => \${$parentModelClassLower}->only(['id', 'name']),
            '$relationName' => $relatedModelClass::paginate(),
            '{$relationName}_ids' => \${$parentModelClassLower}->{$relationName}->pluck('id'),
        ]);
    }

    public function update(Request \$request, $parentModelClass \$$parentModelClassLower)
    {
        \${$parentModelClassLower}->{$relationName}()->sync(\$request->input('$relationName', []));
        return back()->with('success', '$relatedModelClass updated.');
    }
}
PHP;

        return $template;

    }

    /**
     * Generate routes for all entities and save to app.php
     * This app.php file will be required by web.php
     *
     * @see ensureWebPhpRequiresAppPhp
     */
    public function generateRoutes(): void
    {
        $routeLines = [];

        // First, let's register main resource controllers routes
        foreach ($this->context->getConfig()->entities as $entity) {
            $name = $entity->getRouteName();
            $controller = '\\App\\Http\\Controllers\\'.Str::studly(Str::singular($name)).'Controller';

            $routeLines[] = "Route::resource('$name', '$controller');";
        }

        // Now lets register relation controllers routes
        foreach ($this->context->getConfig()->entities as $entity) {
            $routeLines[] = '';
            $routeLines[] = " // Routes for entity: {$entity->getName()} relations";
            foreach ($entity->getRelationsWithValidEntity() as $relation) {
                $relationName = $relation->getRelationName();
                $fQCN = $relation->getControllerFullQualifiedName();

                $routeLines[] = " // Routes for relation: {$entity->getName()} -> {$relation->getRelationName()} ({$relation->type->value})";
                $routeLines[] = "Route::get('{$relation->getRouteDefinitionPath()}', [{$fQCN}::class, 'index'])->name('{$entity->getRouteName()}.index-$relationName');";
                //$routeLines[] = "Route::put('{$relation->getRouteDefinitionPath()}', [{$fQCN}::class, 'update'])->name('{$entity->getRouteName()}.update-$relationName');";
            }
        }
        $routesContent = "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n".implode("\n", $routeLines)."\n";

        app(GenerateFileAction::class)($this->routesFilePath, $routesContent);
        $this->context->registerGeneratedFile($this->routesFilePath);

        Log::channel('magic')->info("Routes generated and saved to: {$this->routesFilePath}");

        $this->ensureWebPhpRequiresAppPhp();
    }

    /**
     * Ensure web.php requires app.php
     */
    protected function ensureWebPhpRequiresAppPhp(): void
    {
        $webPhpPath = base_path('routes/web.php');
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
}
