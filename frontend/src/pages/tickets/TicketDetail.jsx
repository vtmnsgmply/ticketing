import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { clsx } from 'clsx'
import { ArrowLeft, Ban, CheckCircle2, RotateCcw, Trash2, XCircle } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import TicketActivityTimeline from '../../components/tickets/TicketActivityTimeline'
import TicketAttachmentList from '../../components/tickets/TicketAttachmentList'
import TicketConversation from '../../components/tickets/TicketConversation'
import TicketPriorityBadge from '../../components/tickets/TicketPriorityBadge'
import TicketReplyForm from '../../components/tickets/TicketReplyForm'
import TicketStatusBadge from '../../components/tickets/TicketStatusBadge'
import { Badge, SLABadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import PageHeader from '../../components/ui/PageHeader'
import Skeleton from '../../components/ui/Skeleton'
import { useAuth } from '../../hooks/useAuth'
import { getEchoClient } from '../../services/echoClient'
import { getManagerTeam } from '../../services/managerService'
import { addInternalNote, assignTicket, cancelTicket, changePriority, changeStatus, closeTicket, deleteTicketMessage, getTicket, getTicketOptions, markTicketRead, reactToTicketMessage, reopenTicket, replyToTicket } from '../../services/ticketService'
import { confirmAction, showBlockingNotice, showError, showSuccess } from '../../utils/alerts'

const FOUR_HOURS_MS = 4 * 60 * 60 * 1000
const customerLabelMeta = {
  priority: { label: 'Priority Customer', tone: 'amber' },
  vip: { label: 'VIP Customer', tone: 'violet' },
  watchlist: { label: 'Watchlist', tone: 'blue' },
  at_risk: { label: 'At Risk', tone: 'red' },
}

function patchConversation(ticket, event) {
  if (!ticket || Number(event.ticket_id) !== Number(ticket.id) || !event.message) return ticket

  const messages = ticket.messages ?? []
  const nextMessages = messages.some((message) => Number(message.id) === Number(event.message.id))
    ? messages.map((message) => (Number(message.id) === Number(event.message.id) ? event.message : message))
    : [...messages, event.message]

  nextMessages.sort((first, second) => new Date(first.created_at).getTime() - new Date(second.created_at).getTime())

  return {
    ...ticket,
    messages: nextMessages,
    updated_at: event.updated_at ?? ticket.updated_at,
  }
}

function slaState(dueAt, status) {
  if (['resolved', 'closed'].includes(status)) return 'resolved'
  if (status === 'cancelled' || !dueAt) return null
  const diff = new Date(dueAt).getTime() - Date.now()
  if (diff < 0) return 'overdue'
  if (diff < FOUR_HOURS_MS) return 'due_soon'
  return 'on_track'
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  })[char])
}

function cancellationNoticeHtml(ticket) {
  return `
    <div class="space-y-3 text-left">
      <p class="text-sm text-slate-600">The customer cancelled this ticket. No further action is needed.</p>
      <dl class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
        <div class="flex justify-between gap-4 border-b border-slate-200 py-1.5">
          <dt class="font-semibold text-slate-500">Ticket</dt>
          <dd class="text-right font-semibold text-slate-900">${escapeHtml(ticket?.ticket_number)}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-slate-200 py-1.5">
          <dt class="font-semibold text-slate-500">Subject</dt>
          <dd class="text-right text-slate-900">${escapeHtml(ticket?.subject)}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-slate-200 py-1.5">
          <dt class="font-semibold text-slate-500">Customer</dt>
          <dd class="text-right text-slate-900">${escapeHtml(ticket?.customer?.name)}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-slate-200 py-1.5">
          <dt class="font-semibold text-slate-500">Department</dt>
          <dd class="text-right text-slate-900">${escapeHtml(ticket?.department?.name ?? 'Unassigned')}</dd>
        </div>
        <div class="flex justify-between gap-4 py-1.5">
          <dt class="font-semibold text-slate-500">Priority</dt>
          <dd class="text-right text-slate-900">${escapeHtml(ticket?.priority?.name ?? ticket?.priority?.slug ?? 'Default')}</dd>
        </div>
      </dl>
    </div>
  `
}

