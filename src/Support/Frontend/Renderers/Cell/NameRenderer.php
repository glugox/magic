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
        // If the value is an object, use its name property
        $tableCellStr = "

                    const href = '{$entity->getHref()}';
                    const id = cell.row.original.id;
                    const nameVal = cell.getValue() ?? '';
                    const avatarUrl = cell.row.original.avatar_url ?? '';

                    return h('a', {
                        href: href + '/' + id,
                        class: 'flex items-center gap-2 text-blue-600 hover:underline'
                    }, [
                        h(Avatar, { name: nameVal, src: avatarUrl }),
                        h('span', null, nameVal)
                    ]);
                ";

        return new RendererResult(
            content: (string) $tableCellStr,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'name';
    }
}
