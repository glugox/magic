<?php

namespace Glugox\Magic\Support\File;

use Illuminate\Support\Facades\Log;

class FilesGenerationUpdate
{
    /**
     * Constructor
     */
    public function __construct(
        public array $created = [],
        public array $updated = [],
        public array $deleted = [],
    ) {
        // /
    }

    /**
     * Merges another FilesGenerationUpdate into this one
     */
    public function merge(FilesGenerationUpdate $other): self
    {
        $this->created = array_merge($this->created, $other->created);
        $this->updated = array_merge($this->updated, $other->updated);
        $this->deleted = array_merge($this->deleted, $other->deleted);

        return $this;
    }

    /**
     * Add created file
     */
    public function addCreated(string $filePath): void
    {
        $this->created[] = $filePath;

        // If the file was previously marked as deleted, remove it from deleted
        if (($key = array_search($filePath, $this->deleted)) !== false) {
            unset($this->deleted[$key]);
            // Reindex the array to maintain proper indices
            $this->deleted = array_values($this->deleted);
        }
    }

    /**
     * Add updated file
     */
    public function addUpdated(string $filePath): void
    {
        $this->updated[] = $filePath;
    }

    /**
     * Add deleted file
     */
    public function addDeleted(string $filePath): void
    {
        $this->deleted[] = $filePath;
    }

    /**
     * Writes the manifest to a JSON file
     */
    public function writeManifest(): void
    {
        $manifestPath = storage_path('magic/manifest.json');
        $data = [
            'timestamp' => date('c'),
            'files' => [
                'created' => $this->created,
                'updated' => $this->updated,
                'deleted' => $this->deleted,
            ],
        ];
        file_put_contents($manifestPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Deletes all files listed in the manifest file
     *
     * @throws \JsonException
     */
    public static function deleteGeneratedFiles(): void
    {
        $manifestPath = storage_path('magic/manifest.json');
        Log::channel('magic')->debug("Deleting generated files by manifest file : $manifestPath}");

        // Load the manifest
        if (file_exists($manifestPath)) {
            $contents = file_get_contents($manifestPath);
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (isset($data['files']['created'])) {
                foreach ($data['files']['created'] as $file) {
                    if (file_exists($file)) {
                        Log::channel('magic')->debug("Deleting generated file : $file");
                        unlink($file);
                    }
                }
            }
        }

        if (file_exists($manifestPath)) {
            unlink($manifestPath);
            Log::channel('magic')->debug("Deleting generated files : $manifestPath");
        }
    }
}
