<?php

namespace Glugox\Ai;

class AiResponse
{
    protected string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function text(): string
    {
        return $this->text;
    }
}
