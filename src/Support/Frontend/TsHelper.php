<?php

namespace Glugox\Magic\Support\Frontend;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\Cell\Renderer;
use Glugox\Magic\Support\TypeHelper;

class TsHelper
{
    /**
     * Write a single table column definition.
     */
    public static function writeTableColumn(Field $field, Entity $entity): string
    {
        $tsType = TypeHelper::migrationTypeToTsType($field->type);
        $strEnableSorting = $field->sortable ? 'true' : 'false';
        if ($field->sortable) {
            $fieldHeader = static::writeSortableColumnHeader($field);
        } else {
            $fieldHeader = "'{$field->title()}'";
        }

        $cellRenderer = static::writeTableCell($field, $entity);

        return "{
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
    public static function writeSortableColumnHeader(Field $field): string
    {
        $fieldTitle = $field->title();
        $header = <<< HEADER
({ column }) => {
            return h(Button, {
                variant: 'ghost',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            }, () => ['{$fieldTitle}', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
        }
HEADER;

        return $header;

    }

    /**
     * Write field metadata for a given field.
     * This is used to generate TypeScript interfaces or types.
     */
    public static function writeFieldMeta(Field $field)
    {
        $tsType = TypeHelper::migrationTypeToTsType($field->type);

        return "{
            name: '{$field->name}',
            type: '{$tsType->value}',
            nullable: ".($field->nullable ? 'true' : 'false').',
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
    private static function writeTableCell(Field $field, Entity $entity): string
    {
        $renderer = Renderer::getRenderer($field);
        // If the renderer is a custom one, we can use it directly
        $renderResult = $renderer->render($field, $entity);

        return $renderResult->content;
    }
}
