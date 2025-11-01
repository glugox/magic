<?php

namespace Glugox\Actions\Contracts;

use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Support\Progress;

interface Action
{
    public function name(): string;

    /**
     * Return array payload. Should throw exceptions on failure.
     */
    public function handle(ActionContext $ctx, Progress $progress): array;
}
