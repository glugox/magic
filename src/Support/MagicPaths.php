<?php

namespace Glugox\Magic\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MagicPaths
{
    /**
     * The base path for package generation. When null, the host application's
     * paths are used.
     */
    protected static ?string $packageBasePath = null;

    /**
     * Determine if Magic is currently generating files into a package.
     */
    public static function isUsingPackage(): bool
    {
        return static::$packageBasePath !== null;
    }

    /**
     * Configure Magic to generate files inside the given package directory.
     */
    public static function usePackage(string $path): void
    {
        $normalized = static::normalizePath($path);
        File::ensureDirectoryExists($normalized);
        static::$packageBasePath = $normalized;

        static::ensureStructure();
        static::refreshConfiguredPaths();
    }

    /**
     * Reset Magic to use the application's default paths.
     */
    public static function clearPackage(): void
    {
        static::$packageBasePath = null;
        static::refreshConfiguredPaths();
    }

    /**
     * Resolve a base-relative path for the current context.
     */
    public static function base(string $path = ''): string
    {
        $base = static::$packageBasePath ?? base_path();

        return static::join($base, $path);
    }

    /**
     * Resolve an app-relative path.
     */
    public static function app(string $path = ''): string
    {
        if (static::isUsingPackage()) {
            return static::join(static::base('src'), $path);
        }

        return app_path($path);
    }

    /**
     * Resolve a database-relative path.
     */
    public static function database(string $path = ''): string
    {
        if (static::isUsingPackage()) {
            return static::join(static::base('database'), $path);
        }

        return database_path($path);
    }

    /**
     * Resolve a resources-relative path.
     */
    public static function resource(string $path = ''): string
    {
        if (static::isUsingPackage()) {
            return static::join(static::base('resources'), $path);
        }

        return resource_path($path);
    }

    /**
     * Resolve a routes-relative path.
     */
    public static function routes(string $path = ''): string
    {
        if (static::isUsingPackage()) {
            return static::join(static::base('routes'), $path);
        }

        return base_path('routes'.(empty($path) ? '' : DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR)));
    }

    /**
     * Resolve a storage-relative path.
     */
    public static function storage(string $path = ''): string
    {
        if (static::isUsingPackage()) {
            return static::join(static::base('storage'), $path);
        }

        return storage_path($path);
    }

    /**
     * Resolve a tests-relative path.
     */
    public static function tests(string $path = ''): string
    {
        return static::join(static::base('tests'), $path);
    }

    /**
     * Ensure essential directories exist for package builds.
     */
    protected static function ensureStructure(): void
    {
        if (! static::isUsingPackage()) {
            return;
        }

        $directories = [
            'src',
            'database',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'resources',
            'resources/js',
            'resources/js/components',
            'resources/js/pages',
            'resources/js/types',
            'resources/views',
            'routes',
            'storage/magic',
            'tests',
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists(static::base($directory));
        }
    }

    /**
     * Update runtime configuration for paths that depend on the base location.
     */
    protected static function refreshConfiguredPaths(): void
    {
        if (! function_exists('config')) {
            return;
        }

        if (static::isUsingPackage()) {
            config([
                'magic.paths.support_types_file' => static::resource('js/types/support.ts'),
                'magic.paths.entity_types_file' => static::resource('js/types/entities.ts'),
                'magic.paths.entity_meta_file' => static::resource('js/types/entityMeta.ts'),
            ]);
        } else {
            config([
                'magic.paths.support_types_file' => resource_path('js/types/support.ts'),
                'magic.paths.entity_types_file' => resource_path('js/types/entities.ts'),
                'magic.paths.entity_meta_file' => resource_path('js/types/entityMeta.ts'),
            ]);
        }
    }

    /**
     * Convert a path to an absolute path relative to the host application.
     */
    protected static function normalizePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\/]/', $path) === 1) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path($path), DIRECTORY_SEPARATOR);
    }

    /**
     * Join a base path with the provided relative segment.
     */
    protected static function join(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        return rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}
