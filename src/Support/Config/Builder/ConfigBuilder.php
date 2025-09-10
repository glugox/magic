<?php

namespace Glugox\Magic\Support\Config\Builder;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Readers\SchemaReader;
use JsonException;
use RuntimeException;

class ConfigBuilder
{
    // Constants to define source types
    public const string SOURCE_TYPE_GRAPHQL = 'graphql';

    public const string SOURCE_TYPE_SDL = 'sdl';

    public const string SOURCE_TYPE_JSON = 'json';

    protected ?string $graphql;

    protected ?string $sdl = null;

    protected ?string $json = null;

    /**
     * Determine if we are loading JSON or SDL
     */
    protected string $sourceType;

    public function __construct(
        protected ?SchemaReader $schemaReader
    ) {}

    /**
     * Create a new ConfigBuilder instance.
     */
    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * Build and return the Config object by parsing the SDL string.
     *
     * @throws JsonException
     */
    public function build(): Config
    {
        $config = app(Config::class);

        // Load source based on source type
        /*if ($this->sourceType === self::SOURCE_TYPE_GRAPHQL) {
            $config->loadGraphQl($this->graphql);
        } else {
            throw new \RuntimeException("Unsupported source type: {$this->sourceType}");
        }*/

        switch ($this->sourceType) {
            case self::SOURCE_TYPE_GRAPHQL:
                $config->loadGraphQL($this->graphql ?? '');
                break;
            case self::SOURCE_TYPE_SDL:
                // Not implemented yet
                break;
            case self::SOURCE_TYPE_JSON:
                $config->loadJson($this->json ?? '');
                break;
            default:
                throw new RuntimeException("Unsupported source type: {$this->sourceType}");
        }

        // $config->entities = $this->schemaReader->load($this->sdl);

        $config->init();

        return $config;
    }

    /**
     * Set the SDL string to be parsed.
     */
    public function withGraphQl(string $graphql): self
    {
        $this->graphql = $graphql;
        $this->sourceType = self::SOURCE_TYPE_GRAPHQL;

        return $this;
    }
}
