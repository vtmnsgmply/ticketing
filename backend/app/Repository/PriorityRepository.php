<?php

namespace App\Repository;

use App\Models\Priority;

class PriorityRepository
{
    public function findActiveById(int $priorityId): ?Priority
    {
        return Priority::query()->where('is_active', true)->find($priorityId);
    }

    public function defaultPriority(): ?Priority
    {
        return Priority::query()->where('is_active', true)->orderBy('sort_order')->first();
    }
}
