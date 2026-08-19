import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, CheckCircle2, Clock, Inbox, Pause, Ticket, UserCheck } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, EmptyState, LiveActivityPanel, MetricCard, PageHeader, QuickActionsPanel, Skeleton } from '../../components/ui'
import TicketPriorityBadge from '../../components/tickets/TicketPriorityBadge'
import TicketStatusBadge from '../../components/tickets/TicketStatusBadge'
import { getStaffDashboard } from '../../services/staffService'
import { showError } from '../../utils/alerts'

const quickActions = [
  { icon: Ticket, label: 'Ticket List', to: '/staff/tickets', variant: 'primary' },
  { icon: Clock, label: 'View Overdue', to: '/staff/tickets?queue=overdue', variant: 'critical' },
  { icon: Inbox, label: 'View Unassigned', to: '/staff/tickets?queue=unassigned', variant: 'outline' },
  { icon: UserCheck, label: 'My Assigned', to: '/staff/tickets?queue=assigned_to_me', variant: 'outline' },
]

function Queue({ title, tickets, queue }) {
  return (
    <Card>
      <div className="mb-4 flex items-center justify-between gap-3">
        <h2 className="text-lg font-semibold tracking-tight text-slate-950">{title}</h2>
        <Button as={Link} to={`/staff/tickets?queue=${queue}`} variant="secondary">View All</Button>
      </div>
      {tickets.length ? (
        <div className="space-y-3">
          {tickets.map((ticket) => (
            <Link
              className="block rounded-lg border border-slate-200 p-3 transition-colors duration-100 ease-standard hover:bg-slate-50/80"
              key={ticket.id}
              to={`/staff/tickets/${ticket.id}`}
            >
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="font-mono text-xs font-semibold text-slate-500">{ticket.ticket_number}</span>
                <TicketStatusBadge status={ticket.status} />
              </div>
              <p className="mt-1 text-sm font-semibold text-slate-900">{ticket.subject}</p>
              <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>{ticket.customer?.name ?? 'Customer'}</span>
                <TicketPriorityBadge priority={ticket.priority} />
                <span>Updated {new Date(ticket.updated_at).toLocaleString()}</span>
              </div>
            </Link>
          ))}
        </div>
      ) : (
        <EmptyState description="No tickets in this queue right now." icon={Inbox} title="Queue is clear" />
      )}
    </Card>
  )
}

export default function StaffDashboard() {
  const [data, setData] = useState(null)

  useEffect(() => {
    getStaffDashboard().then(setData).catch((error) => showError(error.message || 'Unable to load staff dashboard.'))
  }, [])

  const activityItems = data?.recently_updated?.map((ticket) => ({
    id: ticket.id,
    label: `${ticket.ticket_number} · ${ticket.subject}`,
    meta: ticket.customer?.name ?? 'Customer',
    timestamp: new Date(ticket.updated_at).toLocaleString(),
    tone: ticket.status === 'resolved' ? 'emerald' : ticket.status === 'overdue' ? 'red' : 'blue',
  }))

  return (
    <AppShell width="full">
      <PageHeader eyebrow="Staff Workspace" title="Dashboard" />
      {!data ? (
        <div className="space-y-4"><Skeleton className="h-28 w-full" /><Skeleton className="h-96 w-full" /></div>
      ) : (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            <MetricCard icon={Ticket} label="New" tone="blue" topAccent value={data.summary.new} />
            <MetricCard icon={UserCheck} label="Assigned to Me" tone="indigo" topAccent value={data.summary.assigned_to_me} />
            <MetricCard icon={Pause} label="Waiting for Customer" tone="slate" value={data.summary.waiting_for_customer} />
            <MetricCard critical={Boolean(data.summary.overdue)} icon={AlertTriangle} label="Overdue" tone="red" topAccent value={data.summary.overdue} />
            <MetricCard icon={AlertTriangle} label="High Priority" tone="amber" topAccent value={data.summary.high_priority} />
            <MetricCard icon={CheckCircle2} label="Resolved Today" tone="emerald" topAccent value={data.summary.resolved_today} />
          </div>

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_340px]">
            <div className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Queue queue="assigned_to_me" tickets={data.assigned_to_me} title="Assigned to Me" />
                <Queue queue="unassigned" tickets={data.unassigned} title="Unassigned" />
                <Queue queue="overdue" tickets={data.overdue} title="Overdue" />
                <Queue queue="high_priority" tickets={data.high_priority} title="High Priority" />
              </div>
              <Queue queue="recently_updated" tickets={data.recently_updated} title="Recently Updated" />
            </div>

            <div className="space-y-6 xl:sticky xl:top-24 xl:self-start">
              <QuickActionsPanel actions={quickActions} description="Jump straight to your queue." />
              <LiveActivityPanel items={activityItems} title="Recent ticket activity" />
            </div>
          </div>
        </div>
      )}
    </AppShell>
  )
}
