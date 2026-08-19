<?php

namespace App\Services;

use App\Repository\AdminDashboardRepository;

class AdminDashboardService
{
    public function __construct(private readonly AdminDashboardRepository $dashboard)
    {
    }

    public function summary(): array
    {
        return $this->dashboard->summary();
    }
}
