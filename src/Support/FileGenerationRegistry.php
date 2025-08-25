<?php

namespace Glugox\Magic\Support;

use Illuminate\Support\Facades\Log;

/**
 * Registry for tracking generated files and components.
 */
class FileGenerationRegistry
{

    private static array $generatedFiles = [];
    private static array $modifiedFiles = [];
    private static array $generatedComponents = [];

    /**
     * Register a generated file.
     *
     * @param string $filePath
     */
    public static function registerFile(string|array $filePath): void
    {
        // Check if $filePath is array
        if (is_array($filePath)) {
            foreach ($filePath as $path) {
                if (!in_array($path, self::$generatedFiles)) {
                    self::$generatedFiles[] = $path;
                }
            }
        } else {
            if (!in_array($filePath, self::$generatedFiles)) {
                self::$generatedFiles[] = $filePath;
            }
        }

    }

    /**
     * Get all registered generated files.
     *
     * @return array
     */
    public static function getGeneratedFiles(): array
    {
        return self::$generatedFiles;
    }

    /**
     * Register a modified file.
     *
     * @param string $filePath
     */
    public static function registerModifiedFile(string $filePath): void
    {
        self::$modifiedFiles[] = $filePath;
    }

    /**
     * Get all registered modified files.
     *
     * @return array
     */
    public static function getModifiedFiles(): array
    {
        return self::$modifiedFiles;
    }

    /**
     * Register a generated component.
     *
     * @param string $componentName
     */
    public static function registerComponent(string $componentName): void
    {
        self::$generatedComponents[] = $componentName;
    }

    /**
     * Get all registered generated components.
     *
     * @return array
     */
    public static function getGeneratedComponents(): array
    {
        return self::$generatedComponents;
    }

    /**
     * Write manifest file with all generated files and components.
     * into storage/magic/generated_files.json
     */
    public static function writeManifest(): void
    {
        $manifest = [
            'files' => self::$generatedFiles,
            'components' => self::$generatedComponents,
            'modified_files' => self::$modifiedFiles,
        ];
        $manifestPath = storage_path('magic/generated_files.json');
        if (!is_dir(dirname($manifestPath))) {
            mkdir(dirname($manifestPath), 0755, true);
        }
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Clear the registry (for testing or resetting purposes).
     */
    public static function clear(): void
    {
        self::$generatedFiles = [];
        self::$modifiedFiles = [];
        self::$generatedComponents = [];
    }

    /**
     * Deletes all generated files and components tracked in the registry.
     * Also deletes the manifest file.
     */
    public static function deleteGeneratedFiles(): void
    {
        $manifestPath = storage_path('magic/generated_files.json');
        Log::channel("magic")->debug("Deleting generated files by manifest file : $manifestPath}");

        // Load the manifest
        if (file_exists($manifestPath)) {
            $contents = file_get_contents($manifestPath);
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if(isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    if (file_exists($file)) {
                        Log::channel("magic")->debug("Deleting generated file : $file");
                        unlink($file);
                    }
                }
            }
        }


        /*foreach (self::$generatedFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::channel("magic")->debug("Deleting generated file : $filePath");
            }
        }*/
        self::clear();

        if (file_exists($manifestPath)) {
            unlink($manifestPath);
            Log::channel("magic")->debug("Deleting generated files : $manifestPath");
        }
    }
}
