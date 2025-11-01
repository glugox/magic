<?php

namespace Glugox\Magic\Support;

class LocalPackages
{
    /**
     * Returns the absolute path to the packages directory shipped with Magic.
     */
    public static function packagesRoot(): string
    {
        return static::normalizePath(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'packages');
    }

    /**
     * Resolve the absolute path to a local Glugox package if it exists.
     */
    public static function find(string $packageName): ?string
    {
        $directoryName = static::packageDirectoryName($packageName);
        $candidate = static::packagesRoot().DIRECTORY_SEPARATOR.$directoryName;

        if (! is_dir($candidate)) {
            return null;
        }

        $resolved = realpath($candidate);

        return static::normalizePath($resolved !== false ? $resolved : $candidate);
    }

    /**
     * Build a Composer repository definition for the given package relative to the provided directory.
     *
     * @param  string  $fromDirectory  Directory containing the composer.json that references the package.
     * @return array{type: string, url: string, options: array{symlink: bool}}|null
     */
    public static function repositoryFor(string $packageName, string $fromDirectory): ?array
    {
        $packagePath = static::find($packageName);

        if ($packagePath === null) {
            return null;
        }

        $relative = static::relativePath($fromDirectory, $packagePath);

        return [
            'type' => 'path',
            'url' => str_replace('\\', '/', $relative),
            'options' => ['symlink' => true],
        ];
    }

    /**
     * Determine the directory name of the package within the local packages folder.
     */
    protected static function packageDirectoryName(string $packageName): string
    {
        $parts = explode('/', $packageName, 2);

        return $parts[1] ?? $packageName;
    }

    /**
     * Normalize a filesystem path to use forward slashes without a trailing slash.
     */
    protected static function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        return rtrim($normalized, '/');
    }

    /**
     * Compute a relative path from one absolute directory to another.
     */
    protected static function relativePath(string $from, string $to): string
    {
        [$fromParts, $fromRoot] = static::splitPath($from);
        [$toParts, $toRoot] = static::splitPath($to);

        if ($fromRoot !== $toRoot) {
            return static::normalizePath($to);
        }

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = array_merge(array_fill(0, count($fromParts), '..'), $toParts);
        $relative = implode('/', $relativeParts);

        return $relative === '' ? '.' : $relative;
    }

    /**
     * Split a path into its root and segments for comparison.
     *
     * @return array{0: list<string>, 1: string}
     */
    protected static function splitPath(string $path): array
    {
        $resolved = realpath($path);
        $normalized = static::normalizePath($resolved !== false ? $resolved : $path);

        if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
            $root = substr($normalized, 0, 2);
            $trimmed = ltrim(substr($normalized, 2), '/');
            $parts = $trimmed === '' ? [] : explode('/', $trimmed);

            return [$parts, $root];
        }

        $trimmed = ltrim($normalized, '/');
        $parts = $trimmed === '' ? [] : explode('/', $trimmed);

        return [$parts, '/'];
    }
}
