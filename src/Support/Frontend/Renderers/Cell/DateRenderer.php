<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

use function implode;

class DateRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $tableCellLines = [
            "const strVal: string = cell.getValue() ? String(cell.getValue()) : '';",
            'return formatDate(strVal)',
        ];

        $indent = str_repeat(' ', 15);
        $formattedDate = implode("\n$indent", $tableCellLines);

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
