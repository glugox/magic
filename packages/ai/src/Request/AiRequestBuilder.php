<?php

namespace Glugox\Ai\Request;

use Glugox\Ai\Contracts\AiDriver;

class AiRequestBuilder
{
    protected ?AiDriver $driver = null;

    protected string $promptText = '';

    public function text(string $promptText): self
    {
        $this->promptText = $promptText;

        return $this;
    }

    public function driver(AiDriver $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Build and return the AiRequest instance.
     */
    public function build(): AiRequest
    {
        // Validate all
        if (is_null($this->driver)) {
            throw new \InvalidArgumentException('Driver must be set.');
        }

        if (empty($this->promptText)) {
            throw new \InvalidArgumentException('Prompt text cannot be empty.');
        }

        return new AiRequest($this->driver, $this->promptText);
    }

    /**
     * Make a new instance of the AiRequestBuilder.
     */
    public static function make(): self
    {
        return new self;
    }
}
