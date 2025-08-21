<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;
use Illuminate\Support\Facades\Log;

class BooleanRenderer extends Renderer
{
    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        Log::channel('magic')
            ->info(
                'Rendering boolean cell',
                [
                    'renderer' => static::class,
                ]
            );
        $formattedValue = "return h(Checkbox, { 'modelValue': parseBool(cell.getValue()), disabled: true })";

        return new RendererResult(
            content: $formattedValue,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'boolean';
    }
}
