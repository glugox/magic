<?php

namespace Glugox\Magic\Support\Frontend;

use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\TypeHelper;

class TsHelper
{
    /**
     * Write a single table column definition.
     */
    public static function writeTableColumn(Field $field)
    {
        $tsType = TypeHelper::migrationTypeToTsType($field->type);
        $strEnableSorting = $field->sortable ? 'true' : 'false';
        if ($field->sortable) {
            $fieldHeader = static::writeSortableColumnHeader($field);
        } else {
            $fieldHeader = "'{$field->title()}'";
        }

        $cellRenderer = static::writeTableCell($field);

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
    /**
     * Write a table cell renderer for a given field.
     * Uses the FieldType enum for correct formatting and masks sensitive fields.
     */
    private static function writeTableCell(Field $field)
    {
        $type = $field->type; // FieldType enum
        $cellRenderer = '';

        switch ($type) {
            case FieldType::STRING:
            case FieldType::TEXT:
            case FieldType::CHAR:
            case FieldType::MEDIUM_TEXT:
            case FieldType::LONG_TEXT:
            case FieldType::EMAIL:
            case FieldType::IP_ADDRESS:
            case FieldType::UUID:
            case FieldType::ENUM:
                $cellRenderer = "return cell.getValue() ?? ''";
                break;

            case FieldType::INTEGER:
            case FieldType::BIG_INTEGER:
            case FieldType::BIG_INCREMENTS:
            case FieldType::SMALL_INTEGER:
            case FieldType::TINY_INTEGER:
            case FieldType::UNSIGNED_INTEGER:
            case FieldType::UNSIGNED_BIG_INTEGER:
            case FieldType::UNSIGNED_SMALL_INTEGER:
            case FieldType::UNSIGNED_TINY_INTEGER:
            case FieldType::DECIMAL:
            case FieldType::FLOAT:
            case FieldType::DOUBLE:
                $cellRenderer = "return cell.getValue() !== null ? cell.getValue().toLocaleString() : ''";
                break;

            case FieldType::BOOLEAN:
                $cellRenderer = "return h(Checkbox, { 'modelValue': parseBool(cell.getValue()), disabled: true })";
                break;

            case FieldType::DATE:
            case FieldType::DATETIME:
            case FieldType::TIME:
            case FieldType::TIMESTAMP:
            case FieldType::YEAR:
                $cellRenderer = "return cell.getValue() ? new Date(cell.getValue()).toLocaleDateString() : ''";
                break;

            case FieldType::IMAGE:
                $cellRenderer = self::getTsForImage();
                break;

            case FieldType::PASSWORD:
            case FieldType::FILE:
            case FieldType::SECRET:
            case FieldType::TOKEN:
                // Mask sensitive values
                $cellRenderer = "return cell.getValue() ? '••••••' : ''";
                break;

            case FieldType::JSON:
            case FieldType::JSONB:
            case FieldType::BINARY:
            case FieldType::FOREIGN_ID:
                $cellRenderer = "return cell.getValue() !== null ? JSON.stringify(cell.getValue()) : ''";
                break;

            default:
                $cellRenderer = "return cell.getValue() !== null ? cell.getValue().toString() : ''";
        }

        return $cellRenderer;
    }

    /**
     * @return string
     */
    public static function getTsForImage(): string
    {
        $cellRenderer = "
                {


                const value = cell.getValue() as string | null;
                const finalValue = cell.row.original.name ?? value;
                if (!finalValue) return '';

                // Render our custom Vue component
                return h(Avatar, { name: finalValue });
            }";

        $productionRenderer = "
                {
                const value = cell.getValue() as string | null;
                return value
                    ? h('img', { src: value, alt: '', class: 'h-10 w-10 object-cover rounded' })
                    : '';
    }";

        // Return the renderer based on the environment
        return  env('APP_ENV') === 'local'  ? $cellRenderer : $productionRenderer;
    }

}
