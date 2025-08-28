<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class NumberRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        $formattedStr = [
            '// Glugox\Magic\Support\Frontend\Renderers\Cell\NumberRenderer:',
            "const cellValue = cell.getValue() ?? null; return cellValue !== null ? cellValue.toLocaleString() : ''"
        ];

        $indent = 15;
        $indentStr = str_repeat(' ', $indent);

        return new RendererResult(
            content: implode("\n$indentStr", $formattedStr),
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'number';
    }
}
