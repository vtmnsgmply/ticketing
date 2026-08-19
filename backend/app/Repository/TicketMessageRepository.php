<?php

namespace App\Repository;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;

class TicketMessageRepository
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): TicketMessage
    {
        return TicketMessage::query()->create($data)->load('user.role', 'attachments', 'reactions.user.role', 'reads.user.role', 'deletedBy.role', 'replyTo.user.role');
    }

    public function visibleForCustomer(Ticket $ticket)
    {
        return $ticket->messages()
            ->with('user.role', 'attachments', 'reactions.user.role', 'reads.user.role', 'deletedBy.role', 'replyTo.user.role')
            ->whereIn('message_type', [TicketMessage::TYPE_CUSTOMER_REPLY, TicketMessage::TYPE_AGENT_REPLY])
            ->oldest()
            ->get();
    }

    public function findForTicket(int $ticketId, int $messageId): ?TicketMessage
    {
        return TicketMessage::query()
            ->with('user.role', 'attachments', 'reactions.user.role', 'reads.user.role', 'deletedBy.role', 'replyTo.user.role')
            ->where('ticket_id', $ticketId)
            ->find($messageId);
    }

    public function markDeleted(TicketMessage $message, User $user): TicketMessage
    {
        $message->forceFill([
            'deleted_by' => $user->id,
            'deleted_at' => now(),
        ])->save();

        return $message->fresh(['user.role', 'attachments', 'reactions.user.role', 'reads.user.role', 'deletedBy.role', 'replyTo.user.role']);
    }
}
