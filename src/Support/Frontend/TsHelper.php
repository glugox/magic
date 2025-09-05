<?php

namespace Glugox\Magic\Support\Frontend;

use Glugox\Magic\Enums\CrudActionType;
use Glugox\Magic\Helpers\ValidationHelper;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Support\Frontend\Renderers\Cell\Renderer;
use Glugox\Magic\Support\TypeHelper;
use Glugox\Magic\Validation\EntityRuleSet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TsHelper
{
    /**
     * Constructor
     */
    public function __construct(
        protected TypeHelper $typeHelper,
        protected ValidationHelper $validationHelper,
    ){}

    /**
     * Write import statements for a given entity.
     * import { type User } from '@/types/entities';",
     *
     * @param Entity $entity The entity for which to write imports.
     * @param Entity|null $parentEntity The parent entity if this is a nested entity.
     * @param array|null $options Options to customize the imports. Supported options:
     *                       - 'model' (bool): Whether to import the model type. Default is true.
     *                       - 'controller' (bool): Whether to import the controller. Default is true.
     * @return string
     */
    public function writeEntityImports(Entity $entity, ?Entity $parentEntity = null, ?array $options = []): string
    {
        $imports = [];
        $options = array_merge([
            'model' => true,
            'controller' => true,
        ], $options);

        if ($options['model']) {
            $imports[] = "import { type {$entity->name} } from '@/types/entities';";
        }
        if ($options['controller']) {
            $controllerClass = $entity->name . 'Controller';
            $controllerRelativePath = "{$controllerClass}";
            if ($parentEntity) {
                $controllerClass = $parentEntity->name . $controllerClass;
                $controllerRelativePath = $parentEntity->getName() . "/{$controllerClass}";
            }
            $imports[] = "import $controllerClass from '@/actions/App/Http/Controllers/{$controllerRelativePath}';";
        }
        return implode("\n", $imports);
    }

    /**
     * writeIndexPageSupportImports
     */
    public function writeIndexPageSupportImports(Entity $entity): string
    {
        $imports = [
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
     * Write relation metadata for a given relation.
     * This is used to generate TypeScript interfaces or types.
     */
    public function writeRelationMeta(Entity $entity, Relation $relation): string
    {
        $relatedEntityName = $relation->getRelatedEntityName();
        $relatedEntityStr = $relatedEntityName ? "'{$relatedEntityName}'" : 'null';
        $foreignKeyStr = $relation->getForeignKey() ? "'{$relation->getForeignKey()}'" : 'null';
        $localKeyStr = $relation->getLocalKey() ? "'{$relation->getLocalKey()}'" : 'null';
        $relationNameStr = $relation->getRelationName() ? "'{$relation->getRelationName()}'" : 'null';

        return "{
            type: '{$relation->getType()->value}',
            localEntity: '{$entity->name}',
            entityName: {$relatedEntityStr},
            relatedEntity: null, // Related entity can be set later if needed
            foreignKey: {$foreignKeyStr},
            localKey: {$localKeyStr},
            relationName: {$relationNameStr},
        }";
    }

    /**
     * Write field metadata for a given field.
     * This is used to generate TypeScript interfaces or types.
     */
    public function writeFieldMeta(Field $field, EntityRuleSet $entityValidationRuleSet): string
    {
        $tsType = $this->typeHelper->migrationTypeToTsType($field->type);

        $rulesCreateRuleSet = $entityValidationRuleSet->getCreateRuleSetForField($field->name);
        $rulesUpdateRuleSet = $entityValidationRuleSet->getUpdateRuleSetForField($field->name);

        $rulesStr =
            "rules: {
                create : [".implode(', ', array_map(fn($r) => "'$r'", $rulesCreateRuleSet ? $rulesCreateRuleSet->getRules() : []))."],
                update : [".implode(', ', array_map(fn($r) => "'$r'", $rulesUpdateRuleSet ? $rulesUpdateRuleSet->getRules() : [])) ."]
            }";

        return "{
            name: '{$field->name}',
            type: '{$field->migrationType()}',
            label: '{$field->label()}',
            nullable: ".($field->nullable ? 'true' : 'false').',
            sometimes: '.($field->sometimes ? 'true' : 'false').',
            required: '.($field->required ? 'true' : 'false').',
            length: '.($field->length !== null ? $field->length : 'null').',
            precision: '.($field->precision !== null ? $field->precision : 'null').',
            scale: '.($field->scale !== null ? $field->scale : 'null').',
            '.$rulesStr.',
            default: '.($field->default !== null ? "'{$field->default}'" : 'null').',
            comment: '.($field->comment !== null ? "'{$field->comment}'" : 'null').',
            sortable: '.($field->sortable ? 'true' : 'false').',
            searchable: '.($field->searchable ? 'true' : 'false').',
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
    public function writeRelationSidebarItems(Entity $entity, Config $config, ?CrudActionType $crudActionType = CrudActionType::UPDATE): string
    {
        /**
         *  SquareMinus - Default
         *  Link - BelongsTo
         *  CornerDownRight - HasOne
         *  FolderTree - HasMany, MorphTo, MorphMany
         *  GitCompareArrows - ManyToMany, BelongsToMany
         * /
         */
        $icons = [
            RelationType::HAS_ONE->value => 'CornerDownRight',
            RelationType::BELONGS_TO->value => 'Link',
            RelationType::HAS_MANY->value => 'FolderTree',
            RelationType::BELONGS_TO_MANY->value => 'FolderTree',
            RelationType::MORPH_MANY->value => 'FolderTree'
        ];

        $items = [];
        foreach ($entity->getRelations() as $relation) {

            // Do not show belongsTo relations
            if ($relation->type === RelationType::BELONGS_TO || $relation->type === RelationType::HAS_ONE) {
               continue;
            }
            $relatedEntityName = $relation->getRelatedEntityName();
            if(!$relatedEntityName) {
                Log::channel('magic')->warning("Relation {$relation->getRelationName()} of entity {$entity->name} has no related entity name.");
                continue;
            }

            switch ($crudActionType) {
                case CrudActionType::CREATE:
                    $baseUrlTs = 'create().url';
                    break;
                default:
                    $baseUrlTs = 'show(item.data.id).url';
                    break;
            }

            $relatedEntity = $config->getEntityByName($relatedEntityName);
            if ($relatedEntity) {
                $relationTitle = $relatedEntity->getPluralName();
                $relationFolder = $relatedEntity->getRouteName();
                $icon = $icons[$relation->getType()->value] ?? 'SquareMinus';

                $items[] = <<<VUE
            {
                title: '{$relationTitle}',
                href:  {$baseUrlTs} + `/{$relationFolder}`,
                icon: {$icon},
            }
VUE;
            }
        }
        return implode(",\n", $items);
    }
}
