<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Support\TypeHelper;
use Illuminate\Filesystem\Filesystem;

class TsBuilderService
{
    protected string $jsPath;

    public function __construct(
        protected Filesystem $files,
        protected Config $config
    ) {
        $this->jsPath = resource_path('js');
    }

    /**
     * Build js/ts support files for entities, etc.
     */
    public function build()
    {
        $this->generateSupportFiles();
    }

    /**
     * Generate support files like types.ts.
     */
    private function generateSupportFiles()
    {
        $this->generateTypesFile();
        $this->generateEntityHelperFiles();
        $this->copyVueFiles();
    }

    /**
     * Generate the types file for all entities.
     */
    private function generateTypesFile()
    {
        $path = resource_path('js/types/app.ts');
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $content = '';

        // Generate entity and field interfaces
        $content .= "\n\n";
        $fields = '';
        foreach ($this->config->entities as $entity) {
            $entityName = $entity->getName();
            $content .= "export interface {$entityName} {\n";
            foreach ($entity->getFields() as $field) {
                $tsType = TypeHelper::migrationTypeToTsType($field->type);
                $fields .= "    {$field->name}: {$tsType->value};\n";
            }
            $content .= $fields."}\n\n";
            $fields = ''; // Reset fields for next entity
        }

        $this->files->put($path, $content);
    }

    /**
     * Generate helper files for each entity.
     */
    private function generateEntityHelperFiles()
    {
        foreach ($this->config->entities as $entity) {
            $this->generateEntityHelperFile($entity);
        }
    }

    /**
     * Generate a helper file for a specific entity.
     */
    private function generateEntityHelperFile(Entity $entity)
    {
        $entityName = $entity->getName();
        $folderName = $entity->getFolderName();
        $fileName = $folderName.'_helper.ts';
        $path = "{$this->jsPath}/helpers/{$fileName}";

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $content = <<<EOT

import {type Entity, type Field, type {$entityName}} from "@/types/magic";
import {ColumnDef} from "@tanstack/vue-table";
import { Checkbox } from "@/components/ui/checkbox"
import {h} from "vue";
import {Button} from "@/components/ui/button";
import {ArrowUpDown} from "lucide-vue-next";
import Avatar from "@/components/Avatar.vue";
import {parseBool} from "@/lib/utils";

export function get{$entityName}Columns(): ColumnDef<{$entityName}>[] {
    return [
        {$this->getColumnDef($entity)}
    ];
}

export function get{$entityName}EntityMeta(): Entity {
    return {
        name: '{$entityName}',
        resourcePath: '{$entity->getResourcePath()}',
        singularName: '{$entity->getSingularName()}',
        pluralName: '{$entity->getPluralName()}',
        fields: [
            // Define fields for the entity
            {$this->getColumnsMeta($entity)}
        ]
    };
}

EOT;
        $this->files->put($path, $content);
    }

    /**
     * Generate the metadata for the entity columns.
     * It differs from the getColumnDef method in that it returns
     * the metadata for the fields, not the column definitions
     * that are strict formatted for the table.
     *
     * @return string
     */
    private function getColumnsMeta(Entity $entity)
    {
        $fields = [];
        foreach ($entity->getFields() as $field) {
            $fieldMeta = TsHelper::writeFieldMeta($field);
            $fields[] = $fieldMeta;
        }

        return implode(",\n            ", $fields);
    }

    /**
     * Generate the column definition for the entity.
     */
    private function getColumnDef(Entity $entity): string
    {
        $columns = [];

        // Add select at the beginning
        $columns[] = $this->getInitialColumnDef();

        foreach ($entity->getFields() as $field) {
            $column = TsHelper::writeTableColumn($field, $entity);
            $columns[] = $column;
        }

        return implode(",\n        ", $columns);
    }

    /**
     * Copy Vue files from the package to the resources/js directory.
     * This is a placeholder for future implementation.
     */
    private function copyVueFiles()
    {
        $vueFiles = [
            'components/ResourceTable.vue',
            'components/Avatar.vue',
            'types/magic.ts',
        ];

        foreach ($vueFiles as $file) {
            $sourcePath = __DIR__."/../../resources/js/{$file}";
            $destinationPath = resource_path("js/{$file}");

            if (! $this->files->isDirectory(dirname($destinationPath))) {
                $this->files->makeDirectory(dirname($destinationPath), 0755, true);
            }

            $this->files->copy($sourcePath, $destinationPath);
        }
    }

    public function getInitialColumnDef(): string
    {
        return "
        {
        id: 'select',
        header: ({ table }) => h(Checkbox, {
            'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
            'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
            'ariaLabel': 'Select all',
        }),
        cell: ({ row }) => h(Checkbox, {
            'modelValue': row.getIsSelected(),
            'onUpdate:modelValue': value => row.toggleSelected(!!value),
            'ariaLabel': 'Select row',
        }),
        enableSorting: false,
        enableHiding: false,
    }";
    }
}
