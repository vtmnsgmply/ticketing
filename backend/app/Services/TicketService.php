<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ModerationException;
use App\Events\TicketConversationUpdated;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketMessageReaction;
use App\Models\User;
use App\Repository\AdminConfigRepository;
use App\Repository\PriorityRepository;
use App\Repository\TicketMessageRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Support\AuditAction;
use App\Support\NotificationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class TicketService
{
    private const STAFF_ROLES = [Role::AGENT, Role::MANAGER, Role::ADMINISTRATOR];

    private const TRANSITIONS = [
        Ticket::STATUS_NEW => [Ticket::STATUS_OPEN, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
        Ticket::STATUS_OPEN => [Ticket::STATUS_ASSIGNED, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
        Ticket::STATUS_ASSIGNED => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
        Ticket::STATUS_IN_PROGRESS => [Ticket::STATUS_WAITING_FOR_CUSTOMER, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
        Ticket::STATUS_WAITING_FOR_CUSTOMER => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED],
        Ticket::STATUS_RESOLVED => [Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED],
        Ticket::STATUS_CLOSED => [],
        Ticket::STATUS_CANCELLED => [],
    ];

    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketMessageRepository $messages,
        private readonly PriorityRepository $priorities,
        private readonly UserRepository $users,
        private readonly SLAService $sla,
        private readonly TicketAttachmentService $attachments,
        private readonly TicketActivityService $activities,
        private readonly NotificationService $notifications,
        private readonly TelegramService $telegram,
        private readonly AdminConfigRepository $config,
        private readonly AuditLogService $audit,
        private readonly ConversationModerationService $moderation,
    ) {
    }

    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $user->loadMissing('role');

        return $this->tickets->paginateForUser($user, $filters);
    }

    public function show(User $user, int $ticketId): Ticket
    {
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if ($this->markVisibleMessagesRead($ticket, $user)) {
            broadcast(new TicketConversationUpdated($ticket, $user, 'message.read'));

            return $this->tickets->findById($ticketId);
        }

        return $ticket;
    }

    public function markRead(User $user, int $ticketId): Ticket
    {
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if ($this->markVisibleMessagesRead($ticket, $user)) {
            broadcast(new TicketConversationUpdated($ticket, $user, 'message.read'));

            return $this->tickets->findById($ticketId);
        }

        return $ticket;
    }

    public function create(User $user, array $data, array $files = []): Ticket
    {
        $user->loadMissing('role');

        $customer = $user;
        if (! $user->hasRole(Role::CUSTOMER) && ! empty($data['customer_id'])) {
            $customer = $this->users->findById((int) $data['customer_id']);
            if (! $customer || ! $customer->hasRole(Role::CUSTOMER)) {
                throw new BusinessRuleException('A valid customer is required.');
            }
        }

        $priorityId = $data['priority_id'] ?? $this->priorities->defaultPriority()?->id;

        if ($priorityId === null || $this->priorities->findActiveById((int) $priorityId) === null) {
            throw new BusinessRuleException('A valid active priority is required.');
        }

        return DB::transaction(function () use ($user, $customer, $data, $files, $priorityId): Ticket {
            $deadlines = $this->sla->deadlinesForPriority((int) $priorityId);
            $ticket = $this->tickets->create([
                'ticket_number' => $this->tickets->nextTicketNumber(),
                'customer_id' => $customer->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'department_id' => $data['department_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'priority_id' => $priorityId,
                'status' => Ticket::STATUS_NEW,
                'first_response_due_at' => $deadlines['first_response_due_at'],
                'resolution_due_at' => $deadlines['resolution_due_at'],
            ]);

            $this->activities->record($ticket, $user, 'ticket_created', null, $ticket->ticket_number);
            $this->storeAttachments($ticket, $user, $files);
            $this->recordAdminTicketAudit($user, AuditAction::TICKET_CREATED, $ticket, null, $ticket->only([
                'ticket_number',
                'customer_id',
                'subject',
                'department_id',
                'category_id',
                'priority_id',
                'status',
            ]));
            $this->notifications->ticketEvent(NotificationType::TICKET_CREATED, $ticket);

            return $this->tickets->findById((int) $ticket->id);
        });
    }

    public function update(User $user, int $ticketId, array $data): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if (! $user->hasRole(Role::CUSTOMER, Role::MANAGER, Role::ADMINISTRATOR)) {
            throw new BusinessRuleException('You are not allowed to update this ticket.');
        }

        if ($user->hasRole(Role::CUSTOMER) && $ticket->customer_id !== $user->id) {
            throw new BusinessRuleException('You are not allowed to update this ticket.');
        }

        $allowed = $user->hasRole(Role::CUSTOMER)
            ? array_intersect_key($data, array_flip(['subject', 'description']))
            : array_intersect_key($data, array_flip(['subject', 'description', 'department_id', 'category_id']));

        $updated = $this->tickets->update($ticket, $allowed);
        $this->activities->record($updated, $user, 'ticket_updated');
        $this->recordAdminTicketAudit($user, AuditAction::TICKET_UPDATED, $updated, $ticket->only(array_keys($allowed)), $allowed);

        return $updated;
    }

    public function assign(User $user, int $ticketId, int $agentId): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if ($user->hasRole(Role::AGENT)) {
            if ($agentId !== (int) $user->id || $ticket->assigned_agent_id !== null) {
                throw new BusinessRuleException('Agents can only claim permitted unassigned tickets for themselves.');
            }
            $agent = $user;
        } else {
            if (! $user->hasRole(Role::MANAGER, Role::ADMINISTRATOR)) {
                throw new BusinessRuleException('You are not allowed to assign tickets.');
            }
            $agent = $this->users->findById($agentId);
        }

        if (! $agent || ! $agent->is_active || ! $agent->hasRole(Role::AGENT, Role::MANAGER, Role::ADMINISTRATOR)) {
            throw new BusinessRuleException('The selected user cannot be assigned to tickets.');
        }

        $agentDepartmentIds = array_values(array_unique(array_filter(array_merge($this->users->departmentIds($agent), [(int) $agent->primary_department_id]))));
        if ($user->hasRole(Role::MANAGER) && $ticket->department_id !== null && ! in_array((int) $ticket->department_id, $agentDepartmentIds, true)) {
            throw new BusinessRuleException('The selected agent is not eligible for this ticket department.');
        }

        $old = $ticket->assigned_agent_id;
        $status = $ticket->status === Ticket::STATUS_NEW || $ticket->status === Ticket::STATUS_OPEN
            ? Ticket::STATUS_ASSIGNED
            : $ticket->status;
        $updated = $this->tickets->update($ticket, ['assigned_agent_id' => $agentId, 'status' => $status]);
        $this->activities->record($updated, $user, $old ? 'ticket_reassigned' : 'ticket_assigned', $old, $agentId);
        $this->recordAdminTicketAudit(
            $user,
            $old ? AuditAction::TICKET_REASSIGNED : AuditAction::TICKET_ASSIGNED,
            $updated,
            ['assigned_agent_id' => $old, 'status' => $ticket->status],
            ['assigned_agent_id' => $agentId, 'status' => $status],
        );
        $this->notifications->ticketEvent($old ? NotificationType::TICKET_REASSIGNED : NotificationType::TICKET_ASSIGNED, $updated);

        return $updated;
    }

    public function changeStatus(User $user, int $ticketId, string $status): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if (! $this->canChangeStatus($user, $ticket, $status)) {
            throw new BusinessRuleException('This status transition is not allowed.');
        }

        $oldStatus = $ticket->status;
        $data = ['status' => $status];

        if ($status === Ticket::STATUS_RESOLVED) {
            $data['resolved_at'] = now();
        } elseif ($status === Ticket::STATUS_CLOSED) {
            $data['closed_at'] = now();
        } elseif ($status === Ticket::STATUS_CANCELLED) {
            $data['cancelled_at'] = now();
        } elseif ($status === Ticket::STATUS_OPEN && $oldStatus === Ticket::STATUS_RESOLVED) {
            $data['resolved_at'] = null;
        }

        $updated = $this->tickets->update($ticket, $data);
        $this->activities->record($updated, $user, 'status_changed', $oldStatus, $status);
        $event = match (true) {
            $status === Ticket::STATUS_OPEN && $oldStatus === Ticket::STATUS_RESOLVED => NotificationType::TICKET_REOPENED,
            $status === Ticket::STATUS_RESOLVED => NotificationType::TICKET_RESOLVED,
            $status === Ticket::STATUS_CLOSED => NotificationType::TICKET_CLOSED,
            $status === Ticket::STATUS_CANCELLED => NotificationType::TICKET_CANCELLED,
            default => NotificationType::STATUS_CHANGED,
        };
        $this->recordAdminTicketAudit($user, $this->auditActionForStatus($status, $oldStatus), $updated, ['status' => $oldStatus], $data);
        $this->deferStatusSideEffects(function () use ($updated, $user, $event, $oldStatus, $status): void {
            broadcast(new TicketConversationUpdated($updated, $user, 'ticket.status_changed'));
            $this->notifications->ticketEvent($event, $updated, $user);
            $this->notifyCustomerOnTelegramForStaffStatusChange($user, $updated, $oldStatus, $status);
        });

        return $updated;
    }

    public function changePriority(User $user, int $ticketId, int $priorityId): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if (! $user->hasRole(Role::AGENT, Role::MANAGER, Role::ADMINISTRATOR)) {
            throw new BusinessRuleException('You are not allowed to change ticket priority.');
        }

        if ($this->priorities->findActiveById($priorityId) === null) {
            throw new BusinessRuleException('A valid active priority is required.');
        }

        $deadlines = $this->sla->deadlinesForPriority($priorityId);
        $updated = $this->tickets->update($ticket, [
            'priority_id' => $priorityId,
            'first_response_due_at' => $deadlines['first_response_due_at'],
            'resolution_due_at' => $deadlines['resolution_due_at'],
        ]);
        $this->activities->record($updated, $user, 'priority_changed', $ticket->priority_id, $priorityId);
        $this->recordAdminTicketAudit(
            $user,
            AuditAction::TICKET_PRIORITY_CHANGED,
            $updated,
            ['priority_id' => $ticket->priority_id],
            [
                'priority_id' => $priorityId,
                'first_response_due_at' => $deadlines['first_response_due_at'],
                'resolution_due_at' => $deadlines['resolution_due_at'],
            ],
        );
        $this->notifications->ticketEvent(NotificationType::PRIORITY_CHANGED, $updated);

        return $updated;
    }

    public function reply(User $user, int $ticketId, ?string $message, array $files = [], bool $internal = false, ?int $replyToMessageId = null): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if ($internal && ! $user->hasRole(...self::STAFF_ROLES)) {
            throw new BusinessRuleException('Customers cannot create internal notes.');
        }

        if ($user->hasRole(Role::CUSTOMER) && in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED], true)) {
            throw new BusinessRuleException('This ticket is not open for customer replies.');
        }

        $type = $internal
            ? TicketMessage::TYPE_INTERNAL_NOTE
            : ($user->hasRole(Role::CUSTOMER) ? TicketMessage::TYPE_CUSTOMER_REPLY : TicketMessage::TYPE_AGENT_REPLY);
        try {
            $moderation = $this->moderation->moderate((string) $message, $user);
        } catch (Throwable) {
            throw new BusinessRuleException('We could not process your message safely. Please try again.');
        }

        if ($moderation->shouldBlock) {
            $this->moderation->recordEvent($ticket, $user, $moderation);
            throw new ModerationException();
        }

        $replyTo = null;
        if ($replyToMessageId !== null) {
            $replyTo = $this->messages->findForTicket($ticket->id, $replyToMessageId);

            if ($replyTo === null || $replyTo->deleted_at !== null) {
                throw new BusinessRuleException('The message being replied to is not available.');
            }

            if ($user->hasRole(Role::CUSTOMER) && $replyTo->message_type === TicketMessage::TYPE_INTERNAL_NOTE) {
                throw new BusinessRuleException('The message being replied to is not available.');
            }
        }

        return DB::transaction(function () use ($ticket, $user, $message, $files, $type, $internal, $replyTo, $moderation): Ticket {
            $created = $this->messages->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => trim($moderation->filteredText),
                'message_type' => $type,
                'reply_to_message_id' => $replyTo?->id,
            ]);
            $this->moderation->recordEvent($ticket, $user, $moderation, $created);

            $this->storeAttachments($ticket, $user, $files, $created);

            $updates = [];
            if ($type === TicketMessage::TYPE_AGENT_REPLY && $ticket->first_responded_at === null) {
                $updates['first_responded_at'] = now();
            }

            $updated = empty($updates) ? $ticket : $this->tickets->update($ticket, $updates);
            $action = match ($type) {
                TicketMessage::TYPE_CUSTOMER_REPLY => 'customer_replied',
                TicketMessage::TYPE_AGENT_REPLY => 'agent_replied',
                default => 'internal_note_added',
            };
            $this->activities->record($updated, $user, $action);
            $this->recordAdminTicketAudit(
                $user,
                $type === TicketMessage::TYPE_INTERNAL_NOTE ? AuditAction::TICKET_INTERNAL_NOTE_ADDED : AuditAction::TICKET_REPLIED,
                $updated,
                null,
                ['message_type' => $type, 'ticket_message_id' => $created->id, 'reply_to_message_id' => $replyTo?->id],
                ['attachments_count' => count($files)],
            );
            broadcast(new TicketConversationUpdated($updated, $user, $internal ? 'message.internal_note_created' : 'message.created', (int) $created->id));
            $this->notifications->ticketEvent($action === 'agent_replied' && $user->hasRole(Role::MANAGER) ? NotificationType::MANAGER_REPLIED : $action, $updated);

            return $this->tickets->findById((int) $ticket->id);
        });
    }

    public function deleteMessage(User $user, int $ticketId, int $messageId): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);
        $message = $this->messages->findForTicket($ticket->id, $messageId);

        if ($message === null || ((int) $message->user_id !== (int) $user->id && ! $user->hasRole(Role::ADMINISTRATOR))) {
            throw new BusinessRuleException('This message cannot be deleted.');
        }

        if ($message->deleted_at === null) {
            $this->attachments->deleteForMessage($message);
            $this->messages->markDeleted($message, $user);
            $this->activities->record($ticket, $user, 'message_deleted', null, (string) $message->id);
            broadcast(new TicketConversationUpdated($ticket, $user, 'message.deleted', (int) $message->id));
        }

        return $this->tickets->findById($ticketId);
    }

    public function reactToMessage(User $user, int $ticketId, int $messageId, string $reaction): Ticket
    {
        $ticket = $this->requireAccessibleTicket($user, $ticketId);
        $message = $this->messages->findForTicket($ticket->id, $messageId);
        $reaction = trim($reaction);

        if ($message === null || $message->deleted_at !== null || $reaction === '') {
            throw new BusinessRuleException('This message cannot be reacted to.');
        }

        $existing = TicketMessageReaction::query()
            ->where('ticket_message_id', $message->id)
            ->where('user_id', $user->id)
            ->get();
        $currentReaction = $existing->first()?->reaction;

        if ($currentReaction === $reaction) {
            TicketMessageReaction::query()
                ->where('ticket_message_id', $message->id)
                ->where('user_id', $user->id)
                ->delete();
        } else {
            TicketMessageReaction::query()
                ->where('ticket_message_id', $message->id)
                ->where('user_id', $user->id)
                ->delete();

            TicketMessageReaction::query()->create([
                'ticket_message_id' => $message->id,
                'user_id' => $user->id,
                'reaction' => $reaction,
            ]);
        }

        broadcast(new TicketConversationUpdated($ticket, $user, 'message.reaction_updated', (int) $message->id));

        return $this->tickets->findById($ticketId);
    }

    public function reopen(User $user, int $ticketId): Ticket
    {
        $ticket = $this->requireAccessibleTicket($user, $ticketId);

        if ($ticket->status !== Ticket::STATUS_RESOLVED) {
            throw new BusinessRuleException('This ticket cannot be reopened.');
        }

        if ($user->hasRole(Role::CUSTOMER) && $this->config->settingValue('allow_customer_reopen_resolved') === '0') {
            throw new BusinessRuleException('Customers are not allowed to reopen resolved tickets.');
        }

        if ($user->hasRole(Role::CUSTOMER)) {
            $limit = (int) ($this->config->settingValue('customer_reopen_limit') ?? 3);
            $reopenCount = $ticket->activities()
                ->where('user_id', $user->id)
                ->where('action', 'status_changed')
                ->where('old_value', Ticket::STATUS_RESOLVED)
                ->where('new_value', Ticket::STATUS_OPEN)
                ->count();

            if ($reopenCount >= $limit) {
                throw new BusinessRuleException("Customers can reopen a resolved ticket up to {$limit} times.");
            }
        }

        return $this->changeStatus($user, $ticketId, Ticket::STATUS_OPEN);
    }

    public function close(User $user, int $ticketId): Ticket
    {
        return $this->changeStatus($user, $ticketId, Ticket::STATUS_CLOSED);
    }

    public function cancel(User $user, int $ticketId): Ticket
    {
        return $this->changeStatus($user, $ticketId, Ticket::STATUS_CANCELLED);
    }

    public function uploadAttachments(User $user, int $ticketId, array $files): Ticket
    {
        $user->loadMissing('role');
        $ticket = $this->requireAccessibleTicket($user, $ticketId);
        $this->storeAttachments($ticket, $user, $files);
        $this->activities->record($ticket, $user, 'attachment_uploaded');
        $this->recordAdminTicketAudit($user, AuditAction::TICKET_ATTACHMENT_UPLOADED, $ticket, null, null, ['attachments_count' => count($files)]);

        return $this->tickets->findById($ticketId);
    }

    public function canAccessAttachment(User $user, TicketAttachment $attachment): bool
    {
        return $this->tickets->findForUser($attachment->ticket_id, $user) !== null;
    }

    public function requireAccessibleTicket(User $user, int $ticketId): Ticket
    {
        $ticket = $this->tickets->findForUser($ticketId, $user);

        if ($ticket === null) {
            throw new BusinessRuleException('Ticket not found or access is not allowed.');
        }

        return $ticket;
    }

    private function markVisibleMessagesRead(Ticket $ticket, User $user): bool
    {
        $user->loadMissing('role');
        $visibleTypes = $user->hasRole(Role::CUSTOMER)
            ? [TicketMessage::TYPE_AGENT_REPLY]
            : ($user->hasRole(...self::STAFF_ROLES) ? [TicketMessage::TYPE_CUSTOMER_REPLY] : []);

        if ($visibleTypes === []) {
            return false;
        }

        $messageIds = TicketMessage::query()
            ->where('ticket_id', $ticket->id)
            ->whereIn('message_type', $visibleTypes)
            ->where('user_id', '<>', $user->id)
            ->whereNull('deleted_at')
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->pluck('id');

        if ($messageIds->isEmpty()) {
            return false;
        }

        $now = now();
        DB::table('ticket_message_reads')->insert(
            $messageIds->map(fn ($messageId) => [
                'ticket_message_id' => $messageId,
                'user_id' => $user->id,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );

        return true;
    }

    private function canChangeStatus(User $user, Ticket $ticket, string $status): bool
    {
        if (! in_array($status, self::TRANSITIONS[$ticket->status] ?? [], true)) {
            return false;
        }

        if ($user->hasRole(Role::CUSTOMER)) {
            if ($ticket->customer_id !== $user->id) {
                return false;
            }

            return ($ticket->status === Ticket::STATUS_RESOLVED && $status === Ticket::STATUS_OPEN)
                || ($status === Ticket::STATUS_CANCELLED && ! in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED], true));
        }

        if ($status === Ticket::STATUS_CLOSED) {
            return $user->hasRole(Role::MANAGER, Role::ADMINISTRATOR);
        }

        return $user->hasRole(...self::STAFF_ROLES);
    }

    private function storeAttachments(Ticket $ticket, User $user, array $files, ?TicketMessage $message = null): void
    {
        if ($files === []) {
            return;
        }

        $maxMb = (int) ($this->config->settingValue('attachment_max_size_mb') ?: 10);
        $maxBytes = $maxMb * 1024 * 1024;
        foreach ($files as $file) {
            if (($file->getSize() ?: 0) > $maxBytes) {
                throw new BusinessRuleException("Attachments may not be larger than {$maxMb} MB.");
            }
        }

        $this->attachments->storeMany($ticket, $user, array_values($files), $message);
    }

    private function notifyCustomerOnTelegramForStaffStatusChange(User $actor, Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        $actor->loadMissing('role');
        if (! $actor->hasRole(...self::STAFF_ROLES)) {
            return;
        }

        $ticket->loadMissing('customer');
        if ($ticket->customer === null || blank($ticket->customer->telegram_profile)) {
            return;
        }

        $this->telegram->sendTicketStatusChanged($ticket->customer, $ticket, $oldStatus, $newStatus);
    }

    private function deferStatusSideEffects(callable $callback): void
    {
        if (app()->runningInConsole()) {
            $callback();
            return;
        }

        app()->terminating($callback);
    }

    private function recordAdminTicketAudit(User $actor, string $action, Ticket $ticket, ?array $old = null, ?array $new = null, ?array $metadata = null): void
    {
        $actor->loadMissing('role');
        if (! $actor->hasRole(Role::ADMINISTRATOR)) {
            return;
        }

        $this->audit->record(
            $actor,
            $action,
            'ticket',
            $ticket->id,
            $old,
            $new,
            request(),
            ['ticket_number' => $ticket->ticket_number] + ($metadata ?? []),
        );
    }

    private function auditActionForStatus(string $newStatus, string $oldStatus): string
    {
        return match (true) {
            $newStatus === Ticket::STATUS_OPEN && $oldStatus === Ticket::STATUS_RESOLVED => AuditAction::TICKET_REOPENED,
            $newStatus === Ticket::STATUS_RESOLVED => AuditAction::TICKET_RESOLVED,
            $newStatus === Ticket::STATUS_CLOSED => AuditAction::TICKET_CLOSED,
            $newStatus === Ticket::STATUS_CANCELLED => AuditAction::TICKET_CANCELLED,
            default => AuditAction::TICKET_STATUS_CHANGED,
        };
    }
}
