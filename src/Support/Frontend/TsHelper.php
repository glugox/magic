<?php

namespace Glugox\Magic\Support\Frontend;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\Cell\Renderer;
use Glugox\Magic\Support\TypeHelper;
use Illuminate\Support\Facades\Log;

class TsHelper
{
    /**
     * Constructor
     */
    public function __construct(
        protected TypeHelper $typeHelper
    ){}

    /**
     * Write import statements for a given entity.
     * import { type User } from '@/types/entities';",
     */
    public function writeEntityImports(Entity $entity): string
    {
        $imports = [
            "import { type {$entity->name} } from '@/types/entities';",
            "import {$entity->name}Controller from '@/actions/App/Http/Controllers/{$entity->name}Controller'"
        ];
        /*foreach ($entity->getFields() as $field) {

        }*/
        return implode("\n", $imports)."\n";
    }

    /**
     * writeIndexPageSupportImports
     */
    public function writeIndexPageSupportImports(Entity $entity): string
    {
        $imports = [
            "import { parseBool } from '@/lib/app';",
            "import { type Entity, type Field } from '@/types/support';",
            // Eg. import { getUserColumns, getUserEntityMeta } from '@/helpers/users_helper';
            "import { get{$entity->name}Columns, get{$entity->name}EntityMeta } from '@/helpers/{$entity->getFolderName()}_helper'",
            "import { type PaginationObject, type TableFilters } from '@/types/support';"
        ];
        return implode("\n", $imports)."\n";
    }

    /**
     * In form pages we need some support imports.
     */
    public function writeFormPageSupportImports(Entity $entity): string
    {
        $imports = [
            // Eg. import { getUserColumns, getUserEntityMeta } from '@/helpers/users_helper';
            "import { get{$entity->name}EntityMeta } from '@/helpers/{$entity->getFolderName()}_helper'",
        ];
        return implode("\n", $imports)."\n";
    }

    /**
     * In entity helper files like @/helpers/users_helper'
     * we need some support imports.
     */
    public function writeEntityHelperSupportImports()
    {
        $imports = [
            "import { type Entity, type Field } from '@/types/support';",
        ];
        return implode("\n", $imports)."\n";
    }

    /**
     * Write a single table column definition.
     */
    public function writeTableColumn(Field $field, Entity $entity): string
    {
        $tsType = $this->typeHelper->migrationTypeToTsType($field->type);
        $strEnableSorting = $field->sortable ? 'true' : 'false';
        if ($field->sortable) {
            $fieldHeader = $this->writeSortableColumnHeader($field);
        } else {
            $fieldHeader = "'{$field->label()}'";
        }

        $cellRenderer = $this->writeTableCell($field, $entity);

        return "
        {
            id: '{$field->name}',
            header: {$fieldHeader},
            accessorKey: '{$field->name}',
            cell: ({ cell }) => {
               // Render the cell content based on the field front type : $tsType->value ( server type: {$field->type->value} )
               $cellRenderer
            },
            enableSorting: {$strEnableSorting},
            enableHiding: true,
        }";
    }

    /**
     * Write sortable column header for a given entity.
     */
    public function writeSortableColumnHeader(Field $field): string
    {
        $fieldTitle = $field->label();
        return "
            ({ column }) => {
                return h(Button, {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }, () => ['{$fieldTitle}', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
            }";
    }

    /**
     * Write field metadata for a given field.
     * This is used to generate TypeScript interfaces or types.
     */
    public function writeFieldMeta(Field $field)
    {
        $tsType = $this->typeHelper->migrationTypeToTsType($field->type);

        return "{
            name: '{$field->name}',
            type: '{$tsType->value}',
            label: '{$field->label()}',
            nullable: ".($field->nullable ? 'true' : 'false').',
            sometimes: '.($field->sometimes ? 'true' : 'false').',
            length: '.($field->length !== null ? $field->length : 'null').',
            precision: '.($field->precision !== null ? $field->precision : 'null').',
            scale: '.($field->scale !== null ? $field->scale : 'null').',
            default: '.($field->default !== null ? "'{$field->default}'" : 'null').',
            comment: '.($field->comment !== null ? "'{$field->comment}'" : 'null').',
            sortable: '.($field->sortable ? 'true' : 'false').',
            searchable: '.($field->searchable ? 'true' : 'false').'
        }';
    }

    /**
     * Write a table cell renderer for a given field.
     * This is used to generate the cell content in the table.
     */
    private function writeTableCell(Field $field, Entity $entity): string
    {
        $renderer = Renderer::getRenderer($field);
        // If the renderer is a custom one, we can use it directly
        $renderResult = $renderer->render($field, $entity);

        return $renderResult->content;
    }

    /**
     * Write default value for a given field.
     */
    public function writeValue(mixed $default): string
    {
        if (is_string($default)) {
            return "'".addslashes($default)."'";
        } elseif (is_bool($default)) {
            return $default ? 'true' : 'false';
        } elseif (is_null($default)) {
            return 'null';
        } elseif (is_array($default)) {
            $items = array_map(fn ($item) => $this->writeValue($item), $default);

            return '['.implode(', ', $items).']';
        } else {
            return (string) $default;
        }
    }

    /**
     * Write relation sidebar items for a given entity.
     * This is used on resource edit form pages to create sidebar navigation items.
     * So we have sidebar items like Orders, Products, etc.
     *
     * Writes something like:
     *
     * {
     * title: 'Relationships',
     * href: edit(item.id),
     * },
     * {
     * title: 'Settings',
     * href: edit(item.id),
     * },
     */
    public function writeRelationSidebarItems(Entity $entity, Config $config): string
    {
        $items = [];
        $entityHref = $entity->getHref();
        foreach ($entity->getRelations() as $relation) {
            $relatedEntityName = $relation->getRelatedEntityName();
            if(!$relatedEntityName) {
                Log::channel('magic')->warning("Relation {$relation->getRelationName()} of entity {$entity->name} has no related entity name.");
                continue;

            }
            $relatedEntity = $config->getEntityByName($relatedEntityName);
            if ($relatedEntity) {
                $relationTitle = $relatedEntity->getPluralName();
                $relationFolder = $relatedEntity->getFolderName();
                $href = $entityHref . "/{$relationFolder}";
                $items[] = "{
                    title: '{$relationTitle}',
                    href:  edit(item.id).url + `/{$relationFolder}`,
                }";
            }
        }
        return implode(",\n", $items);
    }
}
