<?php

namespace Glugox\ModelMeta;

use Glugox\ModelMeta\ModelMeta;
use Illuminate\Support\Str;
use RuntimeException;

class ModelMetaResolver
{

    /**
     * Resolve the ModelMeta class name for a given Eloquent model or model class name.
     *
     * Default: App\\Meta\\{ModelName}Meta
     * e.g. App\Meta\UserMeta for App\Models\User
     */
    protected static string $defaultNamespace = 'App\\Meta\\Models';
    public static function setDefaultNamespace(string $namespace): void
    {
        static::$defaultNamespace = trim($namespace, '\\');
    }

    /**
     * @param object|string $model Eloquent model instance or fully qualified model class name
     */
    public static function resolve(object|string $model): string
    {
        $modelClass = is_string($model) ? Str::singular(Str::studly($model)) : $model::class;
        $shortName  = class_basename($modelClass);
        $metaClass  = static::$defaultNamespace . '\\' . $shortName . 'Meta';

        if (! class_exists($metaClass)) {
            throw new RuntimeException("ModelMeta class not found for model [{$modelClass}] at [{$metaClass}]");
        }

        return $metaClass;
    }

    /**
     * Create an instance of the ModelMeta for a given model.
     *
     * @param object|string $model
     * @return ModelMeta
     */
    public static function make(object|string $model): ModelMeta
    {
        $metaClass = static::resolve($model);

        // @phpstan-ignore-next-line
        return function_exists('app') ? app($metaClass) : new $metaClass();
    }
}
