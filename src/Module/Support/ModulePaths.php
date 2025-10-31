<?php

namespace Glugox\Module\Support;

/**
 * Helper methods for working with module-relative paths.
 */
class ModulePaths
{
    /**
     * Join the module base path with one or more relative segments.
     */
    public static function join(string $basePath, string ...$segments): string
    {
        $normalizedBase = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $basePath);
        $path = rtrim($normalizedBase, DIRECTORY_SEPARATOR);
        $isRoot = $normalizedBase === DIRECTORY_SEPARATOR;

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $segment = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $segment), DIRECTORY_SEPARATOR);

            if ($segment === '') {
                continue;
            }

            if ($path === '') {
                if ($isRoot) {
                    $path = DIRECTORY_SEPARATOR.$segment;
                    $isRoot = false;
                } else {
                    $path = $segment;
                }

                continue;
            }

            $path .= DIRECTORY_SEPARATOR.$segment;
        }

        return $path === '' && $isRoot ? DIRECTORY_SEPARATOR : $path;
    }
}
