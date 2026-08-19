<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Role;
use App\Models\User;
use App\Repository\ReportRepository;
use Carbon\CarbonImmutable;

class ReportService
{
    public function __construct(private readonly ReportRepository $reports)
    {
    }

    public function overview(User $user, array $filters): array
    {
        $scope = $this->scope($user, $filters);

        return [
            'period' => ['from' => $scope['date_from']->toDateString(), 'to' => $scope['date_to']->toDateString()],
            'summary' => $this->reports->summary($scope),
            'volume' => $this->reports->volumeTrend($scope),
            'status' => $this->reports->statusBreakdown($scope),
            'priorities' => $this->reports->priorityBreakdown($scope),
            'departments' => $this->reports->departmentPerformance($scope),
            'categories' => $this->reports->categoryPerformance($scope),
            'agents' => $this->reports->agentPerformance($scope),
            'sla' => $this->reports->slaReport($scope),
            'overdue' => $this->reports->overdueTickets($scope),
        ];
    }

    private function scope(User $user, array $filters): array
    {
        $user->loadMissing('role');
        if ($user->hasRole(Role::CUSTOMER)) {
            throw new BusinessRuleException('You are not allowed to view reports.');
        }

        $from = ! empty($filters['date_from']) ? CarbonImmutable::parse($filters['date_from'])->startOfDay() : now()->subDays(29)->startOfDay()->toImmutable();
        $to = ! empty($filters['date_to']) ? CarbonImmutable::parse($filters['date_to'])->endOfDay() : now()->endOfDay()->toImmutable();
        if ($from->diffInDays($to) > 365) {
            throw new BusinessRuleException('Report date range cannot exceed 365 days.');
        }

        return [
            'user' => $user,
            'date_from' => $from,
            'date_to' => $to,
            'filters' => $filters,
        ];
    }
}
