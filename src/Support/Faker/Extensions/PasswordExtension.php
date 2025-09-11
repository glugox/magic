<?php

namespace Glugox\Magic\Support\Faker\Extensions;

use Faker\Generator;
use Glugox\Magic\Support\Faker\FakerExtension;

class PasswordExtension extends FakerExtension
{
    /**
     * Handle the Faker extension logic.
     */
    public function handle(Generator $faker): string
    {
        // Return a hashed password for testing purposes
        return ! config('unsecure_mode', false)
            ? "bcrypt('password')" // Hash for 'password'
            : "'\$2y\$12\$00A.1FrCk3FctOEVIHlkLu5qYNfFdBGJUCyzdMaGcvC9CPTgPoIgK'"; // Hash for 'password'
    }
}
