<?php

namespace App\Services;

use App\Models\User;
use App\Repository\NotificationRepository;

class CustomerNotificationService
{
    public function __construct(private readonly NotificationRepository $notifications)
    {
    }

    public function paginate(User $user, array $filters)
    {
        return $this->notifications->paginateForUser($user, $filters);
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCount($user);
    }

    public function markRead(User $user, int $notificationId)
    {
        $notification = $this->notifications->findForUser($user, $notificationId);

        if ($notification === null) {
            return null;
        }

        return $this->notifications->markRead($notification);
    }

    public function markAllRead(User $user): void
    {
        $this->notifications->markAllRead($user);
    }
}
