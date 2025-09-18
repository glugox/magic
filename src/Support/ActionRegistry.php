<?php

namespace Glugox\Magic\Support;

class ActionRegistry
{
    /**
     * @var array <string, string> Mapping of action aliases to their corresponding class names.
     */
    protected array $actions = [];

    /**
     * Register a new action class with an alias.
     */
    public function register(string $alias, string $class): void
    {
        $this->actions[$alias] = $class;
    }

    public function get(string $alias): ?string
    {
        return $this->actions[$alias] ?? null;
    }

    /**
     * Get all registered actions.
     *
     * @return array <string, string>
     */
    public function all(): array
    {
        return $this->actions;
    }
}
