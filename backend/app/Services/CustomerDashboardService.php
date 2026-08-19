<?php

namespace App\Services;

use App\Models\User;
use App\Repository\CustomerDashboardRepository;

class CustomerDashboardService
{
    public function __construct(private readonly CustomerDashboardRepository $dashboard)
    {
    }

    public function dashboard(User $customer): array
    {
        return [
            'summary' => $this->dashboard->summary($customer),
            'recent_tickets' => $this->dashboard->recentTickets($customer),
        ];
    }
}
