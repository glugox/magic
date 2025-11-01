<?php

namespace Tests\Fixtures;

use Glugox\Orchestrator\Services\ModuleInstaller;
use RuntimeException;

class FakeModuleInstaller extends ModuleInstaller
{
    /**
     * @var array<int, array<int, string>>
     */
    public array $commands = [];

    /**
     * @var list<string|null>
     */
    public array $failureMessages = [];

    protected function runComposerCommand(array $args): void
    {
        $this->commands[] = $args;

        if ($this->failureMessages !== []) {
            $message = array_shift($this->failureMessages);

            if ($message !== null) {
                throw new RuntimeException($message);
            }
        }
    }
}
