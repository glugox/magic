<?php

namespace Glugox\Magic\Support\Frontend;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\TypeHelper;

class TsHelper
{
    /**
     * Write table columns for a given entity.
     */
    public static function writeTableColumns(Entity $entity): string
    {
        $columns = '';
        foreach ($entity->getFields() as $field) {
            $columns .= static::writeTableColumn($field);
        }

        return $columns;
    }

    /**
     * Write a single table column definition.
     */
    public static function writeTableColumn(Field $field)
    {
        $tsType = TypeHelper::migrationTypeToTsType($field->type);
        $strEnableSorting = $field->isSortable() ? 'true' : 'false';
        if ($field->isSortable()) {
            $fieldHeader = static::writeSortableColumnHeader($field);
        } else {
            $fieldHeader = "'{$field->getTitle()}'";
        }

        return "{
                id: '{$field->name}',
                header: {$fieldHeader},
                accessorKey: '{$field->name}',
                cell: ({ cell }) => {
                    const value = cell.getValue() as {$tsType};
                    return value ? value.toString() : '';
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
        $fieldTitle = $field->getTitle();
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
            type: '{$tsType}',
            nullable: ".($field->nullable ? 'true' : 'false').',
            length: '.($field->length !== null ? $field->length : 'null').',
            precision: '.($field->precision !== null ? $field->precision : 'null').',
            scale: '.($field->scale !== null ? $field->scale : 'null').',
            default: '.($field->default !== null ? "'{$field->default}'" : 'null').',
            comment: '.($field->comment !== null ? "'{$field->comment}'" : 'null').',
            sortable: '.($field->isSortable() ? 'true' : 'false').',
            searchable: '.($field->isSearchable() ? 'true' : 'false').'
        }';
    }
}
