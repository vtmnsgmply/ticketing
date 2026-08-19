<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\ConversationModerationEvent;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Repository\AdminConfigRepository;
use App\Repository\NotificationRepository;
use App\Repository\TicketRepository;
use App\Support\NotificationType;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly AdminConfigRepository $config,
        private readonly TicketRepository $tickets,
        private readonly EmailService $email,
    ) {
    }

    public function ticketEvent(string $event, Ticket $ticket, ?User $actor = null): void
    {
        $ticket->loadMissing('customer', 'assignedAgent', 'department', 'priority');
        $recipients = $this->recipientsForTicketEvent($event, $ticket, $actor);
        if ($recipients === []) {
            return;
        }

        $title = NotificationType::labels()[$event] ?? 'Ticket update';
        $message = "{$ticket->ticket_number}: {$ticket->subject}";

        foreach ($recipients as $recipient) {
            $this->notify($recipient, $event, $title, $message, $ticket, ['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number]);
        }
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

    public function preferences(User $user): array
    {
        return [
            'web_notifications_enabled' => (bool) $user->web_notifications_enabled,
            'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            'system_web_enabled' => $this->webEnabled(),
            'system_email_enabled' => $this->emailEnabled(),
        ];
    }

    public function updatePreferences(User $user, array $data): array
    {
        $user->update([
            'web_notifications_enabled' => (bool) $data['web_notifications_enabled'],
            'email_notifications_enabled' => (bool) $data['email_notifications_enabled'],
        ]);

        return $this->preferences($user->fresh());
    }

    public function monitorSla(): array
    {
        $dueSoon = 0;
        $breached = 0;

        foreach ($this->tickets->slaDueSoonTickets() as $ticket) {
            foreach ($this->operationalRecipients($ticket) as $recipient) {
                $this->notify($recipient, NotificationType::SLA_DUE_SOON, 'SLA due soon', "{$ticket->ticket_number} is approaching an SLA deadline.", $ticket, ['sla' => 'due_soon'], true);
                $dueSoon++;
            }
        }

        foreach ($this->tickets->slaBreachedTickets() as $ticket) {
            foreach ($this->operationalRecipients($ticket) as $recipient) {
                $this->notify($recipient, NotificationType::SLA_BREACHED, 'SLA breached', "{$ticket->ticket_number} has exceeded an SLA deadline.", $ticket, ['sla' => 'breached'], true);
                $breached++;
            }
        }

        return ['due_soon' => $dueSoon, 'breached' => $breached];
    }

    public function moderationAlert(ConversationModerationEvent $event): void
    {
        $event->loadMissing('ticket.department', 'user');
        if (! $event->ticket) {
            return;
        }

        $title = NotificationType::labels()[NotificationType::MODERATION_ALERT];
        $message = "{$event->ticket->ticket_number}: Conversation moderation event needs review.";

        foreach ($this->tickets->departmentManagers($event->ticket) as $recipient) {
            if ($event->user_id === $recipient->id) {
                continue;
            }

            $this->notify($recipient, NotificationType::MODERATION_ALERT, $title, $message, $event->ticket, [
                'ticket_id' => $event->ticket->id,
                'ticket_number' => $event->ticket->ticket_number,
                'moderation_event_id' => $event->id,
                'severity' => $event->severity,
            ]);
        }
    }

    private function notify(User $user, string $type, string $title, string $message, ?Ticket $ticket = null, array $data = [], bool $dedupe = false): void
    {
        if ($this->webEnabled() && $user->web_notifications_enabled) {
            if (! $dedupe || $ticket === null || $this->notifications->findDuplicate($user, (int) $ticket->id, $type, 'web') === null) {
                $notification = $this->notifications->create([
                    'user_id' => $user->id,
                    'ticket_id' => $ticket?->id,
                    'type' => $type,
                    'channel' => 'web',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
                broadcast(new NotificationCreated($notification));
            }
        }

        if ($this->emailEnabled() && $user->email_notifications_enabled) {
            if ($dedupe && $ticket !== null && $this->notifications->findDuplicate($user, (int) $ticket->id, $type, 'email') !== null) {
                return;
            }

            $notification = $this->notifications->create([
                'user_id' => $user->id,
                'ticket_id' => $ticket?->id,
                'type' => $type,
                'channel' => 'email',
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
            $notification->loadMissing('user');
            $failure = $this->email->sendNotification($notification);
            $failure === null ? $this->notifications->markSent($notification) : $this->notifications->markFailed($notification, $failure);
        }
    }

    private function recipientsForTicketEvent(string $event, Ticket $ticket, ?User $actor = null): array
    {
        return match ($event) {
            NotificationType::TICKET_CREATED => array_filter(array_merge([$ticket->customer], $this->tickets->departmentManagers($ticket), $this->criticalRecipients($ticket), $this->activeAdministrators())),
            NotificationType::TICKET_ASSIGNED, NotificationType::TICKET_REASSIGNED => array_filter([$ticket->assignedAgent]),
            NotificationType::CUSTOMER_REPLIED => $this->operationalRecipients($ticket),
            NotificationType::TICKET_REOPENED => $this->reopenRecipients($ticket),
            NotificationType::TICKET_CANCELLED => $actor?->hasRole(Role::CUSTOMER)
                ? $this->staffNotificationRecipients($ticket)
                : array_filter([$ticket->customer]),
            NotificationType::AGENT_REPLIED, NotificationType::MANAGER_REPLIED, NotificationType::STATUS_CHANGED, NotificationType::TICKET_RESOLVED, NotificationType::TICKET_CLOSED => array_filter([$ticket->customer]),
            NotificationType::PRIORITY_CHANGED => $this->operationalRecipients($ticket),
            default => [],
        };
    }

    private function operationalRecipients(Ticket $ticket): array
    {
        return array_values(array_unique(array_filter(array_merge([$ticket->assignedAgent], $this->tickets->departmentManagers($ticket))), SORT_REGULAR));
    }

    private function reopenRecipients(Ticket $ticket): array
    {
        return $this->staffNotificationRecipients($ticket);
    }

    private function staffNotificationRecipients(Ticket $ticket): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$ticket->assignedAgent],
            $this->tickets->departmentManagers($ticket),
            $this->activeAdministrators(),
        )), SORT_REGULAR));
    }

    private function activeAdministrators(): array
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->whereHas('role', fn ($role) => $role->where('slug', Role::ADMINISTRATOR))
            ->get()
            ->all();
    }

    private function criticalRecipients(Ticket $ticket): array
    {
        return in_array($ticket->priority?->slug, ['high', 'critical'], true) ? $this->tickets->departmentManagers($ticket) : [];
    }

    private function webEnabled(): bool
    {
        return $this->config->settingValue('notification_web_enabled') !== '0';
    }

    private function emailEnabled(): bool
    {
        return $this->config->settingValue('notification_email_enabled') !== '0';
    }
}
