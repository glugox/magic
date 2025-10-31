<?php

namespace Glugox\Magic\Support;

use Glugox\ModelMeta\ModelMetaResolver;
use Illuminate\Support\Str;

class MagicNamespaces
{
    /**
     * The base namespace used for generated classes.
     */
    protected static string $baseNamespace = 'App';

    /**
     * Determine if the generator is using a non-default namespace.
     */
    public static function isUsingCustomNamespace(): bool
    {
        return static::$baseNamespace !== 'App';
    }

    /**
     * Configure the base namespace used for generation.
     */
    public static function use(string $namespace): void
    {
        static::$baseNamespace = mb_trim($namespace, '\\');

        static::syncModelMetaNamespace();
    }

    /**
     * Reset the base namespace back to the Laravel default.
     */
    public static function clear(): void
    {
        static::$baseNamespace = 'App';

        static::syncModelMetaNamespace();
    }

    /**
     * Retrieve the base namespace or append a suffix to it.
     */
    public static function base(string $suffix = ''): string
    {
        $suffix = mb_trim($suffix, '\\');

        return $suffix === ''
            ? static::$baseNamespace
            : static::$baseNamespace.'\\'.$suffix;
    }

    public static function models(string $suffix = ''): string
    {
        return static::base(static::qualify('Models', $suffix));
    }

    public static function enums(string $suffix = ''): string
    {
        return static::base(static::qualify('Enums', $suffix));
    }

    public static function traits(string $suffix = ''): string
    {
        return static::base(static::qualify('Traits', $suffix));
    }

    public static function actions(string $suffix = ''): string
    {
        return static::base(static::qualify('Actions', $suffix));
    }

    public static function httpControllers(string $suffix = ''): string
    {
        return static::base(static::qualify('Http\\Controllers', $suffix));
    }

    public static function httpResources(string $suffix = ''): string
    {
        return static::base(static::qualify('Http\\Resources', $suffix));
    }

    public static function httpMiddleware(string $suffix = ''): string
    {
        return static::base(static::qualify('Http\\Middleware', $suffix));
    }

    public static function jobs(string $suffix = ''): string
    {
        return static::base(static::qualify('Jobs', $suffix));
    }

    public static function metaModels(string $suffix = ''): string
    {
        return static::base(static::qualify('Meta\\Models', $suffix));
    }

    public static function responses(string $suffix = ''): string
    {
        return static::base(static::qualify('Http\\Responses', $suffix));
    }

    /**
     * Return the namespace used for the package service providers.
     */
    public static function providers(string $suffix = ''): string
    {
        return static::base(static::qualify('Providers', $suffix));
    }

    /**
     * Convert a namespace into its PSR-4 path relative to the base namespace.
     */
    public static function toPsr4Path(string $namespace): string
    {
        $relative = Str::after($namespace, static::$baseNamespace.'\\');
        $relative = mb_trim($relative, '\\');

        return str_replace('\\', '/', $relative === '' ? '' : $relative).($relative === '' ? '' : '/');
    }

    protected static function qualify(string $root, string $suffix): string
    {
        $root = mb_trim($root, '\\');
        $suffix = mb_trim($suffix, '\\');

        if ($suffix === '') {
            return $root;
        }

        return $root.'\\'.$suffix;
    }

    /**
     * Keep the ModelMeta resolver aligned with the active base namespace.
     */
    protected static function syncModelMetaNamespace(): void
    {
        if (! class_exists(ModelMetaResolver::class)) {
            return;
        }

        ModelMetaResolver::setDefaultNamespace(static::metaModels());
    }
}
