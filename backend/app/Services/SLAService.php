<?php

namespace App\Services;

use App\Repository\SlaRuleRepository;
use Carbon\CarbonImmutable;

class SLAService
{
    public function __construct(
        private readonly SlaRuleRepository $slaRules,
    ) {
    }

    /**
     * @return array{first_response_due_at: CarbonImmutable|null, resolution_due_at: CarbonImmutable|null}
     */
    public function deadlinesForPriority(int $priorityId): array
    {
        $rule = $this->slaRules->findActiveByPriorityId($priorityId);

        if ($rule === null) {
            return ['first_response_due_at' => null, 'resolution_due_at' => null];
        }

        $now = CarbonImmutable::now();

        return [
            'first_response_due_at' => $now->addMinutes($rule->first_response_minutes),
            'resolution_due_at' => $now->addMinutes($rule->resolution_minutes),
        ];
    }
}
