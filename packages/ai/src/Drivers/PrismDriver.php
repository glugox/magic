<?php

namespace Glugox\Ai\Drivers;

use Glugox\Ai\AiResponse;
use Glugox\Ai\Contracts\AiDriver;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Text\PendingRequest as PendingTextRequest;

class PrismDriver implements AiDriver
{
    /**
     * Constructor
     */
    public function __construct(protected ?PendingTextRequest $request = null)
    {
        $this->request = $request ?? Prism::text();
    }

    public function ask(string $prompt): AiResponse
    {
        $defaultModel = config('ai.drivers.ollama.model');
        Log::channel('magic')->info("Using Ollama model: {$defaultModel}");
        Log::channel('magic')->info('Ollama URL: '.config('ai.drivers.ollama.url'));

        // Pass the url to PrismPHP
        $response = $this->request
            ->using(Provider::Ollama, $defaultModel, [
                'url' => config('ai.drivers.ollama.url'),
            ])
            ->withPrompt($prompt)
            ->withClientOptions([
                'timeout' => 120, // Increase timeout for long responses
            ])
            ->asText();

        return new AiResponse($response->text);
    }
}
