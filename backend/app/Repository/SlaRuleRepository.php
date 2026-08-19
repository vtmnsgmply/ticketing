<?php

namespace App\Repository;

use App\Models\SlaRule;

class SlaRuleRepository
{
    public function findActiveByPriorityId(int $priorityId): ?SlaRule
    {
        return SlaRule::query()
            ->where('priority_id', $priorityId)
            ->where('is_active', true)
            ->first();
    }
}
