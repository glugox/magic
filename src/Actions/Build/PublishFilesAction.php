<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Support\TypeHelper;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'publish_files',
    description: 'Publishes Magic package files to the main application, including support files like types.ts and entity helpers.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class PublishFilesAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * Context with config
     */
    protected BuildContext $context;

    /**
     * Path to the resources/js directory
     */
    protected string $jsPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jsPath = resource_path('js');
    }

    public function __invoke(BuildContext $context): BuildContext
    {

        $this->context = $context;
        Log::channel('magic')->info('Starting Magic file publishing...');

        $source = __DIR__.'/../../../stubs/magic';
        $destination = base_path();

        // Use the CopyDirectoryAction to copy files
        app(CopyDirectoryAction::class)($source, $destination);

        // Generate support files like types.ts and entity helpers
        $this->generateSupportFiles();

        Log::channel('magic')->info('Magic file publishing complete!');

        return $context;
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

        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));
        $content = '';

        // Generate entity and field interfaces
        $content .= "\n\n";
        $fields = '';
        foreach ($this->context->getConfig()->entities as $entity) {
            $entityName = $entity->getName();
            $content .= "export interface {$entityName} {\n";
            foreach ($entity->getFields() as $field) {
                $tsType = TypeHelper::migrationTypeToTsType($field->type);
                $fields .= "    {$field->name}: {$tsType->value};\n";
            }
            $content .= $fields."}\n\n";
            $fields = ''; // Reset fields for next entity
        }

        // Action call -- Use the GenerateFileAction to create or overwrite the file
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate helper files for each entity.
     */
    private function generateEntityHelperFiles()
    {
        foreach ($this->context->getConfig()->entities as $entity) {
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

        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));

        $content = <<<EOT

import {type Entity, type Field, type {$entityName}} from "@/types/magic";
import {ColumnDef} from "@tanstack/vue-table";
import { Checkbox } from "@/components/ui/checkbox"
import {h} from "vue";
import {Button} from "@/components/ui/button";
import {ArrowUpDown} from "lucide-vue-next";
import Avatar from "@/components/Avatar.vue";
import {parseBool} from "@/lib/app";

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
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
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

        Log::channel('magic')->info("Generating column definitions for entity: {$entity->getName()}");

        // Add select at the beginning
        $columns[] = $this->getInitialColumnDef();

        foreach ($entity->getTableFields() as $field) {
            Log::channel('magic')->info("Processing field: {$field->name} of type: {$field->type->value}");
            if ($field->isForeignKey()) {
                // Skip belongsTo fields as they are handled in relations
                Log::channel('magic')->info("Skipping belongsTo field: {$field->name}");

                continue;
            }

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
        /**
         * We actually need to copy and overwire all files from package's stub/laravel/ folder to the main application.
         */
        $sourcePath = __DIR__.'/../../../resources/js';
        $destinationPath = base_path('resources/js');

        $filesCopied = app(CopyDirectoryAction::class)($sourcePath, $destinationPath);
        $this->context->registerGeneratedFile($filesCopied);

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
