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
        $relatedEntity = $belongsTo->getLocalEntity();
        $tableCellStr = "
                    const relatedEntity = cell.row.original.{$belongsTo->getRelationName()};
                    const href = '{$belongsTo->getHref()}';
                    const relId = cell.row.original.id;
                    if (!relatedEntity) return '—';

                    return h('a', {
                        href: href + '/' + relId,
                        class: 'flex items-center gap-2 text-blue-600 hover:underline'
                    }, [
                        h(Avatar, { name: relatedEntity.name, src: relatedEntity.avatar_url ?? '' }),
                        h('span', null, relatedEntity.name)
                    ]);
                ";

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
