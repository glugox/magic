<?php

namespace Glugox\Ai;

use Glugox\Ai\Drivers\Driver;
use Glugox\Ai\Request\AiRequest;
use Glugox\Ai\Request\AiRequestBuilder;

class AiManager
{
    /**
     * Ask a question to the AI model and get a response.
     */
    public function ask(AiRequest|string $aiRequest): AiResponse
    {
        if (is_string($aiRequest)) {
            // If a string is provided, create a default AiRequest using the configured driver
            $aiRequest = AiRequestBuilder::make()
                ->driver(Driver::default())
                ->text($aiRequest)
                ->build();
        }

        return $aiRequest->handle();
    }
}
