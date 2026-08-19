<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly User $actor,
        private readonly string $action,
        private readonly ?int $messageId = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tickets.'.$this->ticket->id);
    }

    public function broadcastAs(): string
    {
        return 'ticket.conversation.updated';
    }

    public function broadcastWith(): array
    {
        $message = $this->messageId ? $this->serializeMessage($this->messageId) : null;
        $ticket = $this->ticket->loadMissing('customer', 'department', 'category', 'priority', 'assignedAgent');

        return [
            'ticket_id' => $this->ticket->id,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message_id' => $this->messageId,
            'message' => $message,
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'customer' => $this->serializeUser($ticket->customer),
                'department' => $ticket->department?->only(['id', 'name', 'slug']),
                'category' => $ticket->category?->only(['id', 'name', 'slug']),
                'priority' => $ticket->priority?->only(['id', 'name', 'slug']),
                'assigned_agent' => $this->serializeUser($ticket->assignedAgent),
            ],
            'requires_refetch' => $this->action === 'message.read' || ($this->messageId !== null && $message === null),
            'updated_at' => now()->toISOString(),
        ];
    }

    private function serializeMessage(int $messageId): ?array
    {
        $message = TicketMessage::query()
            ->with('user.role', 'attachments', 'reactions.user.role', 'reads.user.role', 'deletedBy.role', 'replyTo.user.role')
            ->where('ticket_id', $this->ticket->id)
            ->find($messageId);

        if ($message === null || $message->message_type === TicketMessage::TYPE_INTERNAL_NOTE) {
            return null;
        }

        return [
            'id' => $message->id,
            'message' => $message->deleted_at ? null : $message->message,
            'message_type' => $message->message_type,
            'reply_to' => $this->serializeReplyPreview($message->replyTo),
            'user' => $this->serializeUser($message->user),
            'deleted_at' => $message->deleted_at?->toISOString(),
            'deleted_by' => $this->serializeUser($message->deletedBy),
            'is_deleted' => $message->deleted_at !== null,
            'attachments' => $message->deleted_at ? [] : $message->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'ticket_id' => $attachment->ticket_id,
                'ticket_message_id' => $attachment->ticket_message_id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'file_size' => $attachment->file_size,
                'download_url' => "/tickets/{$attachment->ticket_id}/attachments/{$attachment->id}",
                'is_image' => str_starts_with((string) $attachment->mime_type, 'image/'),
                'is_video' => str_starts_with((string) $attachment->mime_type, 'video/'),
                'thumbnail_url' => str_starts_with((string) $attachment->mime_type, 'image/')
                    ? "/tickets/{$attachment->ticket_id}/attachments/{$attachment->id}/thumbnail"
                    : null,
                'created_at' => $attachment->created_at?->toISOString(),
            ])->values(),
            'reactions' => $message->reactions
                ->groupBy('reaction')
                ->map(fn ($items, string $reaction) => [
                    'reaction' => $reaction,
                    'count' => $items->count(),
                    'users' => $items->map(fn ($item) => $this->serializeUser($item->user))->values(),
                    'user_ids' => $items->pluck('user_id')->map(fn ($id) => (int) $id)->values(),
                ])
                ->values(),
            'read_by' => $message->reads
                ->sortBy('read_at')
                ->map(fn ($read) => [
                    'user' => $this->serializeUser($read->user),
                    'read_at' => $read->read_at?->toISOString(),
                ])
                ->values(),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    private function serializeReplyPreview(?TicketMessage $message): ?array
    {
        if ($message === null || $message->message_type === TicketMessage::TYPE_INTERNAL_NOTE) {
            return null;
        }

        return [
            'id' => $message->id,
            'message' => $message->deleted_at ? null : str($message->message)->limit(160)->toString(),
            'message_type' => $message->message_type,
            'user' => $this->serializeUser($message->user),
            'is_deleted' => $message->deleted_at !== null,
        ];
    }

    private function serializeUser($user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'customer_label' => $user->customer_label,
            'role' => $user->role?->only(['id', 'name', 'slug']),
        ];
    }
}
