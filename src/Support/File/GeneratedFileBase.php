<?php

namespace Glugox\Magic\Support\File;

use Glugox\Magic\Actions\Files\WriteGeneratedFiles;
use Glugox\Magic\Contracts\GeneratedFile;

class GeneratedFileBase implements GeneratedFile
{
    /**
     * File name with extension
     * e.g., "MyComponent.vue"
     */
    public string $fileName;

    /**
     * Directory path where the file will be saved
     * e.g., "src/components"
     * Optional, defaults to current directory
     */
    public string $directory = '.';

    public function __toString(): string
    {
        // To be implemented by subclasses
        return '';
    }

    /**
     * Write the generated file to the filesystem.
     */
    public function writeToFile(): void
    {
        $filePath = mb_rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->fileName;
        app(WriteGeneratedFiles::class)(
            input: $this
        );
    }
}
