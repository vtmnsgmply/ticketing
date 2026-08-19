const actionLabels = {
  'auth.login_success': 'Login Successful',
  'auth.logout': 'Logout',
  'user.created': 'User Created',
  'user.updated': 'User Updated',
  'user.disabled': 'User Disabled',
  'user.reactivated': 'User Reactivated',
  'user.role_changed': 'User Role Changed',
  'user.departments_changed': 'User Departments Changed',
  'ticket.created': 'Ticket Created',
  'ticket.updated': 'Ticket Updated',
  'ticket.assigned': 'Ticket Assigned',
  'ticket.reassigned': 'Ticket Reassigned',
  'ticket.status_changed': 'Ticket Status Changed',
  'ticket.priority_changed': 'Ticket Priority Changed',
  'ticket.replied': 'Ticket Reply Added',
  'ticket.internal_note_added': 'Internal Note Added',
  'ticket.attachment_uploaded': 'Ticket Attachment Uploaded',
  'ticket.resolved': 'Ticket Resolved',
  'ticket.reopened': 'Ticket Reopened',
  'ticket.cancelled': 'Ticket Cancelled',
  'ticket.closed': 'Ticket Closed',
  'department.activated': 'Department Activated',
  'department.disabled': 'Department Disabled',
  'category.activated': 'Category Activated',
  'category.disabled': 'Category Disabled',
  'priority.activated': 'Priority Activated',
  'priority.disabled': 'Priority Disabled',
  'sla.updated': 'SLA Updated',
  'settings.updated': 'Settings Updated',
  'settings.email_updated': 'Email Settings Updated',
}

const actionTones = {
  'auth.login_success': 'emerald',
  'auth.logout': 'slate',
  'user.created': 'emerald',
  'user.updated': 'blue',
  'user.disabled': 'red',
  'user.reactivated': 'emerald',
  'user.role_changed': 'amber',
  'user.departments_changed': 'blue',
  'ticket.created': 'emerald',
  'ticket.updated': 'blue',
  'ticket.assigned': 'blue',
  'ticket.reassigned': 'amber',
  'ticket.status_changed': 'amber',
  'ticket.priority_changed': 'amber',
  'ticket.replied': 'blue',
  'ticket.internal_note_added': 'slate',
  'ticket.attachment_uploaded': 'blue',
  'ticket.resolved': 'emerald',
  'ticket.reopened': 'amber',
  'ticket.cancelled': 'red',
  'ticket.closed': 'slate',
  'department.activated': 'emerald',
  'department.disabled': 'red',
  'category.activated': 'emerald',
  'category.disabled': 'red',
  'priority.activated': 'emerald',
  'priority.disabled': 'red',
  'sla.updated': 'amber',
  'settings.updated': 'amber',
  'settings.email_updated': 'amber',
}

export function humanizeAuditAction(action) {
  if (!action) return 'Unknown Action'
  if (actionLabels[action]) return actionLabels[action]

  return action
    .split('.')
    .join(' ')
    .split('_')
    .join(' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

export function auditActionTone(action) {
  return actionTones[action] ?? 'slate'
}

export function formatAuditTimestamp(timestamp, { includeDate = true, includeTime = true } = {}) {
  if (!timestamp) return '-'
  const date = new Date(timestamp)
  if (Number.isNaN(date.getTime())) return '-'

  const parts = []
  if (includeDate) {
    parts.push(new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      timeZone: 'Asia/Manila',
    }).format(date))
  }
  if (includeTime) {
    parts.push(new Intl.DateTimeFormat('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      timeZone: 'Asia/Manila',
    }).format(date))
  }

  return parts.join(' ')
}
