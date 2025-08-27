<?php

namespace Glugox\Magic\Support\File;

use Glugox\Magic\Contracts\GeneratedFile;

class VueFile extends GeneratedFileBase implements GeneratedFile
{
    public function __construct(

        /**
         * File name with extension
         * e.g., "MyComponent.vue"
         */
        public string $fileName,

        /**
         * Directory path where the file will be saved
         * e.g., "src/components"
         * Optional, defaults to current directory
         */
        public string $directory = '.',

        /**
         * Script portion of the Vue file
         */
        public string $script = '',

        /**
         * Template portion of the Vue file
         */
        public string $template = '',

        /**
         * Style portion of the Vue file
         */
        public string $style = ''
    ) {}

    /**
     * Create instance from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileName: $data['fileName'] ?? throw new \InvalidArgumentException("File 'fileName' is required"),
            directory: $data['directory'] ?? '.',
            script: $data['script'] ?? '',
            template: $data['template'] ?? '',
            style: $data['style'] ?? ''
        );
    }

    /**
     * String representation of the Vue file
     */
    public function __toString(): string
    {
        return <<<VUE
<script setup lang="ts">
{$this->script}
</script>

<template>
{$this->template}
</template>

{$this->style}
VUE;
    }
}
