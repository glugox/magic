<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class BelongsToRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $belongsTo = $field->belongsTo();
        $showAvatar = true;
        $avatarDefinition = "const avatarUrl = '';";
        // @phpstan-ignore-next-line
        $avatarJs = $showAvatar ? 'h(Avatar, { name: nameVal, src: "" }),' : '';
        $indent = str_repeat(' ', 15);
        $indent2 = str_repeat(' ', 2);
        $tableCellLines = [
            "const relatedEntity = cell.row.original.{$belongsTo->getRelationName()};",
            "const href = '{$belongsTo->getHref()}';",
            'const relId = cell.row.original.id;',
            "if (!relatedEntity) return '—';",
            'const nameVal: string = relatedEntity.name;',
            $avatarDefinition,
            "return h('a', {",
            $indent2."href: href + '/' + relId,",
            $indent2."class: 'flex items-center gap-2 text-blue-600 hover:underline'",
            '}, [',
            $indent2.'#AvatarPlaceholder#',
            $indent2."h('span', null, nameVal)",
            ']);'
        ];

        $tableCellStr = implode("\n$indent", $tableCellLines);

        $tableCellStr = str_replace('#AvatarPlaceholder#', $avatarJs, $tableCellStr);

        return new RendererResult(
            content: $tableCellStr,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'belongsTo';
    }
}
