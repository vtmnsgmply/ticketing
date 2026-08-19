import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, BarChart3, Inbox, ShieldCheck, Ticket, UserCheck, Users } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, EmptyState, InsightStrip, LiveActivityPanel, MetricCard, PageHeader, QuickActionsPanel, Skeleton, Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui'
import TicketPriorityBadge from '../../components/tickets/TicketPriorityBadge'
import TicketStatusBadge from '../../components/tickets/TicketStatusBadge'
import { getManagerDashboard } from '../../services/managerService'
import { showError } from '../../utils/alerts'

const quickActions = [
  { icon: BarChart3, label: 'Open Reports', to: '/manager/reports', variant: 'primary' },
  { icon: Ticket, label: 'View Overdue', to: '/manager/tickets?queue=overdue', variant: 'critical' },
  { icon: Inbox, label: 'View Unassigned', to: '/manager/tickets?queue=unassigned', variant: 'outline' },
  { icon: Users, label: 'Team', to: '/manager/team', variant: 'outline' },
]

function Queue({ title, tickets, queue }) {
  return (
    <Card>
      <div className="mb-4 flex items-center justify-between gap-3">
        <h2 className="text-lg font-semibold tracking-tight text-slate-950">{title}</h2>
        <Button as={Link} to={`/manager/tickets?queue=${queue}`} variant="secondary">View All</Button>
      </div>
      {tickets.length ? (
        <div className="space-y-3">
          {tickets.map((ticket) => (
            <Link
              className="block rounded-lg border border-slate-200 p-3 transition-colors duration-100 ease-standard hover:bg-slate-50/80"
              key={ticket.id}
              to={`/manager/tickets/${ticket.id}`}
            >
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="font-mono text-xs font-semibold text-slate-500">{ticket.ticket_number}</span>
                <TicketStatusBadge status={ticket.status} />
              </div>
              <p className="mt-1 text-sm font-semibold text-slate-900">{ticket.subject}</p>
              <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>{ticket.customer?.name ?? 'Customer'}</span>
                <TicketPriorityBadge priority={ticket.priority} />
                <span>{ticket.department?.name ?? 'No department'}</span>
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

export default function ManagerDashboard() {
  const [data, setData] = useState(null)

  useEffect(() => {
    getManagerDashboard().then(setData).catch((error) => showError(error.message || 'Unable to load manager dashboard.'))
  }, [])

  const insights = data
    ? [
        data.summary.unassigned > 0 ? { label: `${data.summary.unassigned} tickets unassigned`, tone: 'amber' } : null,
        data.summary.overdue > 0 ? { label: `${data.summary.overdue} tickets overdue`, tone: 'red' } : null,
        data.sla.due_soon > 0 ? { label: `${data.sla.due_soon} tickets due soon`, tone: 'amber' } : null,
      ].filter(Boolean)
    : []

  const activeAgents = data ? data.team_workload.filter((agent) => agent.is_active).length : null

  const activityItems = data?.recent_tickets?.map((ticket) => ({
    id: ticket.id,
    label: `${ticket.ticket_number} · ${ticket.subject}`,
    meta: ticket.customer?.name ?? 'Customer',
    timestamp: new Date(ticket.updated_at).toLocaleString(),
    tone: ticket.status === 'resolved' ? 'emerald' : ticket.status === 'overdue' ? 'red' : 'blue',
  }))

  return (
    <AppShell width="full">
      <PageHeader eyebrow="Manager Workspace" title="Dashboard" />
      {!data ? (
        <div className="space-y-4"><Skeleton className="h-28 w-full" /><Skeleton className="h-96 w-full" /></div>
      ) : (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            <MetricCard icon={Ticket} label="Open" tone="blue" topAccent value={data.summary.open} />
            <MetricCard critical={data.summary.unassigned > 0} icon={Inbox} label="Unassigned" tone="amber" topAccent value={data.summary.unassigned} />
            <MetricCard critical={data.summary.overdue > 0} icon={AlertTriangle} label="Overdue" tone="red" topAccent value={data.summary.overdue} />
            <MetricCard icon={AlertTriangle} label="Critical" tone="amber" topAccent value={data.summary.high_priority} />
            <MetricCard icon={ShieldCheck} label="SLA Compliance" tone="emerald" topAccent value={`${data.sla.compliance_percentage}%`} />
            <MetricCard icon={UserCheck} label="Active Agents" tone="indigo" topAccent value={activeAgents} />
          </div>

          <InsightStrip items={insights} />

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_340px]">
            <Card>
              <div className="mb-4 flex items-center justify-between">
                <h2 className="text-lg font-semibold tracking-tight text-slate-950">Team Workload</h2>
                <Button as={Link} to="/manager/team" variant="secondary"><Users className="h-4 w-4" />View Team</Button>
              </div>
              <TableContainer>
                <Table>
                  <Thead><tr><Th>Agent</Th><Th>Department</Th><Th>Open</Th><Th>In Progress</Th><Th>Overdue</Th><Th>High Priority</Th><Th>Resolved Today</Th></tr></Thead>
                  <Tbody>
                    {data.team_workload.map((agent) => (
                      <Tr key={agent.id} sla={agent.overdue > 0 ? 'overdue' : undefined}>
                        <Td primary>{agent.name}</Td>
                        <Td>{agent.department?.name ?? '-'}</Td>
                        <Td numeric>{agent.assigned_tickets}</Td>
                        <Td numeric>{agent.in_progress}</Td>
                        <Td numeric>{agent.overdue}</Td>
                        <Td numeric>{agent.high_priority}</Td>
                        <Td numeric>{agent.resolved_today}</Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              </TableContainer>
            </Card>

            <div className="space-y-6 xl:sticky xl:top-24 xl:self-start">
              <QuickActionsPanel actions={quickActions} description="Team and workload controls." />
              <LiveActivityPanel items={activityItems} title="Recent ticket activity" />
            </div>
          </div>

          <div>
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">SLA Risk</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <MetricCard label="On Track" tone="emerald" value={data.sla.on_track} />
              <MetricCard critical={data.sla.due_soon > 0} label="Due Soon" tone="amber" value={data.sla.due_soon} />
              <MetricCard critical={data.sla.overdue > 0} label="Overdue" tone="red" value={data.sla.overdue} />
            </div>
          </div>

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <Queue queue="unassigned" tickets={data.unassigned} title="Unassigned" />
            <Queue queue="overdue" tickets={data.overdue} title="Overdue" />
            <Queue queue="high_priority" tickets={data.high_priority} title="High Priority" />
          </div>
        </div>
      )}
    </AppShell>
  )
}
