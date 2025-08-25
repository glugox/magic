<?php

namespace Glugox\Magic\Support\Faker;

use Faker\Generator;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Faker\Extensions\ImageExtension;
use Glugox\Magic\Support\Faker\Extensions\PasswordExtension;

abstract class FakerExtension
{
    /**
     * Registration of all Faker extensions.
     *
     * @returns array<string, class-string<FakerExtension>>
     */
    public static function getExtensions(): array
    {
        return [
            'image' => ImageExtension::class,
            'password' => PasswordExtension::class,
            // Add other extensions here as needed
        ];
    }

    /**
     * Return extension by field.
     */
    public static function getExtensionByField(Field $field): ?FakerExtension
    {
        $fieldString = $field->name;
        $extensions = self::getExtensions();
        if (isset($extensions[$fieldString])) {
            return new $extensions[$fieldString];
        }

        return null;
    }

    /**
     * Handle the Faker extension logic.
     */
    public function handle(Generator $faker): string
    {
        // This method can be overridden by subclasses to implement specific logic
        // for the Faker extension. It is intentionally left empty here.
        return '';
    }
}
