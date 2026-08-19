<?php

namespace App\Repository;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;

class TicketCatalogRepository
{
    public function activeDepartments()
    {
        return Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
    }

    public function activeCategories()
    {
        return Category::query()->with('department:id,name,slug')->where('is_active', true)->orderBy('name')->get(['id', 'department_id', 'name', 'slug']);
    }

    public function activePriorities()
    {
        return Priority::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'sort_order']);
    }
}
