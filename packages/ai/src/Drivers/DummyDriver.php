<?php

namespace Glugox\Ai\Drivers;

use Glugox\Ai\AiResponse;
use Glugox\Ai\Contracts\AiDriver;

class DummyDriver implements AiDriver
{
    protected array $map = [
        'What is the capital of France?' => 'Paris',
        'What is 2 + 2?' => '4',
        'What is the capital of Germany?' => 'Berlin',
    ];

    /**
     * Simulates asking an AI model a question and getting a response.
     */
    public function ask(string $prompt): AiResponse
    {
        return new AiResponse($this->map[$prompt] ?? "I don't know");
    }
}
