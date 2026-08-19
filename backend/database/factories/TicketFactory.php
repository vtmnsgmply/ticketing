<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        $priority = Priority::factory()->create();
        $department = Department::factory()->create();
        $category = Category::factory()->create(['department_id' => $department->id]);

        return [
            'ticket_number' => 'TKT-'.fake()->unique()->numberBetween(10001, 99999),
            'customer_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'department_id' => $department->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status' => Ticket::STATUS_NEW,
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at' => now()->addDay(),
        ];
    }
}
