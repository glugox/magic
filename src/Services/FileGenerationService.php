<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\FileGenerationRegistry;
use Illuminate\Support\Facades\File;

/**
 * Service for generating files based on configuration.
 * Keeps track of generated files and components by interacting with FileGenerationRegistry.
 */
class FileGenerationService
{
    /**
     * Generate a file with the given content and register it.
     */
    public function generateFile(string $filePath, string $content, ?bool $isUpdate = false): void
    {
        File::put($filePath, $content);

        if ($isUpdate) {
            FileGenerationRegistry::registerModifiedFile($filePath);
        } else {
            FileGenerationRegistry::registerFile($filePath);
        }
    }

    /**
     * Modify an existing file and register it.
     */
    public static function registerModifiedFile(string $filePath): void
    {
        FileGenerationRegistry::registerModifiedFile($filePath);
    }

    /**
     * Copies dir to destination recursively.
     */
    public static function copyDirectoryWithList(string $source, string $destination): array
    {
        $copiedFiles = [];

        $files = File::allFiles($source); // recursive iterator of all files

        foreach ($files as $file) {
            // Destination path keeping subdirectory structure
            $relativePath = $file->getRelativePathname();
            $targetPath = $destination.DIRECTORY_SEPARATOR.$relativePath;

            // Ensure directory exists
            File::ensureDirectoryExists(dirname($targetPath));

            // Copy & overwrite
            File::copy($file->getRealPath(), $targetPath);

            $copiedFiles[] = $targetPath;
        }

        return $copiedFiles;
    }
}
