<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class NameRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        // If this field is the primary name field, show avatar
        $showAvatar = $this->isPrimaryNameField($field, $entity);
        $avatarDefinition = "const avatarUrl = '';";
        $avatarJs = $showAvatar ? 'h(Avatar, { name: nameVal, src: avatarUrl }),' : '';

        $indent = str_repeat(' ', 15);
        $indent2 = str_repeat(' ', 2);
        $tableCellLines = [
            "const href = '{$entity->getHref()}';",
            'const id = cell.row.original.id;',
            "const nameVal: string = cell.getValue() ? String(cell.getValue()) : '';",
            $avatarDefinition,
            "return h('a', {",
            $indent2."href: href + '/' + id + '/edit',",
            $indent2."class: 'flex items-center gap-2 text-blue-600 hover:underline'",
            '}, [',
            $indent2.'#AvatarPlaceholder#',
            $indent2."h('span', null, nameVal)",
            ']);'
        ];

        $tableCellStr = implode("\n$indent", $tableCellLines);

        $tableCellStr = str_replace('#AvatarPlaceholder#', $avatarJs, $tableCellStr);

        return new RendererResult(
            content: (string) $tableCellStr,
            type: $this->getType()
        );
    }

    /**
     * Checks if this field is non primary name field. For example, if the field is 'title' or 'email' or 'username'.
     * We will choose the first one only to show avatar with name.
     */
    public function isPrimaryNameField(Field $field, Entity $entity): bool
    {
        $nameField = $entity->getPrimaryNameField();

        return $nameField && $field->name === $nameField->name;
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'name';
    }
}
