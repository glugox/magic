<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;
use Illuminate\Support\Facades\Log;

class DefaultRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        Log::channel('magic')
            ->info(
                'Rendering default cell',
                [
                    'renderer' => static::class,
                ]
            );
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
