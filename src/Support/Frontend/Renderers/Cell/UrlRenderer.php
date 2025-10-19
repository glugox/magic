<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class UrlRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $formattedStr = "
{
    const value = cell.getValue() as string | null;
    if (!value) return '';

    const display = value.length > 32 ? value.slice(0, 32) + '...' : value;
    return h('a', { title: value, href: value, class: 'text-small underline', target: '_blank' }, display);
}";

        return new RendererResult(
            content: $formattedStr,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'url';
    }
}
