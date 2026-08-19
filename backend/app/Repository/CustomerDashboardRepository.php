<?php

namespace App\Repository;

use App\Models\Ticket;
use App\Models\User;

class CustomerDashboardRepository
{
    public function summary(User $customer): array
    {
        $row = Ticket::query()
            ->where('customer_id', $customer->id)
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as waiting_for_customer,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved',
                [
                    Ticket::STATUS_NEW,
                    Ticket::STATUS_OPEN,
                    Ticket::STATUS_ASSIGNED,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_FOR_CUSTOMER,
                    Ticket::STATUS_RESOLVED,
                ],
            )
            ->first();

        return [
            'open' => (int) ($row->open ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'waiting_for_customer' => (int) ($row->waiting_for_customer ?? 0),
            'resolved' => (int) ($row->resolved ?? 0),
        ];
    }

    public function recentTickets(User $customer, int $limit = 8)
    {
        return Ticket::query()
            ->with(['department', 'category', 'priority'])
            ->where('customer_id', $customer->id)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }
}
