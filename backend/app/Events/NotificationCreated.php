<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly Notification $notification)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('notifications.'.$this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        $notification = $this->notification->loadMissing('ticket:id,ticket_number,subject,customer_id');

        return [
            'notification' => [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'ticket_id' => $notification->ticket_id,
                'type' => $notification->type,
                'channel' => $notification->channel,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'is_read' => (bool) $notification->is_read,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at?->toISOString(),
                'ticket' => $notification->ticket ? [
                    'id' => $notification->ticket->id,
                    'ticket_number' => $notification->ticket->ticket_number,
                    'subject' => $notification->ticket->subject,
                    'customer_id' => $notification->ticket->customer_id,
                ] : null,
            ],
        ];
    }
}
