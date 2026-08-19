import { History } from 'lucide-react'
import EmptyState from '../ui/EmptyState'

const hiddenActions = new Set(['customer_replied', 'agent_replied', 'manager_replied'])

const actionLabels = {
  attachment_uploaded: 'Attachment Uploaded',
  internal_note_added: 'Internal Note Added',
  message_deleted: 'Message Deleted',
  priority_changed: 'Priority Changed',
  status_changed: 'Status Changed',
  ticket_assigned: 'Ticket Assigned',
  ticket_created: 'Ticket Created',
  ticket_reassigned: 'Ticket Reassigned',
  ticket_updated: 'Ticket Updated',
}

function humanize(value) {
  if (value === null || value === undefined || value === '') return 'none'
  return `${value}`.replaceAll('_', ' ')
}

function statusLabel(value) {
  return humanize(value).replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function activityDetails(item) {
  switch (item.action) {
    case 'ticket_created':
      return item.new_value ? `Created ticket ${item.new_value}.` : 'Created this ticket.'
    case 'status_changed':
      return `Changed status from ${statusLabel(item.old_value)} to ${statusLabel(item.new_value)}.`
    case 'ticket_assigned':
      return item.new_value ? `Assigned this ticket to user #${item.new_value}.` : 'Assigned this ticket.'
    case 'ticket_reassigned':
      return `Reassigned this ticket from user #${item.old_value} to user #${item.new_value}.`
    case 'priority_changed':
      return `Changed priority from #${item.old_value} to #${item.new_value}.`
    case 'message_deleted':
      return item.new_value ? `Deleted message #${item.new_value}.` : 'Deleted a message.'
    case 'attachment_uploaded':
      return 'Uploaded ticket attachments.'
    case 'internal_note_added':
      return 'Added an internal note.'
    case 'ticket_updated':
      return 'Updated ticket details.'
    default:
      return null
  }
}

export default function TicketActivityTimeline({ activity }) {
  const visibleActivity = activity.filter((item) => !hiddenActions.has(item.action))

  if (!visibleActivity.length) {
    return <EmptyState description="Actions taken on this ticket will be logged here." icon={History} title="No activity yet" />
  }

  return (
    <ol className="space-y-3">
      {visibleActivity.map((item) => (
        <li className="flex items-start gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0" key={item.id}>
          <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500" />
          <div className="min-w-0">
            <p className="text-sm font-medium text-slate-900">{actionLabels[item.action] ?? statusLabel(item.action)}</p>
            {activityDetails(item) ? <p className="mt-1 text-xs leading-5 text-slate-600">{activityDetails(item)}</p> : null}
            <p className="text-xs text-slate-500">
              {item.user?.name ?? 'System'} &middot; <time>{new Date(item.created_at).toLocaleString()}</time>
            </p>
          </div>
        </li>
      ))}
    </ol>
  )
}
