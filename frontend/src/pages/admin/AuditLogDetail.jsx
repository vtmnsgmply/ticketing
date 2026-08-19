import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { Button, Card, PageContainer, PageHeader, Skeleton } from '../../components/ui'
import { formatAuditTimestamp, humanizeAuditAction } from '../../utils/auditFormatting'
import { showError } from '../../utils/alerts'

function JsonBlock({ title, value }) {
  return (
    <Card>
      <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{title}</h2>
      <pre className="max-h-80 overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{JSON.stringify(value ?? {}, null, 2)}</pre>
    </Card>
  )
}

export default function AuditLogDetail() {
  const { id } = useParams()
  const [log, setLog] = useState(null)

  useEffect(() => {
    adminService.getAuditLog(id).then(setLog).catch((error) => showError(error.message || 'Unable to load audit log.'))
  }, [id])

  if (!log) {
    return (
      <PageContainer width="standard">
        <Skeleton className="h-10 w-64" />
        <Skeleton className="mt-4 h-80 w-full" />
      </PageContainer>
    )
  }

  return (
    <PageContainer width="standard">
      <PageHeader
        actions={<Button as={Link} to="/admin/audit-logs" variant="secondary"><ArrowLeft className="h-4 w-4" />Back</Button>}
        breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Audit Logs', to: '/admin/audit-logs' }, { label: 'Detail' }]}
        eyebrow="Audit"
        title={humanizeAuditAction(log.action)}
      />
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card>
          <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt className="font-semibold text-slate-500">Timestamp</dt><dd>{formatAuditTimestamp(log.created_at)}</dd></div>
            <div><dt className="font-semibold text-slate-500">Actor</dt><dd>{log.user?.email ?? 'System'}</dd></div>
            <div><dt className="font-semibold text-slate-500">Entity</dt><dd>{log.entity_type ?? '-'} #{log.entity_id ?? '-'}</dd></div>
            <div><dt className="font-semibold text-slate-500">IP Address</dt><dd>{log.ip_address ?? '-'}</dd></div>
          </dl>
          <p className="mt-4 break-words text-xs text-slate-500">{log.user_agent ?? 'No user agent captured.'}</p>
        </Card>
        <JsonBlock title="Metadata" value={log.metadata} />
        <JsonBlock title="Old Values" value={log.old_values} />
        <JsonBlock title="New Values" value={log.new_values} />
      </div>
    </PageContainer>
  )
}
