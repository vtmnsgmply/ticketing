<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Repository\TicketActivityRepository;

class TicketActivityService
{
    public function __construct(
        private readonly TicketActivityRepository $activities,
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(Ticket $ticket, ?User $actor, string $action, mixed $oldValue = null, mixed $newValue = null, ?array $metadata = null): void
    {
        $this->activities->create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'old_value' => $oldValue === null ? null : (string) $oldValue,
            'new_value' => $newValue === null ? null : (string) $newValue,
            'metadata' => $metadata,
        ]);
    }
}
