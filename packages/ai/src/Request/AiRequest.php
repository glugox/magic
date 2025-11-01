<?php

namespace Glugox\Ai\Request;

use Glugox\Ai\AiResponse;
use Glugox\Ai\Contracts\AiDriver;

class AiRequest
{
    public function __construct(
        protected AiDriver $driver,
        protected string $prompt = '',
    ) {}

    /**
     * Handle the AI request and get a response.
     */
    public function handle(): AiResponse
    {
        // Here you can add any pre-processing logic if needed
        return $this->driver->ask($this->getPrompt());
    }

    /**
     * Get the prompt for the AI request.
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }
}
