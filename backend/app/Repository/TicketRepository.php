<?php

namespace App\Repository;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TicketRepository
{
    /**
     * @var array<int, array<int>>
     */
    private array $permittedDepartmentIdsByUser = [];

    public function nextTicketNumber(): string
    {
        $value = DB::table('ticket_number_sequences')->where('id', 1)->lockForUpdate()->value('current_value');
        $prefix = DB::table('system_settings')->where('key', 'ticket_prefix')->value('value') ?: 'TKT';
        $startNumber = (int) (DB::table('system_settings')->where('key', 'ticket_start_number')->value('value') ?: 10000);

        if ($value === null) {
            DB::table('ticket_number_sequences')->insert(['id' => 1, 'current_value' => $startNumber]);
            $value = $startNumber;
        }

        $next = ((int) $value) + 1;
        DB::table('ticket_number_sequences')->where('id', 1)->update(['current_value' => $next]);

        return $prefix.'-'.$next;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Ticket
    {
        return Ticket::query()->create($data)->load($this->detailRelations());
    }

    public function findForUser(int $ticketId, User $user): ?Ticket
    {
        return $this->scopeForUser(Ticket::query()->with($this->detailRelations()), $user)->find($ticketId);
    }

    public function findById(int $ticketId): ?Ticket
    {
        return Ticket::query()->with($this->detailRelations())->find($ticketId);
    }

    public function findActiveByNumberForCustomer(string $ticketNumber, User $customer): ?Ticket
    {
        return Ticket::query()
            ->with($this->detailRelations())
            ->where('customer_id', $customer->id)
            ->where('ticket_number', strtoupper(trim($ticketNumber)))
            ->whereNotIn('status', $this->closedStatuses())
            ->first();
    }

    public function activeForCustomer(User $customer, int $limit = 6)
    {
        return Ticket::query()
            ->with(['customer.role', 'department', 'category', 'priority', 'assignedAgent.role'])
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', $this->closedStatuses())
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForUser(User $user, array $filters): LengthAwarePaginator
    {
        $query = $this->scopeForUser(
            Ticket::query()
                ->leftJoin('priorities', 'priorities.id', '=', 'tickets.priority_id')
                ->select([
                    'tickets.id',
                    'tickets.ticket_number',
                    'tickets.subject',
                    'tickets.status',
                    'tickets.priority_id',
                    'tickets.created_at',
                    'tickets.updated_at',
                    'tickets.first_response_due_at',
                    'tickets.resolution_due_at',
                    'tickets.first_responded_at',
                    'tickets.resolved_at',
                    'tickets.closed_at',
                    'tickets.cancelled_at',
                    'priorities.id as priority_lookup_id',
                    'priorities.name as priority_lookup_name',
                    'priorities.slug as priority_lookup_slug',
                ]),
            $user,
        );

        $this->applyQueue($query, $filters, $user);
        $this->applyFilters($query, $filters);

        $sortColumns = [
            'created_at' => 'tickets.created_at',
            'updated_at' => 'tickets.updated_at',
            'ticket_number' => 'tickets.ticket_number',
            'status' => 'tickets.status',
        ];
        $sort = $sortColumns[$filters['sort'] ?? ''] ?? 'tickets.updated_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->fresh($this->detailRelations());
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        $user->loadMissing('role');

        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return $query;
        }

        if ($user->hasRole(Role::CUSTOMER)) {
            return $query->where('tickets.customer_id', $user->id);
        }

        if ($user->hasRole(Role::AGENT)) {
            $departmentIds = $this->permittedDepartmentIds($user);

            return $query->where(function (Builder $builder) use ($user, $departmentIds): void {
                $builder->where('tickets.assigned_agent_id', $user->id)
                    ->orWhere(function (Builder $departmentScope) use ($departmentIds): void {
                        $departmentScope
                            ->whereIn('tickets.department_id', $departmentIds)
                            ->orWhere(function (Builder $unassignedDefault): void {
                                $unassignedDefault->whereNull('tickets.assigned_agent_id')->whereNull('tickets.department_id');
                            });
                    });
            });
        }

        if ($user->hasRole(Role::MANAGER)) {
            $departmentIds = $this->permittedDepartmentIds($user);

            return $query->where(function (Builder $builder) use ($departmentIds): void {
                $builder->whereIn('tickets.department_id', $departmentIds)
                    ->orWhereNull('tickets.department_id');
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('tickets.ticket_number', 'like', $search)
                    ->orWhere('tickets.subject', 'like', $search)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        foreach ([
            'ticket_number' => 'tickets.ticket_number',
            'status' => 'tickets.status',
            'priority' => 'tickets.priority_id',
            'category' => 'tickets.category_id',
            'department' => 'tickets.department_id',
            'assigned_agent' => 'tickets.assigned_agent_id',
        ] as $input => $column) {
            if (($filters[$input] ?? null) !== null && $filters[$input] !== '') {
                $query->where($column, $filters[$input]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('tickets.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('tickets.created_at', '<=', $filters['date_to']);
        }
    }

    public function queueForUser(User $user, string $queue, int $limit = 8)
    {
        $query = $this->scopeForUser(Ticket::query()->with(['customer.role', 'department', 'category', 'priority', 'assignedAgent.role']), $user);
        $this->applyQueue($query, ['queue' => $queue], $user);

        return $query->latest('updated_at')->limit($limit)->get();
    }

    public function staffSummary(User $user): array
    {
        $closedStatuses = [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED];

        $row = $this->scopeForUser(Ticket::query(), $user)
            ->leftJoin('priorities', 'priorities.id', '=', 'tickets.priority_id')
            ->selectRaw(
                'SUM(CASE WHEN tickets.status IN (?, ?) THEN 1 ELSE 0 END) as new,
                SUM(CASE WHEN tickets.assigned_agent_id = ? THEN 1 ELSE 0 END) as assigned_to_me,
                SUM(CASE WHEN tickets.assigned_agent_id IS NULL THEN 1 ELSE 0 END) as unassigned,
                SUM(CASE WHEN ((tickets.first_response_due_at < ? AND tickets.first_responded_at IS NULL) OR tickets.resolution_due_at < ?) AND tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN priorities.slug IN (?, ?) THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN tickets.status = ? THEN 1 ELSE 0 END) as waiting_for_customer,
                SUM(CASE WHEN DATE(tickets.resolved_at) = ? THEN 1 ELSE 0 END) as resolved_today',
                [
                    Ticket::STATUS_NEW, Ticket::STATUS_OPEN,
                    $user->id,
                    now(), now(),
                    ...$closedStatuses,
                    'high', 'critical',
                    Ticket::STATUS_WAITING_FOR_CUSTOMER,
                    today()->toDateString(),
                ],
            )
            ->first();

        return [
            'new' => (int) ($row->new ?? 0),
            'assigned_to_me' => (int) ($row->assigned_to_me ?? 0),
            'unassigned' => (int) ($row->unassigned ?? 0),
            'overdue' => (int) ($row->overdue ?? 0),
            'high_priority' => (int) ($row->high_priority ?? 0),
            'waiting_for_customer' => (int) ($row->waiting_for_customer ?? 0),
            'resolved_today' => (int) ($row->resolved_today ?? 0),
        ];
    }

    public function managerSummary(User $manager): array
    {
        $closedStatuses = [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED];

        $row = $this->scopeForUser(Ticket::query(), $manager)
            ->leftJoin('priorities', 'priorities.id', '=', 'tickets.priority_id')
            ->selectRaw(
                'SUM(CASE WHEN tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN tickets.assigned_agent_id IS NOT NULL THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN tickets.assigned_agent_id IS NULL THEN 1 ELSE 0 END) as unassigned,
                SUM(CASE WHEN ((tickets.first_response_due_at < ? AND tickets.first_responded_at IS NULL) OR tickets.resolution_due_at < ?) AND tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN priorities.slug IN (?, ?) THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN tickets.status = ? THEN 1 ELSE 0 END) as waiting_for_customer,
                SUM(CASE WHEN DATE(tickets.resolved_at) = ? THEN 1 ELSE 0 END) as resolved_today',
                [
                    ...$closedStatuses,
                    now(), now(),
                    ...$closedStatuses,
                    'high', 'critical',
                    Ticket::STATUS_WAITING_FOR_CUSTOMER,
                    today()->toDateString(),
                ],
            )
            ->first();

        return [
            'open' => (int) ($row->open ?? 0),
            'assigned' => (int) ($row->assigned ?? 0),
            'unassigned' => (int) ($row->unassigned ?? 0),
            'overdue' => (int) ($row->overdue ?? 0),
            'high_priority' => (int) ($row->high_priority ?? 0),
            'waiting_for_customer' => (int) ($row->waiting_for_customer ?? 0),
            'resolved_today' => (int) ($row->resolved_today ?? 0),
        ];
    }

    public function managerSla(User $manager): array
    {
        $closedStatuses = [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED];

        $row = $this->scopeForUser(Ticket::query(), $manager)
            ->whereNotIn('status', $closedStatuses)
            ->selectRaw(
                'COUNT(*) as total,
                SUM(CASE WHEN (first_response_due_at < ? AND first_responded_at IS NULL) OR resolution_due_at < ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN (first_response_due_at BETWEEN ? AND ?) OR (resolution_due_at BETWEEN ? AND ?) THEN 1 ELSE 0 END) as due_soon',
                [now(), now(), now(), now()->addHour(), now(), now()->addHour()],
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $overdue = (int) ($row->overdue ?? 0);
        $dueSoon = (int) ($row->due_soon ?? 0);

        return [
            'on_track' => max($total - $overdue - $dueSoon, 0),
            'due_soon' => $dueSoon,
            'overdue' => $overdue,
            'compliance_percentage' => $total === 0 ? 100 : round((($total - $overdue) / $total) * 100, 1),
        ];
    }

    public function teamWorkload(User $manager)
    {
        $departmentIds = $this->permittedDepartmentIds($manager);

        $agents = User::query()
            ->with(['role', 'primaryDepartment'])
            ->whereHas('role', fn (Builder $role) => $role->where('slug', Role::AGENT))
            ->where(function (Builder $query) use ($departmentIds): void {
                $query->whereIn('primary_department_id', $departmentIds)
                    ->orWhereHas('departments', fn (Builder $department) => $department->whereIn('departments.id', $departmentIds));
            })
            ->get();

        if ($agents->isEmpty()) {
            return $agents;
        }

        $agentIds = $agents->pluck('id')->all();
        $closedStatuses = [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED];
        $workload = Ticket::query()
            ->leftJoin('priorities', 'priorities.id', '=', 'tickets.priority_id')
            ->whereIn('tickets.assigned_agent_id', $agentIds)
            ->selectRaw(
                'tickets.assigned_agent_id,
                SUM(CASE WHEN tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as assigned_tickets,
                SUM(CASE WHEN tickets.status = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN tickets.status = ? THEN 1 ELSE 0 END) as waiting_for_customer,
                SUM(CASE WHEN tickets.resolution_due_at < ? AND tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN priorities.slug IN (?, ?) THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN DATE(tickets.resolved_at) = ? THEN 1 ELSE 0 END) as resolved_today',
                [
                    ...$closedStatuses,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_FOR_CUSTOMER,
                    now(),
                    ...$closedStatuses,
                    'high',
                    'critical',
                    today()->toDateString(),
                ],
            )
            ->groupBy('tickets.assigned_agent_id')
            ->get()
            ->keyBy('assigned_agent_id');

        return $agents
            ->map(fn (User $agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'department' => $agent->primaryDepartment?->only(['id', 'name', 'slug']),
                'is_active' => $agent->is_active,
                'assigned_tickets' => (int) ($workload[$agent->id]->assigned_tickets ?? 0),
                'in_progress' => (int) ($workload[$agent->id]->in_progress ?? 0),
                'waiting_for_customer' => (int) ($workload[$agent->id]->waiting_for_customer ?? 0),
                'overdue' => (int) ($workload[$agent->id]->overdue ?? 0),
                'high_priority' => (int) ($workload[$agent->id]->high_priority ?? 0),
                'resolved_today' => (int) ($workload[$agent->id]->resolved_today ?? 0),
            ])
            ->values();
    }

    public function departmentManagers(Ticket $ticket): array
    {
        if ($ticket->department_id === null) {
            return [];
        }

        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $role) => $role->where('slug', Role::MANAGER))
            ->where(function (Builder $query) use ($ticket): void {
                $query->where('primary_department_id', $ticket->department_id)
                    ->orWhereHas('departments', fn (Builder $department) => $department->where('departments.id', $ticket->department_id));
            })
            ->get()
            ->all();
    }

    public function slaDueSoonTickets()
    {
        return Ticket::query()
            ->with(['assignedAgent.role', 'department', 'priority'])
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED])
            ->where(function (Builder $query): void {
                $query->whereBetween('first_response_due_at', [now(), now()->addHour()])
                    ->orWhereBetween('resolution_due_at', [now(), now()->addHour()]);
            })
            ->limit(250)
            ->get();
    }

    public function slaBreachedTickets()
    {
        return Ticket::query()
            ->with(['assignedAgent.role', 'department', 'priority'])
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED])
            ->where(function (Builder $query): void {
                $query->where(fn (Builder $first) => $first->where('first_response_due_at', '<', now())->whereNull('first_responded_at'))
                    ->orWhere('resolution_due_at', '<', now());
            })
            ->limit(250)
            ->get();
    }

    private function applyQueue(Builder $query, array $filters, User $user): void
    {
        match ($filters['queue'] ?? null) {
            'assigned_to_me' => $query->where('tickets.assigned_agent_id', $user->id),
            'unassigned' => $query->whereNull('tickets.assigned_agent_id'),
            'new' => $query->whereIn('tickets.status', [Ticket::STATUS_NEW, Ticket::STATUS_OPEN]),
            'overdue' => $query->where(function (Builder $builder): void {
                $builder->where('tickets.first_response_due_at', '<', now())->whereNull('tickets.first_responded_at')
                    ->orWhere('tickets.resolution_due_at', '<', now());
            })->whereNotIn('tickets.status', [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED]),
            'high_priority' => $query->whereHas('priority', fn (Builder $priority) => $priority->whereIn('slug', ['high', 'critical'])),
            'waiting_for_customer' => $query->where('tickets.status', Ticket::STATUS_WAITING_FOR_CUSTOMER),
            'resolved' => $query->where('tickets.status', Ticket::STATUS_RESOLVED),
            default => null,
        };

        match ($filters['sla_state'] ?? null) {
            'overdue' => $query->where(function (Builder $builder): void {
                $builder->where('tickets.first_response_due_at', '<', now())->whereNull('tickets.first_responded_at')
                    ->orWhere('tickets.resolution_due_at', '<', now());
            }),
            'responded' => $query->whereNotNull('tickets.first_responded_at'),
            'resolved' => $query->whereIn('tickets.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]),
            default => null,
        };
    }

    private function permittedDepartmentIds(User $user): array
    {
        if (array_key_exists((int) $user->id, $this->permittedDepartmentIdsByUser)) {
            return $this->permittedDepartmentIdsByUser[(int) $user->id];
        }

        $ids = $user->departments()->pluck('departments.id')->all();
        if ($user->primary_department_id !== null) {
            $ids[] = (int) $user->primary_department_id;
        }

        return $this->permittedDepartmentIdsByUser[(int) $user->id] = array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return list<string>
     */
    private function closedStatuses(): array
    {
        return [
            Ticket::STATUS_RESOLVED,
            Ticket::STATUS_CLOSED,
            Ticket::STATUS_CANCELLED,
        ];
    }

    /**
     * @return list<string>
     */
    private function detailRelations(): array
    {
        return [
            'customer.role',
            'department',
            'category',
            'priority',
            'assignedAgent.role',
            'messages.user.role',
            'messages.attachments',
            'messages.reactions.user.role',
            'messages.reads.user.role',
            'messages.deletedBy.role',
            'messages.replyTo.user.role',
            'attachments.uploader.role',
            'activities.user.role',
        ];
    }
}