export default function TicketDetail() {
  const { id } = useParams()
  const { user } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [options, setOptions] = useState({ statuses: [], priorities: [] })
  const [team, setTeam] = useState([])
  const [loading, setLoading] = useState(true)
  const [replyingTo, setReplyingTo] = useState(null)
  const [typingUsers, setTypingUsers] = useState({})
  const ticketChannelRef = useRef(null)
  const typingTimersRef = useRef({})
  const typingStopTimerRef = useRef(null)
  const lastReadSignatureRef = useRef('')
  const isStaff = ['agent', 'manager', 'administrator'].includes(user?.role?.slug)
  const isCustomer = user?.role?.slug === 'customer'
  const isAgent = user?.role?.slug === 'agent'
  const isManager = user?.role?.slug === 'manager'
  const basePath = isCustomer ? '/customer/tickets' : isAgent ? '/staff/tickets' : isManager ? '/manager/tickets' : '/tickets'

  const load = useCallback(async (showLoading = true) => {
    if (showLoading) setLoading(true)
    try {
      setTicket(await getTicket(id))
    } catch (error) {
      await showError(error.message || 'Unable to load ticket.')
    } finally {
      if (showLoading) setLoading(false)
    }
  }, [id])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    setReplyingTo(null)
    setTypingUsers({})
  }, [id])

  useEffect(() => {
    if (!ticket?.id || !user?.id) return undefined

    const echo = getEchoClient()
    if (!echo) return undefined

    const channelName = `tickets.${ticket.id}`
    const channel = echo.private(channelName)
    ticketChannelRef.current = channel

    channel.listen('.ticket.conversation.updated', (event) => {
      if (Number(event.ticket_id) !== Number(ticket.id)) return
      if (event.action === 'ticket.status_changed' && event.ticket?.status === 'cancelled') {
        setTicket((current) => current ? { ...current, ...event.ticket, messages: current.messages, activity: current.activity, attachments: current.attachments } : current)
        setReplyingTo(null)
        if (isStaff && Number(event.actor_id) !== Number(user.id) && event.ticket?.customer?.id === Number(event.actor_id)) {
          showBlockingNotice({
            html: cancellationNoticeHtml(event.ticket),
            icon: 'warning',
            title: 'Ticket cancelled by customer',
          })
        }
        load(false)
        return
      }
      if (event.message) {
        setTicket((current) => patchConversation(current, event))
        return
      }
      if (event.requires_refetch) {
        load(false)
      }
    })

    channel.listenForWhisper('typing', (event) => {
      if (!event?.user?.id || Number(event.user.id) === Number(user.id)) return

      window.clearTimeout(typingTimersRef.current[event.user.id])

      if (!event.is_typing) {
        setTypingUsers((current) => {
          const next = { ...current }
          delete next[event.user.id]
          return next
        })
        return
      }

      setTypingUsers((current) => ({ ...current, [event.user.id]: event.user }))
      typingTimersRef.current[event.user.id] = window.setTimeout(() => {
        setTypingUsers((current) => {
          const next = { ...current }
          delete next[event.user.id]
          return next
        })
      }, 3500)
    })

    return () => {
      ticketChannelRef.current = null
      window.clearTimeout(typingStopTimerRef.current)
      Object.values(typingTimersRef.current).forEach((timer) => window.clearTimeout(timer))
      typingTimersRef.current = {}
      echo.leave(channelName)
    }
  }, [isStaff, load, ticket?.id, user?.id])

  useEffect(() => {
    getTicketOptions().then(setOptions).catch(() => {})
  }, [])

  useEffect(() => {
    if (isManager) {
      getManagerTeam().then(setTeam).catch(() => {})
    }
  }, [isManager])

  useEffect(() => {
    if (!ticket?.id || !user?.id) return

    const unreadIncomingIds = (ticket.messages ?? [])
      .filter((message) => message.message_type !== 'internal_note')
      .filter((message) => Number(message.user?.id) !== Number(user.id))
      .filter((message) => !message.is_deleted)
      .filter((message) => !(message.read_by ?? []).some((read) => Number(read.user?.id) === Number(user.id)))
      .map((message) => message.id)

    const signature = unreadIncomingIds.join(',')
    if (!signature || signature === lastReadSignatureRef.current) return
    lastReadSignatureRef.current = signature

    const timer = window.setTimeout(() => {
      markTicketRead(ticket.id).catch(() => {})
    }, 250)

    return () => window.clearTimeout(timer)
  }, [ticket?.id, ticket?.messages, user?.id])

  async function submitReply(data, options = {}) {
    try {
      setTicket(await replyToTicket(id, data, options))
      setReplyingTo(null)
    } catch (error) {
      await showError(error.message || 'Unable to add reply.')
      throw error
    }
  }

  function broadcastTyping(value) {
    const channel = ticketChannelRef.current
    if (!channel?.whisper || !user?.id) return

    const isTyping = Boolean(value.trim())
    channel.whisper('typing', {
      is_typing: isTyping,
      user: {
        id: user.id,
        name: user.name,
        role: user.role,
      },
    })

    window.clearTimeout(typingStopTimerRef.current)
    if (isTyping) {
      typingStopTimerRef.current = window.setTimeout(() => {
        channel.whisper('typing', {
          is_typing: false,
          user: {
            id: user.id,
            name: user.name,
            role: user.role,
          },
        })
      }, 1800)
    }
  }

  async function submitNote(data, options = {}) {
    try {
      setTicket(await addInternalNote(id, data, options))
    } catch (error) {
      await showError(error.message || 'Unable to add internal note.')
      throw error
    }
  }

  async function deleteMessage(messageId) {
    const result = await confirmAction('Delete this message?', 'Delete message')
    if (!result.isConfirmed) return
    try {
      setTicket(await deleteTicketMessage(id, messageId))
    } catch (error) {
      await showError(error.message || 'Unable to delete message.')
    }
  }

  async function reactToMessage(messageId, reaction) {
    try {
      setTicket(await reactToTicketMessage(id, messageId, reaction))
    } catch (error) {
      await showError(error.message || 'Unable to update reaction.')
    }
  }

  async function action(label, fn) {
    const result = await confirmAction(`Continue with ${label}?`, 'Confirm ticket action')
    if (!result.isConfirmed) return
    try {
      setTicket(await fn(id))
      await showSuccess(`Ticket ${label}.`)
    } catch (error) {
      await showError(error.message || `Unable to ${label} ticket.`)
    }
  }

  async function assignSelf() {
    const result = await confirmAction('Assign this ticket to yourself?', 'Assign ticket')
    if (!result.isConfirmed) return
    try {
      setTicket(await assignTicket(id, { assigned_agent_id: user.id }))
      await showSuccess('Ticket assigned to you.')
    } catch (error) {
      await showError(error.message || 'Unable to assign ticket.')
    }
  }

  async function assignAgent(event) {
    const agentId = event.target.value
    if (!agentId || Number(agentId) === ticket.assigned_agent?.id) return
    const result = await confirmAction('Assign this ticket to the selected agent?', 'Assign ticket')
    if (!result.isConfirmed) return
    try {
      setTicket(await assignTicket(id, { assigned_agent_id: Number(agentId) }))
      await showSuccess('Ticket assigned.')
    } catch (error) {
      await showError(error.message || 'Unable to assign ticket.')
    }
  }

  async function updateStatus(event) {
    const status = event.target.value
    if (!status || status === ticket.status) return
    const result = await confirmAction(`Change status to ${status.replaceAll('_', ' ')}?`, 'Change status')
    if (!result.isConfirmed) return
    try {
      setTicket(await changeStatus(id, { status }))
      await showSuccess('Ticket status updated.')
    } catch (error) {
      await showError(error.message || 'Unable to change status.')
    }
  }

  async function updatePriority(event) {
    const priorityId = event.target.value
    if (!priorityId || Number(priorityId) === ticket.priority?.id) return
    const result = await confirmAction('Change ticket priority?', 'Change priority')
    if (!result.isConfirmed) return
    try {
      setTicket(await changePriority(id, { priority_id: Number(priorityId) }))
      await showSuccess('Ticket priority updated.')
    } catch (error) {
      await showError(error.message || 'Unable to change priority.')
    }
  }

  if (loading) {
    return (
      <AppShell width="standard">
        <div className="space-y-4">
          <Skeleton className="h-10 w-64" />
          <Skeleton className="h-48 w-full" />
          <Skeleton className="h-64 w-full" />
        </div>
      </AppShell>
    )
  }

  if (!ticket) {
    return (
      <AppShell width="standard">
        <p className="text-sm text-slate-500">Ticket not found.</p>
      </AppShell>
    )
  }

  const publicMessages = ticket.messages.filter((message) => message.message_type !== 'internal_note')
  const internalNotes = ticket.messages.filter((message) => message.message_type === 'internal_note')
  const repliesEnabled = !['closed', 'cancelled', 'resolved'].includes(ticket.status)
  const isFinalStatus = ['closed', 'cancelled'].includes(ticket.status)
  const statusMessage = ticket.status === 'closed'
    ? 'This ticket is closed. No further action is needed.'
    : ticket.status === 'cancelled'
      ? 'This ticket was cancelled. No further action is needed.'
      : ticket.status === 'resolved'
        ? 'This ticket is resolved. Reopen it if more work is required.'
        : null

  return (
    <AppShell width="standard">
      <PageHeader
        actions={
          <Button as={Link} to={basePath} variant="secondary">
            <ArrowLeft aria-hidden="true" className="h-4 w-4" />
            Back to tickets
          </Button>
        }
        eyebrow={ticket.ticket_number}
        title={ticket.subject}
      />

      <Card className="mb-6">
        <div className="flex flex-wrap items-center gap-2">
          <TicketStatusBadge status={ticket.status} />
          <TicketPriorityBadge priority={ticket.priority} />
        </div>
        <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{ticket.description}</p>
      </Card>

      <div className={clsx('grid grid-cols-1 items-start gap-6', isStaff ? 'xl:grid-cols-[320px_minmax(0,1fr)_340px]' : 'lg:grid-cols-[minmax(0,1fr)_360px]')}>
        {isStaff ? (
          <div className="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <TicketReplyForm allowAttachments={false} internal onSubmit={submitNote} />
            <InternalNotesList currentUser={user} notes={internalNotes} onDelete={deleteMessage} />

            <Card>
              <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Attachments</h2>
              <TicketAttachmentList attachments={ticket.attachments} />
            </Card>

            <Card>
              <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Activity</h2>
              <TicketActivityTimeline activity={ticket.activity} />
            </Card>
          </div>
        ) : null}

        <div className="min-w-0">
          <Card className="flex h-[calc(100vh-8rem)] min-h-[36rem] flex-col overflow-hidden">
            <h2 className="mb-4 text-lg font-semibold text-slate-900">Conversation</h2>
            <TicketConversation
              className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto pr-1"
              currentUser={user}
              key={ticket.id}
              messages={publicMessages}
              onDelete={deleteMessage}
              onReact={reactToMessage}
              onReply={setReplyingTo}
              repliesEnabled={repliesEnabled}
              typingUsers={Object.values(typingUsers)}
            />
            <div className="mt-2 border-t border-slate-100 pt-4">
              {repliesEnabled ? (
                <TicketReplyForm onCancelReply={() => setReplyingTo(null)} onSubmit={submitReply} onTyping={broadcastTyping} replyingTo={replyingTo} />
              ) : null}
            </div>
          </Card>
        </div>

        <div className="space-y-4 lg:sticky lg:top-24 lg:self-start">
          <Card>
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Ticket Info</h2>
            <dl className="space-y-4">
              <div>
                <dt className="text-xs font-semibold text-slate-500">Customer</dt>
                <dd className="mt-1 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                  <span>{ticket.customer?.name}</span>
                  {ticket.customer?.customer_label ? (
                    <Badge tone={customerLabelMeta[ticket.customer.customer_label]?.tone ?? 'slate'}>
                      {customerLabelMeta[ticket.customer.customer_label]?.label ?? ticket.customer.customer_label.replaceAll('_', ' ')}
                    </Badge>
                  ) : null}
                </dd>
              </div>
              <div>
                <dt className="text-xs font-semibold text-slate-500">Department</dt>
                <dd className="mt-0.5 text-sm font-medium text-slate-900">{ticket.department?.name ?? 'Unassigned'}</dd>
              </div>
              <div>
                <dt className="text-xs font-semibold text-slate-500">Category</dt>
                <dd className="mt-0.5 text-sm font-medium text-slate-900">{ticket.category?.name ?? 'Uncategorized'}</dd>
              </div>
              {isCustomer ? null : (
                <div>
                  <dt className="text-xs font-semibold text-slate-500">Assigned Agent</dt>
                  <dd className="mt-0.5 text-sm font-medium text-slate-900">{ticket.assigned_agent?.name ?? 'Unassigned'}</dd>
                </div>
              )}
              <div>
                <dt className="text-xs font-semibold text-slate-500">First Response Due</dt>
                <dd className="mt-1 flex items-center gap-2">
                  <span className="text-sm text-slate-900">
                    {ticket.first_response_due_at ? new Date(ticket.first_response_due_at).toLocaleString() : 'None'}
                  </span>
                  {slaState(ticket.first_response_due_at, ticket.status) ? <SLABadge state={slaState(ticket.first_response_due_at, ticket.status)} /> : null}
                </dd>
              </div>
              <div>
                <dt className="text-xs font-semibold text-slate-500">Resolution Due</dt>
                <dd className="mt-1 flex items-center gap-2">
                  <span className="text-sm text-slate-900">
                    {ticket.resolution_due_at ? new Date(ticket.resolution_due_at).toLocaleString() : 'None'}
                  </span>
                  {slaState(ticket.resolution_due_at, ticket.status) ? <SLABadge state={slaState(ticket.resolution_due_at, ticket.status)} /> : null}
                </dd>
              </div>
            </dl>
          </Card>

          <Card>
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Actions</h2>
            {statusMessage ? (
              <div className="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600">
                {statusMessage}
              </div>
            ) : null}
            {isFinalStatus ? null : (
              <div className="flex flex-wrap gap-2">
                {isAgent && ticket.assigned_agent === null ? (
                  <Button onClick={assignSelf} size="sm" variant="secondary">
                    Assign to Me
                  </Button>
                ) : null}
                {isStaff ? (
                  <>
                    {isManager ? (
                      <select className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" onChange={assignAgent} value={ticket.assigned_agent?.id ?? ''}>
                        <option value="">Unassigned</option>
                        {team.map((agent) => (
                          <option key={agent.id} value={agent.id}>{agent.name}</option>
                        ))}
                      </select>
                    ) : null}
                    <select className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" onChange={updateStatus} value={ticket.status}>
                      {options.statuses.map((status) => (
                        <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>
                      ))}
                    </select>
                    <select className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" onChange={updatePriority} value={ticket.priority?.id ?? ''}>
                      {options.priorities.map((priority) => (
                        <option key={priority.id} value={priority.id}>{priority.name}</option>
                      ))}
                    </select>
                  </>
                ) : null}
                {ticket.status === 'resolved' ? <Button onClick={() => action('reopened', reopenTicket)} size="sm" variant="secondary">
                  <RotateCcw aria-hidden="true" className="h-4 w-4" />
                  Reopen
                </Button> : null}
                {isStaff && ticket.status !== 'resolved' ? (
                  <Button onClick={() => action('resolved', () => changeStatus(id, { status: 'resolved' }))} size="sm" variant="secondary">
                    <CheckCircle2 aria-hidden="true" className="h-4 w-4" />
                    Resolve
                  </Button>
                ) : null}
                {isStaff && !isAgent && ticket.status === 'resolved' ? (
                  <Button onClick={() => action('closed', closeTicket)} size="sm" variant="secondary">
                    <XCircle aria-hidden="true" className="h-4 w-4" />
                    Close
                  </Button>
                ) : null}
                {isStaff && !isAgent && ticket.status !== 'resolved' ? <Button onClick={() => action('cancelled', cancelTicket)} size="sm" variant="danger">
                  <Ban aria-hidden="true" className="h-4 w-4" />
                  Cancel
                </Button> : null}
                {isCustomer && !['resolved', 'closed', 'cancelled'].includes(ticket.status) ? <Button onClick={() => action('cancelled', cancelTicket)} size="sm" variant="danger">
                  <Ban aria-hidden="true" className="h-4 w-4" />
                  Cancel Ticket
                </Button> : null}
              </div>
            )}
            {!isStaff && !statusMessage ? (
              <p className="text-sm text-slate-500">No ticket actions are available.</p>
              ) : null}
          </Card>

          {!isStaff ? (
            <Card>
              <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Attachments</h2>
              <TicketAttachmentList attachments={ticket.attachments} />
            </Card>
          ) : null}
        </div>
      </div>
    </AppShell>
  )
}

