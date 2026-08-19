<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => str($name)->headline()->toString(),
            'slug' => str($name)->slug()->toString(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
