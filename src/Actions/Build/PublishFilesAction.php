<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Support\TypeHelper;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'publish_files',
    description: 'Publishes Magic package files to the main application, including support files like types.ts and entity helpers.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class PublishFilesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

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
    public function __construct(
        private readonly TypeHelper $typeHelper,
        private readonly TsHelper $tsHelper,
        private readonly ValidationHelper $validationHelper,
    ){
        $this->jsPath = resource_path('js');
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        $this->context = $context;
        Log::channel('magic')->info('Starting Magic file publishing...');

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
        $this->generateEntitiesTsFiles();
        $this->generateEntityHelperFiles();
        $this->copyVueFiles();
    }

    /**
     * Generate the types file for all entities.
     * Example:
     * export interface User {
     *     id: number;
     *     name: string;
     *     ...
     * }
     */
    private function generateEntitiesTsFiles()
    {
        // This would be something like resources/js/types/entities.ts
        $path = config('magic.paths.entity_types_file');

        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));
        $content = '';

        // Add imports
        $content .= "import {ResourceData} from '@/types/support';\n";

        // Generate entity and field interfaces
        $content .= "\n\n";
        $fields = '';
        foreach ($this->context->getConfig()->entities as $entity) {
            $entityName = $entity->getName();
            $content .= "export interface {$entityName} extends ResourceData {\n";
            foreach ($entity->getTsFields() as $field) {
                $tsType = $this->typeHelper->migrationTypeToTsType($field->type);
                $fields .= "    {$field->name}: {$tsType->value};\n";
            }

            // Relations
            foreach ($entity->getRelations() as $relation) {
                $tsType = $this->typeHelper->relationToTsString($relation);
                $fields .= "    {$relation->getRelationName()}: {$tsType};\n";
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
     * For example , functions to get column definitions for tables, etc.
     * The function helpers are written for each entity in a separate file for type safety
     * open for modification, and better organization.
     *
     * @throws \ReflectionException
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
        $entityImports = $this->tsHelper->writeEntityImports($entity);
        $supportImports = $this->tsHelper->writeEntityHelperSupportImports($entity);


        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));

        $content = <<<EOT
import {ColumnDef} from "@tanstack/vue-table";
import { Checkbox } from "@/components/ui/checkbox"
import {h} from "vue";
import {Button} from "@/components/ui/button";
import {ArrowUpDown} from "lucide-vue-next";
import Avatar from "@/components/Avatar.vue";
import {parseBool} from "@/lib/app";
$entityImports
$supportImports

export function get{$entityName}Columns(): ColumnDef<{$entityName}>[] {
    return [
        {$this->getColumnDef($entity, 8)}
    ];
}

export function get{$entityName}EntityMeta(): Entity {
    return {
        name: '{$entityName}',
        indexRouteName: '{$entity->getIndexRouteName()}',
        singularName: '{$entity->getSingularName()}',
        pluralName: '{$entity->getPluralName()}',
        fields: [
            // Define fields for the entity
            {$this->getColumnsMeta($entity)}
        ],
        relations: [
            // Define relations for the entity
            {$this->getRelationsMeta($entity)}
        ],
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
        $entityValidationRuleSet = $this->validationHelper->make($entity);
        foreach ($entity->getFormFields() as $field) {
            $fieldMeta = $this->tsHelper->writeFieldMeta($field, $entityValidationRuleSet);
            $fields[] = $fieldMeta;
        }

        return implode(",\n            ", $fields);
    }

    /**
     * Generate the metadata for the entity relations.
     *
     * @return string
     */
    private function getRelationsMeta(Entity $entity)
    {
        $relations = [];
        foreach ($entity->getRelations() as $relation) {
            $relationMeta = $this->tsHelper->writeRelationMeta($entity, $relation);
            $relations[] = $relationMeta;
        }
        return implode(",\n            ", $relations);
    }

    /**
     * Generate the column definition for the entity.
     */
    private function getColumnDef(Entity $entity, $indent = 0): string
    {
        $columns = [];

        Log::channel('magic')->info("Generating column definitions for entity: {$entity->getName()}");

        // Add select at the beginning
        $columns[] = $this->getInitialColumnDef($indent);

        foreach ($entity->getTableFields() as $field) {
            Log::channel('magic')->info("Processing field: {$field->name} of type: {$field->type->value}");
            if ($field->isForeignKey()) {
                // Skip belongsTo fields as they are handled in relations
                Log::channel('magic')->info("Skipping belongsTo field: {$field->name}");

                continue;
            }

            $column = $this->tsHelper->writeTableColumn($field, $entity);
            $columns[] = $column;
        }

        //$indentStr = str_repeat("\t", $indent);
        return implode(",\n", $columns);
    }

    /**
     * Copy Vue files from the package to the resources/js directory.
     * This is a placeholder for future implementation.
     */
    private function copyVueFiles()
    {
        $source = __DIR__.'/../../../stubs/magic';
        $destination = base_path();

        // Use the CopyDirectoryAction to copy files
        $filesCopied = app(CopyDirectoryAction::class)($source, $destination);
        $this->context->registerGeneratedFile($filesCopied);

    }

    public function getInitialColumnDef($indent=0): string
    {
        return "{
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
