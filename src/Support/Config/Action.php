<?php

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Action
{
    public function __construct(
        // Short name of the action, e.g. "create"
        public string $shortName,
        // Full name including entity name prefix, e.g. "user.create"
        public string $name = '',
        public string $type = 'command',
        public ?string $command = null,
        public ?string $label = null,
        public ?string $field = null,
        public ?string $icon = null,
        public ?string $description = null,
        public array $extras = [],
    ) {
        $this->type = $type !== '' ? $type : 'command';
    }

    /**
     * @param  array{name?: string, type?: string, command?: string, label?: string, field?: string, icon?: string, description?: string}  $data
     */
    public static function fromConfig(array $data, Entity $entity): self
    {
        if (! isset($data['name']) || $data['name'] === '') {
            throw new InvalidArgumentException('Action name is required');
        }

        $extras = $data;
        unset($extras['name'], $extras['type'], $extras['command'], $extras['label'], $extras['field'], $extras['icon'], $extras['description']);

        return new self(
            shortName: $data['name'],
            name: $entity->getName().'.'.$data['name'],
            type: $data['type'] ?? 'command',
            command: $data['command'] ?? null,
            label: $data['label'] ?? Str::title(str_replace('_', ' ', $data['name'])),
            field: $data['field'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
            extras: $extras,
        );
    }

    public function toArray(): array
    {
        $data = array_merge($this->extras, [
            'name' => $this->name,
            'type' => $this->type,
            'command' => $this->command,
            'label' => $this->label,
            'field' => $this->field,
            'icon' => $this->icon,
            'description' => $this->description,
        ]);

        return array_filter($data, static fn ($value) => $value !== null);
    }

    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT);

        return $json === false ? '{}' : $json;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->extras[$key] ?? $default;
    }
}
