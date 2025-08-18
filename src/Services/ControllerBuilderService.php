<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\FieldType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ControllerBuilderService
{
    protected string $controllerPath;

    protected string $routesFilePath;

    public function __construct(protected Config $config)
    {
        $this->controllerPath = app_path('Http/Controllers');
        $this->routesFilePath = base_path('routes/app.php');
        if (! File::exists($this->controllerPath)) {
            File::makeDirectory($this->controllerPath, 0755, true);
        }
    }

    /**
     * Build controllers for all entities defined in the config.
     * And build routes for them.
     */
    public function build(): void
    {
        $this->buildControllers();
        $this->generateRoutes();
    }

    /**
     * Generate controllers for all entities defined in the config.
     */
    public function buildControllers(): void
    {
        foreach ($this->config->entities as $entity) {
            $this->generateController($entity);
        }
    }

    /**
     * Generate a controller for a given entity.
     */
    protected function generateController(Entity $entity): void
    {
        $modelClass = '\\App\\Models\\'.Str::studly(Str::singular($entity->getName()));
        $controllerClass = Str::studly(Str::singular($entity->getName())).'Controller';
        $vuePage = $entity->getFolderName();

        // Validation rules
        $validationRules = [];
        foreach ($entity->getFields() as $field) {
            $rules = [];
            if (! $field->nullable) {
                $rules[] = 'required';
            } else {
                $rules[] = 'sometimes';
            }
            $rules[] = match ($field->type) {
                FieldType::EMAIL => 'email',
                FieldType::INTEGER => 'integer',
                FieldType::DECIMAL, FieldType::FLOAT, FieldType::DOUBLE => 'numeric',
                FieldType::BOOLEAN => 'boolean',
                FieldType::DATE, FieldType::DATETIME => 'date',
                default => 'string',
            };
            $validationRules[$field->name] = implode('|', $rules);
        }
        $rulesArrayStr = var_export($validationRules, true);
        $rulesArrayStr = str_replace(['array (', ')'], ['[', ']'], $rulesArrayStr);

        $template = <<<PHP
<?php

namespace App\Http\Controllers;

use $modelClass;
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
        \$query = $modelClass::query();

        // Sorting
        \$sorts = (array) \$request->get('sort', []);
        foreach (\$sorts as \$sort) {
            [\$column, \$direction] = explode(':', \$sort);
            \$query->orderBy(\$column, \$direction);
        }
        // Search
        \$search = \$request->get('search');
        if (\$search) {
            \$query->where(function (\$q) use (\$search) {
                \$q->where('name', 'like', "%{\$search}%")
                  ->orWhere('email', 'like', "%{\$search}%");
            });
        }
        \$items = \$query->paginate(
            \$request->integer('per_page', 15),
            ['*'],
            'page',
            \$request->integer('page', 1)
        );

        return Inertia::render('$vuePage/Index', [
            'filters' => request()->only(['search', 'sort', 'direction']),
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
        \$data = \$request->validate($rulesArrayStr);

        $modelClass::create(\$data);

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage created successfully.');
    }

    /**
     * Show the form for editing the specified $modelClass.
     */
    public function edit($modelClass \$item)
    {
        return Inertia::render('$vuePage/Edit', [
            'item' => \$item,
        ]);
    }

    /**
     * Update the specified $modelClass in storage.
     */
    public function update(Request \$request, $modelClass \$item)
    {
        \$data = \$request->validate($rulesArrayStr);

        \$item->update(\$data);

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage updated successfully.');
    }

    /**
     * Remove the specified $modelClass from storage.
     */
    public function destroy($modelClass \$item)
    {
        \$item->delete();

        return redirect()->route(strtolower('$vuePage') . '.index')
            ->with('success', '$vuePage deleted successfully.');
    }
}
PHP;

        $filePath = $this->controllerPath.'/'.$controllerClass.'.php';

        File::put($filePath, $template);

        $relPath = str_replace(app_path('Http/Controllers/'), '', $filePath);
        Log::channel('magic')->info("Controller created: {$relPath}");
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

        foreach ($this->config->entities as $entity) {
            $name = $entity->getRouteName();
            $controller = '\\App\\Http\\Controllers\\'.Str::studly(Str::singular($name)).'Controller';

            $routeLines[] = "Route::resource('$name', '$controller');";
        }

        $routesContent = "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n".implode("\n", $routeLines)."\n";

        File::put($this->routesFilePath, $routesContent);

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
            File::put($webPhpPath, "<?php\n\n$requireLine\n");
            Log::channel('magic')->info('Created routes/web.php and added require for app.php');

            return;
        }

        $webPhpContent = File::get($webPhpPath);

        if (! str_contains($webPhpContent, $requireLine)) {
            // Append require line at the end
            File::append($webPhpPath, "\n$requireLine\n");
            Log::channel('magic')->info('Added require to routes/web.php');
        }
    }
}
