<?php

namespace App\Services;

use App\Models\User;
use App\Repository\TicketRepository;

class ManagerDashboardService
{
    public function __construct(private readonly TicketRepository $tickets)
    {
    }

    public function dashboard(User $manager): array
    {
        return [
            'summary' => $this->tickets->managerSummary($manager),
            'sla' => $this->tickets->managerSla($manager),
            'team_workload' => $this->tickets->teamWorkload($manager),
            'unassigned' => $this->tickets->queueForUser($manager, 'unassigned'),
            'overdue' => $this->tickets->queueForUser($manager, 'overdue'),
            'high_priority' => $this->tickets->queueForUser($manager, 'high_priority'),
            'recent_tickets' => $this->tickets->queueForUser($manager, 'recently_updated'),
        ];
    }

    public function team(User $manager)
    {
        return $this->tickets->teamWorkload($manager);
    }
}
