<?php

namespace App\Repository;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository
{
    public function create(array $data): Notification
    {
        return Notification::query()->create($data);
    }

    public function paginateForUser(User $user, array $filters): LengthAwarePaginator
    {
        return Notification::query()
            ->with('ticket:id,ticket_number,subject,customer_id')
            ->where('user_id', $user->id)
            ->where('channel', $filters['channel'] ?? 'web')
            ->when(array_key_exists('is_read', $filters) && $filters['is_read'] !== null && $filters['is_read'] !== '', fn ($q) => $q->where('is_read', (bool) $filters['is_read']))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()->where('user_id', $user->id)->where('channel', 'web')->where('is_read', false)->count();
    }

    public function findForUser(User $user, int $notificationId): ?Notification
    {
        return Notification::query()->where('user_id', $user->id)->find($notificationId);
    }

    public function markRead(Notification $notification): Notification
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return $notification->fresh('ticket');
    }

    public function markAllRead(User $user): void
    {
        Notification::query()->where('user_id', $user->id)->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function findDuplicate(User $user, int $ticketId, string $type, string $channel): ?Notification
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('ticket_id', $ticketId)
            ->where('type', $type)
            ->where('channel', $channel)
            ->first();
    }

    public function markSent(Notification $notification): Notification
    {
        $notification->update(['sent_at' => now(), 'failed_at' => null, 'failure_reason' => null]);

        return $notification->fresh();
    }

    public function markFailed(Notification $notification, string $reason): Notification
    {
        $notification->update([
            'failed_at' => now(),
            'failure_reason' => mb_substr($reason, 0, 500),
        ]);

        return $notification->fresh();
    }
}
