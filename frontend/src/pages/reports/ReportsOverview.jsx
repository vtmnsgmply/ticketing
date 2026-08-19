import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { BarChart3, Clock, TimerOff } from 'lucide-react'
import { Badge, Button, Card, EmptyState, Field, Input, MetricCard, PageContainer, PageHeader, Skeleton } from '../../components/ui'
import { useAuth } from '../../hooks/useAuth'
import { getReportOverview } from '../../services/reportService'
import { showError } from '../../utils/alerts'

function minutes(value) {
  if (!value) return '0m'
  const hours = Math.floor(value / 60)
  const mins = Math.round(value % 60)
  return hours ? `${hours}h ${mins}m` : `${mins}m`
}

export default function ReportsOverview() {
  const { user } = useAuth()
  const [filters, setFilters] = useState({ date_from: '', date_to: '' })
  const [data, setData] = useState(null)

  const load = useCallback(async (params = filters) => {
    try {
      setData(await getReportOverview(user?.role?.slug, params))
    } catch (error) {
      await showError(error.message || 'Unable to load reports.')
    }
  }, [filters, user?.role?.slug])

  useEffect(() => {
    if (user?.role?.slug) load()
  }, [load, user?.role?.slug])

  if (!data) {
    return (
      <PageContainer width="full">
        <Skeleton className="h-10 w-64" />
        <Skeleton className="mt-4 h-80 w-full" />
      </PageContainer>
    )
  }

  const isManager = user?.role?.slug === 'manager'
  const ticketBase = isManager ? '/manager/tickets' : '/tickets'

  return (
    <PageContainer width="full">
      <PageHeader eyebrow={isManager ? 'Manager Workspace' : 'Administration'} title="Reports" />
      <form className="mb-6 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) md:grid-cols-[1fr_1fr_auto]" onSubmit={(event) => { event.preventDefault(); load(filters) }}>
        <Field label="From">
          <Input onChange={(event) => setFilters((current) => ({ ...current, date_from: event.target.value }))} type="date" value={filters.date_from} />
        </Field>
        <Field label="To">
          <Input onChange={(event) => setFilters((current) => ({ ...current, date_to: event.target.value }))} type="date" value={filters.date_to} />
        </Field>
        <div className="flex items-end"><Button type="submit">Apply</Button></div>
      </form>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
        <MetricCard animated icon={BarChart3} label="Tickets Created" tone="blue" topAccent value={data.summary.created} />
        <MetricCard animated icon={BarChart3} label="Tickets Resolved" tone="emerald" topAccent value={data.summary.resolved} />
        <MetricCard animated icon={TimerOff} label="Overdue Tickets" tone={data.summary.overdue ? 'red' : 'slate'} topAccent={data.summary.overdue > 0} value={data.summary.overdue} />
        <MetricCard icon={Clock} label="Avg First Response" tone="blue" topAccent value={minutes(data.summary.average_first_response_minutes)} />
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <Card>
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Status Breakdown</h2>
          <div className="mt-4 space-y-2">{data.status.map((row) => <div className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2" key={row.label}><span className="capitalize">{row.label.replaceAll('_', ' ')}</span><Badge>{row.total}</Badge></div>)}</div>
        </Card>
        <Card>
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">SLA</h2>
          <div className="mt-4 grid grid-cols-2 gap-3">
            <MetricCard label="Due Soon" value={data.sla.due_soon} />
            <MetricCard label="Breached" value={data.sla.breached} />
            <MetricCard label="First Response SLA" value={`${data.summary.first_response_sla_compliance}%`} />
            <MetricCard label="Resolution SLA" value={`${data.summary.resolution_sla_compliance}%`} />
          </div>
        </Card>
      </div>

      <Card className="mt-6">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Overdue Tickets</h2>
        {data.overdue.length ? (
          <div className="mt-4 divide-y divide-slate-100">{data.overdue.map((ticket) => <Link className="flex flex-col gap-1 py-3 text-sm hover:text-indigo-700 sm:flex-row sm:items-center sm:justify-between" key={ticket.id} to={`${ticketBase}/${ticket.id}`}><span className="font-semibold">{ticket.ticket_number} - {ticket.subject}</span><span>{ticket.priority?.name ?? 'No priority'}</span></Link>)}</div>
        ) : <EmptyState description="No SLA breaches matched this reporting period." icon={TimerOff} title="No overdue tickets" />}
      </Card>
    </PageContainer>
  )
}
