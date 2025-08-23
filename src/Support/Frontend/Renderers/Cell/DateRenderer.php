<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class DateRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $formattedDate = "return cell.getValue() ? new Date(cell.getValue()).toLocaleDateString() : ''";

        return new RendererResult(
            content: $formattedDate,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'date';
    }
}
