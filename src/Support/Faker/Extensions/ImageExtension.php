<?php

namespace Glugox\Magic\Support\Faker\Extensions;

use Glugox\Magic\Support\Faker\FakerExtension;
use Faker\Generator;

class ImageExtension extends FakerExtension
{
    /**
     * Handle the Faker extension logic.
     */
    public function handle(Generator $faker): string
    {
        // Generate a random image URL using Faker
        //return "'https://picsum.photos/200/200?random=' . \$this->faker->unique()->numberBetween(1, 1000)";

        // 'image' => 'https://picsum.photos/id/' . $this->faker->numberBetween(1, 1000) . '/200/200.jpg',

        return "'https://picsum.photos/id/' . \$this->faker->numberBetween(1, 1000) . '/200/200.jpg'";
    }

}
