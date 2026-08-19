<?php

namespace App\Repository;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    public function summary(array $scope): array
    {
        $base = $this->base($scope);
        $responded = (clone $base)->whereNotNull('first_responded_at');
        $resolved = (clone $base)->whereNotNull('resolved_at');
        $firstTotal = (clone $responded)->count();
        $resolutionTotal = (clone $resolved)->count();

        $minutesExpression = Config::get('database.default') === 'sqlite'
            ? 'AVG((julianday(first_responded_at) - julianday(created_at)) * 24 * 60) as value'
            : 'AVG(TIMESTAMPDIFF(MINUTE, created_at, first_responded_at)) as value';
        $resolutionExpression = Config::get('database.default') === 'sqlite'
            ? 'AVG((julianday(resolved_at) - julianday(created_at)) * 24 * 60) as value'
            : 'AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as value';

        return [
            'created' => (clone $base)->count(),
            'resolved' => $resolutionTotal,
            'open' => (clone $this->scoped($scope))->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED])->count(),
            'overdue' => $this->overdueBase($this->scoped($scope))->count(),
            'average_first_response_minutes' => round((float) (clone $responded)->selectRaw($minutesExpression)->value('value'), 1),
            'average_resolution_minutes' => round((float) (clone $resolved)->selectRaw($resolutionExpression)->value('value'), 1),
            'first_response_sla_compliance' => $firstTotal === 0 ? 100 : round(((clone $responded)->whereColumn('first_responded_at', '<=', 'first_response_due_at')->count() / $firstTotal) * 100, 1),
            'resolution_sla_compliance' => $resolutionTotal === 0 ? 100 : round(((clone $resolved)->whereColumn('resolved_at', '<=', 'resolution_due_at')->count() / $resolutionTotal) * 100, 1),
            'reopened' => (clone $base)->whereHas('activities', fn (Builder $activity) => $activity->where('action', 'ticket_reopened')->orWhere('action', 'status_changed'))->where('status', Ticket::STATUS_OPEN)->count(),
        ];
    }

    public function volumeTrend(array $scope): array
    {
        return $this->base($scope)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as created, SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'created' => (int) $row->created, 'resolved' => (int) $row->resolved])
            ->all();
    }

    public function statusBreakdown(array $scope): array
    {
        return $this->groupCount($this->base($scope), 'status');
    }

    public function priorityBreakdown(array $scope): array
    {
        return $this->base($scope)->join('priorities', 'priorities.id', '=', 'tickets.priority_id')
            ->selectRaw('priorities.name as label, priorities.slug as slug, COUNT(*) as total')
            ->groupBy('priorities.id', 'priorities.name', 'priorities.slug')
            ->orderBy('priorities.sort_order')
            ->get()
            ->all();
    }

    public function departmentPerformance(array $scope): array
    {
        return $this->base($scope)->leftJoin('departments', 'departments.id', '=', 'tickets.department_id')
            ->selectRaw('departments.id, COALESCE(departments.name, "Unassigned") as name, COUNT(*) as created, SUM(CASE WHEN tickets.resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function categoryPerformance(array $scope): array
    {
        return $this->base($scope)->leftJoin('categories', 'categories.id', '=', 'tickets.category_id')
            ->leftJoin('departments', 'departments.id', '=', 'tickets.department_id')
            ->selectRaw('categories.id, COALESCE(categories.name, "Uncategorized") as name, departments.name as department, COUNT(*) as created, SUM(CASE WHEN tickets.resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved')
            ->groupBy('categories.id', 'categories.name', 'departments.name')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function agentPerformance(array $scope): array
    {
        return $this->scoped($scope)->leftJoin('users', 'users.id', '=', 'tickets.assigned_agent_id')
            ->selectRaw('users.id, COALESCE(users.name, "Unassigned") as name, COUNT(*) as assigned, SUM(CASE WHEN tickets.resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved, SUM(CASE WHEN tickets.status NOT IN ("resolved", "closed", "cancelled") THEN 1 ELSE 0 END) as open')
            ->groupBy('users.id', 'users.name')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function slaReport(array $scope): array
    {
        $current = $this->scoped($scope)->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED]);

        return [
            'due_soon' => (clone $current)->where(function (Builder $q): void {
                $q->whereBetween('first_response_due_at', [now(), now()->addHour()])
                    ->orWhereBetween('resolution_due_at', [now(), now()->addHour()]);
            })->count(),
            'breached' => $this->overdueBase(clone $current)->count(),
        ];
    }

    public function overdueTickets(array $scope): array
    {
        return $this->overdueBase($this->scoped($scope)->with(['customer:id,name,email', 'department:id,name', 'priority:id,name,slug', 'assignedAgent:id,name']))
            ->latest('updated_at')
            ->limit(25)
            ->get()
            ->all();
    }

    private function base(array $scope): Builder
    {
        return $this->scoped($scope)->whereBetween('tickets.created_at', [$scope['date_from'], $scope['date_to']]);
    }

    private function scoped(array $scope): Builder
    {
        $user = $scope['user'];
        $query = Ticket::query();
        $this->applyRoleScope($query, $user);
        $this->applyFilters($query, $scope['filters']);

        return $query;
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return;
        }

        if ($user->hasRole(Role::MANAGER)) {
            $departmentIds = $this->departmentIds($user);
            $query->where(fn (Builder $q) => $q->whereIn('tickets.department_id', $departmentIds)->orWhereNull('tickets.department_id'));
            return;
        }

        if ($user->hasRole(Role::AGENT)) {
            $query->where('tickets.assigned_agent_id', $user->id);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['department_id', 'category_id', 'priority_id', 'assigned_agent_id', 'status'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where("tickets.{$filter}", $filters[$filter]);
            }
        }
    }

    private function overdueBase(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where(fn (Builder $first) => $first->where('first_response_due_at', '<', now())->whereNull('first_responded_at'))
                ->orWhere('resolution_due_at', '<', now());
        })->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED]);
    }

    private function groupCount(Builder $query, string $column): array
    {
        return $query->selectRaw("{$column} as label, COUNT(*) as total")->groupBy($column)->orderBy($column)->get()->all();
    }

    private function departmentIds(User $user): array
    {
        $ids = $user->departments()->pluck('departments.id')->all();
        if ($user->primary_department_id !== null) {
            $ids[] = (int) $user->primary_department_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
