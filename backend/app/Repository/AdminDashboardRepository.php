<?php

namespace App\Repository;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;

class AdminDashboardRepository
{
    public function summary(): array
    {
        $users = User::query()
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw(
                'COUNT(*) as total_users,
                SUM(CASE WHEN users.is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN users.is_active = 0 THEN 1 ELSE 0 END) as disabled_users,
                SUM(CASE WHEN roles.slug = ? THEN 1 ELSE 0 END) as total_customers,
                SUM(CASE WHEN roles.slug = ? THEN 1 ELSE 0 END) as total_agents,
                SUM(CASE WHEN roles.slug = ? THEN 1 ELSE 0 END) as total_managers',
                [Role::CUSTOMER, Role::AGENT, Role::MANAGER],
            )
            ->first();
        $tickets = Ticket::query()
            ->leftJoin('priorities', 'priorities.id', '=', 'tickets.priority_id')
            ->selectRaw(
                'SUM(CASE WHEN tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN tickets.resolution_due_at < ? AND tickets.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as overdue_tickets,
                SUM(CASE WHEN priorities.slug = ? THEN 1 ELSE 0 END) as critical_tickets',
                [
                    Ticket::STATUS_CLOSED,
                    Ticket::STATUS_CANCELLED,
                    Ticket::STATUS_RESOLVED,
                    now(),
                    Ticket::STATUS_CLOSED,
                    Ticket::STATUS_CANCELLED,
                    Ticket::STATUS_RESOLVED,
                    'critical',
                ],
            )
            ->first();

        return [
            'total_users' => (int) ($users->total_users ?? 0),
            'active_users' => (int) ($users->active_users ?? 0),
            'disabled_users' => (int) ($users->disabled_users ?? 0),
            'total_customers' => (int) ($users->total_customers ?? 0),
            'total_agents' => (int) ($users->total_agents ?? 0),
            'total_managers' => (int) ($users->total_managers ?? 0),
            'total_departments' => Department::query()->count(),
            'total_categories' => Category::query()->count(),
            'open_tickets' => (int) ($tickets->open_tickets ?? 0),
            'overdue_tickets' => (int) ($tickets->overdue_tickets ?? 0),
            'critical_tickets' => (int) ($tickets->critical_tickets ?? 0),
        ];
    }
}
