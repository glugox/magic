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
     * @param  object|string  $model  Eloquent model instance or fully qualified model class name
     */
    public static function resolve(object|string $model): string
    {
        $modelClass = static::normalizeModelClass($model);
        $shortName = class_basename($modelClass);
        $metaCandidates = [];

        foreach (static::candidateNamespaces($modelClass) as $namespace) {
            $metaCandidates[] = $namespace . '\\' . $shortName . 'Meta';
        }

        foreach ($metaCandidates as $metaClass) {
            if (class_exists($metaClass)) {
                return $metaClass;
            }
        }

        $lastAttempt = end($metaCandidates) ?: static::$defaultNamespace . '\\' . $shortName . 'Meta';

        throw new RuntimeException("ModelMeta class not found for model [{$modelClass}] at [{$lastAttempt}]");
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

    /**
     * Normalize a model reference into a class name string.
     */
    protected static function normalizeModelClass(object|string $model): string
    {
        if (is_object($model)) {
            return $model::class;
        }

        if (class_exists($model)) {
            return $model;
        }

        $studly = Str::studly($model);

        if (class_exists($studly)) {
            return $studly;
        }

        return Str::singular($studly);
    }

    /**
     * Determine the list of namespaces to search for a meta class.
     *
     * @return array<int, string>
     */
    protected static function candidateNamespaces(string $modelClass): array
    {
        $namespaces = [];
        $trimmedDefault = trim(static::$defaultNamespace, '\\');

        if (class_exists($modelClass)) {
            $namespace = trim(Str::beforeLast($modelClass, '\\' . class_basename($modelClass)), '\\');

            if ($namespace !== '') {
                $namespaces[] = static::inferMetaNamespace($namespace);
            }
        }

        $namespaces[] = $trimmedDefault;

        return array_values(array_unique(array_filter($namespaces)));
    }

    /**
     * Infer the meta namespace from the given model namespace.
     */
    protected static function inferMetaNamespace(string $namespace): string
    {
        $namespace = trim($namespace, '\\');

        if ($namespace === '') {
            return trim(static::$defaultNamespace, '\\');
        }

        if (Str::endsWith($namespace, '\\Models')) {
            return Str::replaceLast('\\Models', '\\Meta\\Models', $namespace);
        }

        return $namespace . '\\Meta\\Models';
    }
}
