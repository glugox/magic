<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class DefaultRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $formattedStr = "return cell.getValue() ?? ''";

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
        return 'default';
    }
}