function InternalNotesList({ currentUser, notes, onDelete }) {
  if (!notes.length) return null

  return (
    <section className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
      <h2 className="mb-3 text-xs font-bold uppercase tracking-wide text-amber-700">Internal notes</h2>
      <div className="max-h-72 space-y-3 overflow-y-auto pr-1">
        {notes
          .slice()
          .sort((first, second) => new Date(second.created_at).getTime() - new Date(first.created_at).getTime())
          .map((note) => {
            const canDelete = !note.is_deleted && (note.user?.id === currentUser?.id || currentUser?.role?.slug === 'administrator')

            return (
              <article className="rounded-xl border border-amber-100 bg-white p-3 text-sm shadow-sm" key={note.id}>
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate font-semibold text-slate-900">{note.user?.name ?? 'System'}</p>
                    <time className="mt-0.5 block text-xs text-slate-500">{new Date(note.created_at).toLocaleString()}</time>
                  </div>
                  {canDelete ? (
                    <button className="shrink-0 rounded-full p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" onClick={() => onDelete(note.id)} type="button">
                      <Trash2 aria-hidden="true" className="h-4 w-4" />
                      <span className="sr-only">Delete note</span>
                    </button>
                  ) : null}
                </div>
                <p className="mt-2 whitespace-pre-wrap break-words text-slate-700">
                  {note.is_deleted ? `${note.deleted_by?.name ?? note.user?.name ?? 'Sender'} deleted this note.` : note.message}
                </p>
              </article>
            )
          })}
      </div>
    </section>
  )
}
