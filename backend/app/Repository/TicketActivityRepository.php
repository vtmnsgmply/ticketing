<?php

namespace App\Repository;

use App\Models\TicketActivity;

class TicketActivityRepository
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): TicketActivity
    {
        return TicketActivity::query()->create($data + ['created_at' => now()]);
    }
}
