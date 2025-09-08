<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class ImageRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        return new RendererResult(
            content: $this->getTsForImage(),
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'image';
    }

    private function getTsForImage(): string
    {
        $lines = [
            "const value: string = cell.getValue() as string ?? '';",
            'const finalValue:string = String(cell.row.original.name) ?? String(value);',
            "if (!finalValue) return '';",
            'return h(Avatar, { name: finalValue, src: "" });'
        ];

        $indent = str_repeat(' ', 15);
        $cellRenderer = implode("\n$indent", $lines);

        $productionRenderer = "
                {
                const value = cell.getValue() as string | null;
                return value
                    ? h('img', { src: value, alt: '', class: 'h-10 w-10 object-cover rounded' })
                    : '';
    }";

        // Return the renderer based on the environment
        return env('APP_ENV') === 'local' ? $cellRenderer : $productionRenderer;
    }
}
