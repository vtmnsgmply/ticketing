<?php

namespace App\Services;

use App\Models\User;
use App\Repository\TicketRepository;

class StaffDashboardService
{
    public function __construct(private readonly TicketRepository $tickets)
    {
    }

    public function dashboard(User $agent): array
    {
        return [
            'summary' => $this->tickets->staffSummary($agent),
            'assigned_to_me' => $this->tickets->queueForUser($agent, 'assigned_to_me'),
            'unassigned' => $this->tickets->queueForUser($agent, 'unassigned'),
            'overdue' => $this->tickets->queueForUser($agent, 'overdue'),
            'high_priority' => $this->tickets->queueForUser($agent, 'high_priority'),
            'recently_updated' => $this->tickets->queueForUser($agent, 'recently_updated'),
        ];
    }
}
