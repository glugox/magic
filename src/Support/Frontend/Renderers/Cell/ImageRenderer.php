<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;
use Illuminate\Support\Facades\Log;

class ImageRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        Log::channel('magic')
            ->info(
                'Rendering image cell',
                [
                    'renderer' => static::class,
                ]
            );

        return new RendererResult(
            content: $this->getTsForImage(),
            type: $this->getType()
        );
    }

    private function getTsForImage(): string
    {
        $cellRenderer = "
                {
                const value = cell.getValue() as string | null;
                const finalValue = cell.row.original.name ?? value;
                if (!finalValue) return '';

                // Render our custom Vue component
                return h(Avatar, { name: finalValue });
            }";

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

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'image';
    }
}
