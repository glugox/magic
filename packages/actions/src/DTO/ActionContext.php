<?php

namespace Glugox\Actions\DTO;

class ActionContext
{
    public function __construct(
        public array $params = [],
        public ?int $userId = null,
        public array $targets = [],
        public ?int $runId = null,
    ) {}

    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
}
