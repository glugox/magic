<?php

namespace Glugox\Ai\Contracts;

use Glugox\Ai\AiResponse;

interface AiDriver
{
    public function ask(string $prompt): AiResponse;
}
