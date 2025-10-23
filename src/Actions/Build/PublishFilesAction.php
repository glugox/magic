<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Action;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\Filter;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Support\TypeHelper;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionException;

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
    ) {
        $this->jsPath = resource_path('js');
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        $this->context = $context;
        Log::channel('magic')->info('Starting Magic file publishing...');

        // Generate support files like types.ts and entity metas
        $this->generateSupportFiles();

        Log::channel('magic')->info('Magic file publishing complete!');

        return $context;
    }

    public function getInitialColumnDef(int $indent = 0): string
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

    /**
     * Generate support files like types.ts.
     *
     * @throws ReflectionException
     */
    private function generateSupportFiles(): void
    {
        $this->generateEntitiesTsFiles();
        $this->generateEntitiesMetaTsFiles();
        $this->generateEntityHelperFiles();
        $this->copyMagicFiles();
    }

    /**
     * Generate the entity metas file for all entities.
     * Two-phase generation:
     *  - Phase 1: declare all entity objects with fields and empty relations
     *  - Phase 2: after all entities declared, assign relations using lazy references
     */
    private function generateEntitiesMetaTsFiles(): void
    {
        $path = config('magic.paths.entity_meta_file');

        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));

        $content = '';

        // Build import strings for all relevant TS ( Wayfinder ) controllers
        $controllersImportStr = '';
        $ctrImportsArr = [];
        foreach ($this->context->getConfig()->entities as $entity) {
            $ctrImport = "import {$entity->getName()}Controller from '@/actions/App/Http/Controllers/{$entity->getName()}Controller';";
            $ctrImportsArr[] = $ctrImport;

            // Add related entity controllers as well
            foreach ($entity->getRelations() as $relation) {

                if (! $relation->hasRoute()) {
                    // Skip polymorphic relations as they can relate to multiple entities
                    continue;
                }

                $relatedName = $relation->getRelatedEntityName();
                if ($relatedName) {
                    $relatedImport = "import {$entity->getName()}{$relatedName}Controller from '@/actions/App/Http/Controllers/{$entity->getName()}/{$entity->getName()}{$relatedName}Controller';";
                    if (! in_array($relatedImport, $ctrImportsArr, true)) {
                        $ctrImportsArr[] = $relatedImport;
                    }
                }
            }
        }
        if (! empty($ctrImportsArr)) {
            $controllersImportStr = implode("\n", $ctrImportsArr)."\n";
        }

        $content .= "import {Entity, ResourceData} from '@/types/support';\n\n";
        $content .= $controllersImportStr;

        $content .= "let entities: Entity[] = [];\n\n";

        // Phase 1: declare entity shells (fields present, relations empty)
        foreach ($this->context->getConfig()->entities as $entity) {
            $content .= $this->generateEntityMetaShell($entity);
        }

        // Phase 2: create relations assignments (lazy references)
        foreach ($this->context->getConfig()->entities as $entity) {
            $content .= $this->generateEntityMetaRelations($entity);
            $content .= $this->generateEntityMetaFilters($entity);
            $content .= $this->generateEntityMetaActions($entity);
        }

        $exportNames = array_map(fn ($e) => Str::camel(Str::singular($e->getName())).'Entity', $this->context->getConfig()->entities);
        $content .= "\nexport { ".implode(', ', $exportNames)." };\n";

        // Write file
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate the entity declaration with fields and empty relations.
     */
    private function generateEntityMetaShell(Entity $entity): string
    {
        $entityName = $entity->getName();
        $entityVar = Str::camel(Str::singular($entityName));
        $fieldsMeta = $this->getColumnsMeta($entity);

        $content = <<<EOT
const {$entityVar}Entity: Entity = {
    name: '{$entityName}',
    baseRoute: '{$entity->getBaseRoute()}',
    singularName: '{$entity->getSingularName()}',
    singularNameLower: '{$entityVar}',
    pluralName: '{$entity->getPluralName()}',
    controller: {$entityName}Controller,
    inertiaComponent: '{$entity->getInertiaComponent()}', // Unnecessary , written in initial Inertia component call
    fields: [
        // Define fields for the entity
        {$fieldsMeta}
    ],
    relations: [],
    filters: [],
    actions: [],
    nameValueGetter: (entity: ResourceData) => entity.{$entity->getMainFieldName()},
};
entities.push({$entityVar}Entity);

EOT;

        return $content;
    }

    /**
     * Generate relations assignment for an entity.
     * This runs after all entities are declared so we can safely reference variables.
     * Related entities are referenced lazily as () => relatedEntityVar to avoid circular init problems.
     */
    private function generateEntityMetaRelations(Entity $entity): string
    {
        $relations = $entity->getRelations();
        if (empty($relations)) {
            return '';
        }

        $entityVar = Str::camel(Str::singular($entity->getName()));
        $entries = [];

        foreach ($relations as $relation) {
            $entries[] = $this->buildRelationEntry($entity, $relation);
        }

        $relationsBlock = implode(",\n    ", $entries);

        return <<<EOT
{$entityVar}Entity.relations = [
    {$relationsBlock}
];

EOT;
    }

    /**
     * Build a single relation entry string with safe (lazy) relatedEntity reference.
     */
    private function buildRelationEntry(Entity $entity, Relation $relation): string
    {
        // Related entity name and its variable (e.g. teamEntity)
        $relatedName = $relation->getRelatedEntityName();

        // Example: teamEntity, defined above in the generated ts file
        $relatedVar = $relatedName ? Str::camel(Str::singular($relatedName)).'Entity' : 'null';

        $relatedEntityRef = $relatedName ? "() => {$relatedVar}" : 'null';
        $relatedEntityNameStr = $relatedName ? "'{$relatedName}'" : 'null';
        $foreignKeyStr = $relation->getForeignKey() ? "'{$relation->getForeignKey()}'" : 'null';
        $localKeyStr = $relation->getLocalKey() ? "'{$relation->getLocalKey()}'" : 'null';
        $relatedKeyStr = $relation->getRelatedKey() ? "'{$relation->getRelatedKey()}'" : 'null';
        $relationNameStr = $relation->getRelationName() ? "'{$relation->getRelationName()}'" : 'null';
        $apiPathStr = $relation->getApiPath() ? "'{$relation->getApiPath()}'" : 'null';
        $typeStr = $relation->getType() ? $relation->getType()->value : '';

        // localEntityName is the owning entity name
        $localEntityName = $entity->getName();

        $relationController = $relation->hasRoute() && $relatedName
            ? "{$entity->getName()}{$relatedName}Controller"
            : 'null';

        // Build JS object literal for relation
        $entry = "{
        type: '{$typeStr}',
        localEntityName: '{$localEntityName}',
        relatedEntity: {$relatedEntityRef},
        relatedEntityName: {$relatedEntityNameStr},
        foreignKey: {$foreignKeyStr},
        localKey: {$localKeyStr},
        relatedKey: {$relatedKeyStr},
        relationName: {$relationNameStr},
        apiPath: {$apiPathStr},
        controller: {$relationController}
    }";

        return $entry;
    }

    private function generateEntityMetaFilters(Entity $entity): string
    {
        $filters = $entity->getFilters();
        if (empty($filters)) {
            return '';
        }

        $entityVar = Str::camel(Str::singular($entity->getName()));
        $entries = [];

        foreach ($filters as $filter) {
            $entries[] = $this->buildFilterEntry($entity, $filter);
        }

        $filtersBlock = implode(",\n    ", $entries);

        return <<<EOT
{$entityVar}Entity.filters = [
    {$filtersBlock}
];

EOT;
    }

    /**
     * Generate entity meta filters file for all entities.
     */
    private function buildFilterEntry(Entity $entity, Filter $filter): string
    {
        $nullStr = 'null';
        $label = $filter->label ?? Str::title(str_replace('_', ' ', $filter->field));
        $relatedEntityName = $filter->relatedEntityName ? "'{$filter->relatedEntityName}'" : $nullStr;

        $filterEntry = "{
        field: '{$filter->field}',
        label: '{$label}',
        relatedEntityName: {$relatedEntityName},
        type: '{$filter->type->value}'";

        if (! empty($filter->initialValues)) {
            $initialValuesStr = json_encode($filter->initialValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $filterEntry .= ",
        initialValues: {$initialValuesStr}";
        }

        if ($filter->dynamic) {
            // For simplicity, we assume dynamic is a boolean indicating if the filter is dynamic
            $filterEntry .= ',
        dynamic: (entity: Entity) => true // Customize this function as needed';
        }

        // Entity reference (lazy)
        // Example: teamEntity, defined above in the generated ts file
        $entityRef = Str::camel(Str::singular($entity->getName())).'Entity';
        $filterEntry .= ",
        entityRef: () => {$entityRef}
        ";

        $filterEntry .= "\n    }";

        return $filterEntry;
    }

    private function generateEntityMetaActions(Entity $entity): string
    {
        $actions = $entity->getActions();
        if (empty($actions)) {
            return '';
        }

        $entityVar = Str::camel(Str::singular($entity->getName()));
        $entries = [];

        foreach ($actions as $action) {
            $entries[] = $this->buildActionEntry($action);
        }

        $actionsBlock = implode(",\n    ", $entries);

        return <<<EOT
{$entityVar}Entity.actions = [
    {$actionsBlock}
];

EOT;
    }

    private function buildActionEntry(Action $action): string
    {
        $payload = $action->toArray();

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }

        return $json;
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
    private function generateEntitiesTsFiles(): void
    {
        // This would be something like resources/js/types/entities.ts
        /** @var string $path */
        $path = config('magic.paths.entity_types_file');
        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));
        $content = '';

        // Add imports
        $content .= "import {ResourceData} from '@/types/support';\n\n";

        // Generate entity interfaces
        foreach ($this->context->getConfig()->entities as $entity) {
            $entityName = $entity->getName();
            $content .= "export interface {$entityName} extends ResourceData {\n";

            $fields = '';
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
        }

        // Write file
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate the metadata for the entity columns (fields).
     * It differs from the getColumnDef method in that it returns
     * the metadata for the fields, not the column definitions
     * that are strictly formatted for the table.
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
     * Generate helper files for each entity.
     * For example , functions to get column definitions for tables, etc.
     * The function helpers are written for each entity in a separate file for type safety
     * open for modification, and better organization.
     *
     * @throws ReflectionException
     */
    private function generateEntityHelperFiles(): void
    {
        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateEntityHelperFile($entity);
        }
    }

    /**
     * Generate a helper file for a specific entity.
     */
    private function generateEntityHelperFile(Entity $entity): void
    {
        $entityName = $entity->getName();
        $entitySingularLower = Str::camel(Str::singular($entity->getName()));
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
import {ArrowUp,ArrowDown,ArrowUpDown} from "lucide-vue-next";
import Avatar from "@/components/Avatar.vue";
import {parseBool, formatDate} from "@/lib/app";
$entityImports
$supportImports

export function get{$entityName}Columns(): ColumnDef<{$entityName}>[] {
    return [
        {$this->getColumnDef($entity, 8)}
    ];
}
EOT;
        app(GenerateFileAction::class)($path, $content);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate the column definition for the entity.
     */
    private function getColumnDef(Entity $entity, int $indent = 0): string
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

        // Commented as getTableFields will add BelongsTo fields as regular fields, For now i don't see the reason when we dont want to include them,
        // so they are directly added as fields. Even in Eloquent they are used as select fields ( nested )
        //
        // Add relations as columns
        /*foreach ($entity->getRelations() as $relation) {
            if ($relation->getType() === RelationType::BELONGS_TO) {
                Log::channel('magic')->info("Processing belongsTo relation: {$relation->getRelationName()}");
                $column = $this->tsHelper->writeTableColumn(Field::fromRelation($relation), $entity);
                $columns[] = $column;
            } else {
                Log::channel('magic')->info("Skipping non-belongsTo relation: {$relation->getRelationName()}");
            }
        }*/

        // $indentStr = str_repeat("\t", $indent);
        return implode(",\n", $columns);
    }

    /**
     * Copy Vue files from the package to the resources/js directory.
     */
    private function copyMagicFiles(): void
    {
        $source = __DIR__.'/../../../stubs/magic';
        $destination = base_path();

        // Use the CopyDirectoryAction to copy files
        $filesCopied = app(CopyDirectoryAction::class)($source, $destination, false, true);
        $this->context->registerGeneratedFile($filesCopied);
    }
}
