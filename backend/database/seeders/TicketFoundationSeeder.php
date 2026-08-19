<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\SlaRule;
use Illuminate\Database\Seeder;

class TicketFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'sort_order' => 10, 'first' => 480, 'resolution' => 2880],
            ['name' => 'Medium', 'slug' => 'medium', 'sort_order' => 20, 'first' => 240, 'resolution' => 1440],
            ['name' => 'High', 'slug' => 'high', 'sort_order' => 30, 'first' => 60, 'resolution' => 480],
            ['name' => 'Critical / Urgent', 'slug' => 'critical', 'sort_order' => 40, 'first' => 30, 'resolution' => 240],
        ];

        foreach ($priorities as $item) {
            $priority = Priority::query()->updateOrCreate(
                ['slug' => $item['slug']],
                ['name' => $item['name'], 'sort_order' => $item['sort_order'], 'is_active' => true],
            );

            SlaRule::query()->updateOrCreate(
                ['priority_id' => $priority->id],
                [
                    'first_response_minutes' => $item['first'],
                    'resolution_minutes' => $item['resolution'],
                    'pause_on_waiting_customer' => true,
                    'use_business_hours' => false,
                    'is_active' => true,
                ],
            );
        }

        $department = Department::query()->updateOrCreate(
            ['slug' => 'support'],
            ['name' => 'Support', 'description' => 'General support requests.', 'is_active' => true],
        );

        Category::query()->updateOrCreate(
            ['department_id' => $department->id, 'slug' => 'general'],
            ['name' => 'General', 'description' => 'General ticket category.', 'is_active' => true],
        );
    }
}
