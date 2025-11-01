<?php

namespace Glugox\ModelMeta\Requests;

use Glugox\ModelMeta\ModelMeta;
use Illuminate\Foundation\Http\FormRequest;
use Glugox\ModelMeta\ModelMetaResolver;
use Illuminate\Support\Str;
use RuntimeException;

class MetaRequest extends FormRequest
{

    /**
     * Resolve the ModelMeta class name for a given Eloquent model or model class name.
     *
     * Default: App\\Meta\\{ModelName}Meta
     * e.g. App\Meta\UserMeta for App\Models\User
     */
    protected static string $defaultModelNamespace = 'App\\Models';
    public static function setDefaultModelNamespace(string $namespace): void
    {
        static::$defaultModelNamespace = trim($namespace, '\\');
    }

    protected ?string $resourceName = null;
    protected ?string $modelClass = null;

    protected ?string $metaClass = null;

    /**
     * Determine if the request expects a JSON response.
     * Checks for 'jsonMode' boolean parameter to force JSON response.
     */
    public function expectsJson(): bool
    {
        if ($this->boolean('jsonMode')) {
            return true;
        }
        return $this->ajax() || $this->wantsJson();
    }

    /**
     * Rules for Laravel FormRequest validation.
     *
     * @return array<string, string[]> The validation rules.
     */
    public function rules(): array
    {
        $routeName = $this->route()?->getName() ?? '';
        $segments = explode('.', $routeName);
        if (count($segments) === 1) {
            $segments = explode('-', $routeName);
        }

        $action = end($segments);

        // First, see if the Meta class defines custom rules for this action
        /** @var array{store: array<string, string[]>, update: array<string, string[]>} $metaRules */
        $recordId = $this->route()?->parameter('id') ?? $this->input('id');
        $metaRules = $this->meta()->rules($recordId);

        if (isset($metaRules[$action])) {
            return $metaRules[$action];
        }

        // Default CRUD fallback
        $method = $this->method();
        if ($method === 'POST') {
            return $metaRules['store'];
        }
        if ($method === 'PUT' || $method === 'PATCH') {
            return $metaRules['update'];
        }

        return [];
    }


    /**
     * Get resource name from route or URI.
     * e.g. "addresses" from "addresses.index" or "/api/addresses"
     *
     * @return string The resource name (plural, snake-case)
     */
    public function resourceName(): string
    {
        if (! $this->resourceName) {
            $routeName = $this->route()?->getName();

            if (! $routeName) {
                throw new RuntimeException("Cannot resolve resource name: route has no name.");
            }

            // Step 1: split only on dots
            $segments = explode('.', $routeName);

            // Step 2: if no dots (custom dash-style route), split once on dashes
            if (count($segments) === 1) {
                $segments = explode('-', $routeName);
            }

            // Remove common prefixes
            $prefixesToSkip = ['api', 'admin', 'v1', 'v2'];
            $segments = array_values(array_filter(
                $segments,
                fn ($s) => ! in_array($s, $prefixesToSkip, true)
            ));

            if (empty($segments)) {
                throw new RuntimeException("Cannot resolve resource name from route: {$routeName}");
            }

            // Known action keywords (extendable)
            $actionKeywords = [
                'index', 'show', 'create', 'store',
                'edit', 'update', 'destroy', 'action',
                'bulk', 'bulk-destroy', 'update-selection'
            ];

            // Step 3: Drop trailing action words
            while (! empty($segments) && in_array(end($segments), $actionKeywords, true)) {
                array_pop($segments);
            }

            $this->resourceName = end($segments) ?: throw new RuntimeException(
                "Cannot resolve resource name from route: {$routeName}"
            );
        }

        return $this->resourceName;
    }



    /**
     * Resolve the Eloquent model class for this request.
     */
    public function modelClass(): string
    {
        if (! $this->modelClass) {
            $modelClass = Str::singular(Str::studly($this->resourceName()));
            $shortName  = class_basename($modelClass);
            $this->modelClass  = static::$defaultModelNamespace . '\\' . $shortName;

            if (! class_exists($this->modelClass)) {
                throw new RuntimeException("ModelMeta class not found for model [{$modelClass}] at [{$this->modelClass}]");
            }
        }

        return $this->modelClass;
    }

    /**
     * Resolve the Meta class for this request.
     */
    public function metaClass(): string
    {
        if (! $this->metaClass) {
            $this->metaClass = ModelMetaResolver::resolve($this->modelClass());
        }

        return $this->metaClass;
    }

    /**
     * Instantiate the Meta object.
     */
    public function meta(): ModelMeta
    {
        return ModelMetaResolver::make($this->modelClass());
    }

    /**
     * Get the Inertia component to be used for the "create" and "edit" views.
     * It checks for a '_parentComponent' parameter in the request to allow dynamic component assignment.
     * If not present, it defaults to '/'.
     *
     * @return string|null The Inertia component name or path.
     */
    public function getStoreInertiaComponent(): ?string
    {
        if($this->filled('_parentComponent')){
            return $this->string('_parentComponent');
        }
        return null;
    }
}
