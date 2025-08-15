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
        $tsType = TypeHelper::migrationTypeToTsType($field->getType());
        $strEnableSorting = $field->isSortable() ? 'true' : 'false';
        if ($field->isSortable()) {
            $fieldHeader = static::writeSortableColumnHeader($field);
        } else {
            $fieldHeader = "'{$field->getTitle()}'";
        }

        return "{
                id: '{$field->getName()}',
                header: {$fieldHeader},
                accessorKey: '{$field->getName()}',
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
        $tsType = TypeHelper::migrationTypeToTsType($field->getType());
        $fieldMeta = "{
            name: '{$field->getName()}',
            type: '{$tsType}',
            nullable: " . ($field->isNullable() ? 'true' : 'false') . ",
            length: " . ($field->getLength() !== null ? $field->getLength() : 'null') . ",
            precision: " . ($field->getPrecision() !== null ? $field->getPrecision() : 'null') . ",
            scale: " . ($field->getScale() !== null ? $field->getScale() : 'null') . ",
            default: " . ($field->getDefault() !== null ? "'{$field->getDefault()}'" : 'null') . ",
            comment: " . ($field->getComment() !== null ? "'{$field->getComment()}'" : 'null') . ",
            sortable: " . ($field->isSortable() ? 'true' : 'false') . ",
            searchable: " . ($field->isSearchable() ? 'true' : 'false') . "
        }";
        return $fieldMeta;
    }
}
