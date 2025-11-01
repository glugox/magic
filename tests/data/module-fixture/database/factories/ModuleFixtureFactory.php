<?php

namespace Glugox\Module\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFixtureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
