<?php

namespace Glugox\Magic\Support\Config\Entity;

class Settings
{
    /**
     * If the entity should have avatar to display right before/after the name.
     */
    public bool $hasAvatar = false;

    /**
     * Whether the entity should be searchable in the admin panel.
     */
    public bool $isSearchable = true;

    public function __construct(array $settings = [])
    {
        $this->hasAvatar = $settings['has_avatar'] ?? false;
        $this->isSearchable = $settings['is_searchable'] ?? true;
    }

    /**
     * Get a specific setting value by key.
     *
     * @param string $key The setting key to retrieve.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The value of the setting or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'has_avatar' => $this->hasAvatar,
            'is_searchable' => $this->isSearchable,
            default => $default,
        };
    }

}
