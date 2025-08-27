<?php

namespace Glugox\Magic\Contracts;

/**
 * Marker interface for generated files.
 */
interface GeneratedFile
{

    /**
     * String representation of the generated file.
     */
    public function __toString(): string;

    /**
     * Write the generated file to the filesystem.
     */
    public function writeToFile(): void;
}
