<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;
use Illuminate\Support\Facades\Log;

class DateRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        Log::channel('magic')
            ->info(
                'Rendering date cell',
                [
                    'renderer' => static::class,
                ]
            );
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
