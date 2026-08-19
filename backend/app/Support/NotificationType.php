<?php

namespace App\Support;

final class NotificationType
{
    public const TICKET_CREATED = 'ticket_created';
    public const TICKET_ASSIGNED = 'ticket_assigned';
    public const TICKET_REASSIGNED = 'ticket_reassigned';
    public const CUSTOMER_REPLIED = 'customer_replied';
    public const AGENT_REPLIED = 'agent_replied';
    public const MANAGER_REPLIED = 'manager_replied';
    public const STATUS_CHANGED = 'status_changed';
    public const PRIORITY_CHANGED = 'priority_changed';
    public const TICKET_RESOLVED = 'ticket_resolved';
    public const TICKET_REOPENED = 'ticket_reopened';
    public const TICKET_CLOSED = 'ticket_closed';
    public const TICKET_CANCELLED = 'ticket_cancelled';
    public const SLA_DUE_SOON = 'sla_due_soon';
    public const SLA_BREACHED = 'sla_breached';
    public const PASSWORD_CHANGED = 'password_changed';
    public const MODERATION_ALERT = 'moderation_alert';

    public static function labels(): array
    {
        return [
            self::TICKET_CREATED => 'Ticket created',
            self::TICKET_ASSIGNED => 'Ticket assigned',
            self::TICKET_REASSIGNED => 'Ticket reassigned',
            self::CUSTOMER_REPLIED => 'Customer replied',
            self::AGENT_REPLIED => 'Support replied',
            self::MANAGER_REPLIED => 'Manager replied',
            self::STATUS_CHANGED => 'Ticket status changed',
            self::PRIORITY_CHANGED => 'Ticket priority changed',
            self::TICKET_RESOLVED => 'Ticket resolved',
            self::TICKET_REOPENED => 'Ticket reopened',
            self::TICKET_CLOSED => 'Ticket closed',
            self::TICKET_CANCELLED => 'Ticket cancelled',
            self::SLA_DUE_SOON => 'SLA due soon',
            self::SLA_BREACHED => 'SLA breached',
            self::PASSWORD_CHANGED => 'Password changed',
            self::MODERATION_ALERT => 'Conversation moderation alert',
        ];
    }
}
