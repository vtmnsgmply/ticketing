import { useEffect, useState } from 'react'
import {
  BarChart3,
  Building2,
  Clock,
  Flame,
  Inbox,
  PlusCircle,
  ShieldCheck,
  Tags,
  Timer,
  UserCheck,
  UserPlus,
  Users,
} from 'lucide-react'
import { adminService } from '../../services/adminService'
import { Card, InsightStrip, LiveActivityPanel, MetricCard, PageContainer, PageHeader, QuickActionsPanel, SectionHeader } from '../../components/ui'
import { auditActionTone, formatAuditTimestamp, humanizeAuditAction } from '../../utils/auditFormatting'
import { showError } from '../../utils/alerts'

const quickActions = [
  { icon: PlusCircle, label: 'Create Ticket', to: '/tickets/create', variant: 'primary' },
  { icon: UserPlus, label: 'Add User', to: '/admin/users', variant: 'outline' },
  { icon: Clock, label: 'View Overdue', to: '/tickets?queue=overdue', variant: 'critical' },
  { icon: Timer, label: 'Review SLA', to: '/admin/sla', variant: 'outline' },
  { icon: BarChart3, label: 'Open Reports', to: '/admin/reports', variant: 'outline' },
  { icon: Building2, label: 'Departments', to: '/admin/departments', variant: 'outline' },
  { icon: Tags, label: 'Categories', to: '/admin/categories', variant: 'outline' },
]

function InsightPanel({ title, description, children }) {
  return (
    <Card className="bg-slate-50/70 shadow-none" padded={false}>
      <SectionHeader description={description} title={title} />
      <dl className="grid gap-3 px-5 pb-5">{children}</dl>
    </Card>
  )
}

function InsightValue({ label, value }) {
  return (
    <div>
      <dt className="text-xs font-medium text-slate-500">{label}</dt>
      <dd className="mt-1 text-lg font-semibold tabular-nums text-slate-950">{value}</dd>
    </div>
  )
}

export default function AdminDashboard() {
  const [summary, setSummary] = useState(null)
  const [activity, setActivity] = useState(null)

  useEffect(() => {
    adminService.getDashboard().then(setSummary).catch((error) => showError(error.message || 'Unable to load admin dashboard.'))
    adminService
      .getAuditLogs({ per_page: 8 })
      .then((data) => setActivity(data.audit_logs))
      .catch(() => setActivity([]))
  }, [])

  const insights = summary
    ? [
        summary.overdue_tickets > 0 ? { label: `${summary.overdue_tickets} tickets overdue`, tone: 'amber' } : null,
        summary.critical_tickets > 0 ? { label: `${summary.critical_tickets} critical tickets need review`, tone: 'red' } : null,
        summary.disabled_users > 0 ? { label: `${summary.disabled_users} users disabled`, tone: 'slate' } : null,
      ].filter(Boolean)
    : []

  const activityItems = activity?.map((log) => ({
    id: log.id,
    label: humanizeAuditAction(log.action),
    meta: log.user?.email ?? 'System',
    timestamp: formatAuditTimestamp(log.created_at, { includeDate: false }),
    tone: auditActionTone(log.action),
  }))

  return (
    <PageContainer width="full">
      <PageHeader eyebrow="Administration" title="Dashboard">
        System-wide health, configuration, and the latest administrative activity.
      </PageHeader>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <MetricCard animated hint={summary ? `${summary.active_users} active / ${summary.disabled_users} disabled` : undefined} icon={Users} label="Total Users" tone="indigo" topAccent value={summary ? summary.total_users : '-'} />
        <MetricCard animated hint={summary ? `${summary.overdue_tickets} overdue` : undefined} icon={Inbox} label="Open Tickets" tone="blue" topAccent value={summary ? summary.open_tickets : '-'} />
        <MetricCard animated critical={Boolean(summary?.overdue_tickets)} hint={summary?.overdue_tickets ? 'Action needed' : undefined} icon={Clock} label="Overdue Tickets" tone={summary?.overdue_tickets ? 'amber' : 'slate'} topAccent={Boolean(summary?.overdue_tickets)} value={summary ? summary.overdue_tickets : '-'} />
        <MetricCard animated critical={Boolean(summary?.critical_tickets)} hint={summary?.critical_tickets ? 'Action needed' : undefined} icon={Flame} label="Critical Tickets" tone={summary?.critical_tickets ? 'red' : 'slate'} topAccent={Boolean(summary?.critical_tickets)} value={summary ? summary.critical_tickets : '-'} />
        <MetricCard animated hint={summary ? `${summary.disabled_users} disabled` : undefined} icon={UserCheck} label="Active Users" tone="emerald" topAccent value={summary ? summary.active_users : '-'} />
      </div>

      {summary ? (
        <div className="mt-6">
          <InsightStrip items={insights} />
        </div>
      ) : null}

      <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_340px]">
        <LiveActivityPanel items={activityItems} loading={!activity} title="Recent administrative activity" />

        <div className="space-y-6 xl:sticky xl:top-24 xl:self-start">
          <QuickActionsPanel actions={quickActions} description="Jump straight to configuration." />

          <Card className="bg-slate-50/70 shadow-none" padded={false}>
            <SectionHeader description="Infrastructure status" title="System Health" />
            <div className="flex items-center gap-3 px-5 pb-5">
              <ShieldCheck aria-hidden="true" className="h-5 w-5 shrink-0 text-slate-400" strokeWidth={1.75} />
              <p className="text-sm text-slate-500">Monitoring is not configured yet.</p>
            </div>
          </Card>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <InsightPanel description="Enabled vs. disabled accounts." title="Account status">
          <div className="grid grid-cols-2 gap-3">
            <InsightValue label="Active" value={summary ? summary.active_users : '-'} />
            <InsightValue label="Disabled" value={summary ? summary.disabled_users : '-'} />
          </div>
        </InsightPanel>

        <InsightPanel description="Users by role." title="Customer base">
          <div className="grid grid-cols-3 gap-3">
            <InsightValue label="Customers" value={summary ? summary.total_customers : '-'} />
            <InsightValue label="Agents" value={summary ? summary.total_agents : '-'} />
            <InsightValue label="Managers" value={summary ? summary.total_managers : '-'} />
          </div>
        </InsightPanel>

        <InsightPanel description="Departments and categories in use." title="Structure">
          <div className="grid grid-cols-2 gap-3">
            <InsightValue label="Departments" value={summary ? summary.total_departments : '-'} />
            <InsightValue label="Categories" value={summary ? summary.total_categories : '-'} />
          </div>
        </InsightPanel>
      </div>
    </PageContainer>
  )
}
