<?php

namespace App\Support;

final class AuditAction
{
    public const USER_CREATED = 'user.created';
    public const USER_UPDATED = 'user.updated';
    public const USER_DISABLED = 'user.disabled';
    public const USER_REACTIVATED = 'user.reactivated';
    public const USER_ROLE_CHANGED = 'user.role_changed';
    public const USER_DEPARTMENTS_CHANGED = 'user.departments_changed';
    public const DEPARTMENT_CREATED = 'department.created';
    public const DEPARTMENT_UPDATED = 'department.updated';
    public const DEPARTMENT_ACTIVATED = 'department.activated';
    public const DEPARTMENT_DISABLED = 'department.disabled';
    public const CATEGORY_CREATED = 'category.created';
    public const CATEGORY_UPDATED = 'category.updated';
    public const CATEGORY_ACTIVATED = 'category.activated';
    public const CATEGORY_DISABLED = 'category.disabled';
    public const PRIORITY_UPDATED = 'priority.updated';
    public const PRIORITY_ACTIVATED = 'priority.activated';
    public const PRIORITY_DISABLED = 'priority.disabled';
    public const SLA_UPDATED = 'sla.updated';
    public const SETTINGS_SYSTEM_UPDATED = 'settings.system_updated';
    public const SETTINGS_EMAIL_UPDATED = 'settings.email_updated';
    public const TICKET_CREATED = 'ticket.created';
    public const TICKET_UPDATED = 'ticket.updated';
    public const TICKET_ASSIGNED = 'ticket.assigned';
    public const TICKET_REASSIGNED = 'ticket.reassigned';
    public const TICKET_STATUS_CHANGED = 'ticket.status_changed';
    public const TICKET_PRIORITY_CHANGED = 'ticket.priority_changed';
    public const TICKET_REPLIED = 'ticket.replied';
    public const TICKET_INTERNAL_NOTE_ADDED = 'ticket.internal_note_added';
    public const TICKET_ATTACHMENT_UPLOADED = 'ticket.attachment_uploaded';
    public const TICKET_REOPENED = 'ticket.reopened';
    public const TICKET_RESOLVED = 'ticket.resolved';
    public const TICKET_CLOSED = 'ticket.closed';
    public const TICKET_CANCELLED = 'ticket.cancelled';
    public const AUTH_LOGIN_SUCCESS = 'auth.login_success';
    public const AUTH_LOGIN_FAILED = 'auth.login_failed';
    public const AUTH_LOGOUT = 'auth.logout';
    public const AUTH_PASSWORD_CHANGED = 'auth.password_changed';
    public const AUDIT_EXPORTED = 'audit.exported';
    public const REPORT_EXPORTED = 'report.exported';
    public const MODERATION_WORD_CREATED = 'moderation.word_created';
    public const MODERATION_WORD_UPDATED = 'moderation.word_updated';
    public const MODERATION_WORD_DISABLED = 'moderation.word_disabled';
    public const MODERATION_SETTINGS_UPDATED = 'moderation.settings_updated';
    public const MODERATION_EVENT_REVIEWED = 'moderation.event_reviewed';
    public const MODERATION_EVENT_ESCALATED = 'moderation.event_escalated';

    public static function all(): array
    {
        return [
            self::USER_CREATED,
            self::USER_UPDATED,
            self::USER_DISABLED,
            self::USER_REACTIVATED,
            self::USER_ROLE_CHANGED,
            self::USER_DEPARTMENTS_CHANGED,
            self::DEPARTMENT_CREATED,
            self::DEPARTMENT_UPDATED,
            self::DEPARTMENT_ACTIVATED,
            self::DEPARTMENT_DISABLED,
            self::CATEGORY_CREATED,
            self::CATEGORY_UPDATED,
            self::CATEGORY_ACTIVATED,
            self::CATEGORY_DISABLED,
            self::PRIORITY_UPDATED,
            self::PRIORITY_ACTIVATED,
            self::PRIORITY_DISABLED,
            self::SLA_UPDATED,
            self::SETTINGS_SYSTEM_UPDATED,
            self::SETTINGS_EMAIL_UPDATED,
            self::TICKET_CREATED,
            self::TICKET_UPDATED,
            self::TICKET_ASSIGNED,
            self::TICKET_REASSIGNED,
            self::TICKET_STATUS_CHANGED,
            self::TICKET_PRIORITY_CHANGED,
            self::TICKET_REPLIED,
            self::TICKET_INTERNAL_NOTE_ADDED,
            self::TICKET_ATTACHMENT_UPLOADED,
            self::TICKET_REOPENED,
            self::TICKET_RESOLVED,
            self::TICKET_CLOSED,
            self::TICKET_CANCELLED,
            self::AUTH_LOGIN_SUCCESS,
            self::AUTH_LOGIN_FAILED,
            self::AUTH_LOGOUT,
            self::AUTH_PASSWORD_CHANGED,
            self::AUDIT_EXPORTED,
            self::REPORT_EXPORTED,
            self::MODERATION_WORD_CREATED,
            self::MODERATION_WORD_UPDATED,
            self::MODERATION_WORD_DISABLED,
            self::MODERATION_SETTINGS_UPDATED,
            self::MODERATION_EVENT_REVIEWED,
            self::MODERATION_EVENT_ESCALATED,
        ];
    }
}
