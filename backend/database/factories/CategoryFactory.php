<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'department_id' => Department::factory(),
            'name' => str($name)->headline()->toString(),
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
