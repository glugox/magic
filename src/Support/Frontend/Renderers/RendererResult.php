<?php

namespace Glugox\Magic\Support\Frontend\Renderers;

class RendererResult
{
    /**
     * The rendered content.
     */
    public string $content;

    /**
     * The type of the renderer.
     */
    public string $type;

    /**
     * RendererResult constructor.
     */
    public function __construct(string $content, string $type)
    {
        $this->content = $content;
        $this->type = $type;
    }
}
