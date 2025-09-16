<?php

namespace Glugox\Magic\Support\Config\Entity;

class Settings
{
    /**
     * If the entity should have avatar to display right before/after the name.
     */
    public bool $hasImages = false;

    /**
     * Whether the entity should be searchable in the admin panel.
     */
    public bool $isSearchable = true;

    /**
     * Constructor to initialize settings with default values or provided settings.
     *
     * @param  array{ has_images?: bool, is_searchable?: bool }  $settings
     */
    public function __construct(array $settings = [])
    {
        $this->hasImages = $settings['has_images'] ?? false;
        $this->isSearchable = $settings['is_searchable'] ?? true;
    }

    /**
     * Get a specific setting value by key.
     *
     * @param  string  $key  The setting key to retrieve.
     * @param  mixed  $default  The default value to return if the key does not exist.
     * @return mixed The value of the setting or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'has_images' => $this->hasImages,
            'is_searchable' => $this->isSearchable,
            default => $default,
        };
    }

    /**
     * Json representation of the settings.
     */
    public function toJson(): string
    {
        $json = json_encode([
            'has_images' => $this->hasImages,
            'is_searchable' => $this->isSearchable,
        ], JSON_PRETTY_PRINT);

        return $json === false ? '{}' : $json;
    }
}
