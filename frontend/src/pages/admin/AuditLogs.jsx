import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Filter, History, ListTree, Tags } from 'lucide-react'
import { adminService } from '../../services/adminService'
import Badge from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import Pagination from '../../components/ui/Pagination'
import SearchInput from '../../components/ui/SearchInput'
import Select from '../../components/ui/Select'
import Sheet from '../../components/ui/Sheet'
import Skeleton from '../../components/ui/Skeleton'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { auditActionTone, formatAuditTimestamp, humanizeAuditAction } from '../../utils/auditFormatting'
import { showError } from '../../utils/alerts'

function JsonBlock({ title, value }) {
  return (
    <Card className="shadow-none">
      <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</h3>
      <pre className="max-h-64 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(value ?? {}, null, 2)}</pre>
    </Card>
  )
}

export default function AuditLogs() {
  const [logs, setLogs] = useState([])
  const [pagination, setPagination] = useState(null)
  const [actions, setActions] = useState([])
  const [entityTypes, setEntityTypes] = useState([])
  const [filters, setFilters] = useState({ search: '', action: '', entity_type: '', per_page: 20 })
  const [selectedLogId, setSelectedLogId] = useState(null)
  const [selectedLog, setSelectedLog] = useState(null)

  async function load(params = filters) {
    try {
      const data = await adminService.getAuditLogs(params)
      setLogs(data.audit_logs)
      setPagination(data.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load audit logs.')
    }
  }

  useEffect(() => {
    load()
    adminService.getAuditActions().then(setActions).catch(() => setActions([]))
    adminService.getAuditEntityTypes().then(setEntityTypes).catch(() => setEntityTypes([]))
  }, [])

  useEffect(() => {
    if (!selectedLogId) {
      setSelectedLog(null)
      return
    }

    adminService.getAuditLog(selectedLogId).then(setSelectedLog).catch((error) => showError(error.message || 'Unable to load audit log.'))
  }, [selectedLogId])

  function submitFilters(event) {
    event.preventDefault()
    const next = { ...filters, page: 1 }
    setFilters(next)
    load(next)
  }

  function goToPage(page) {
    const next = { ...filters, page }
    setFilters(next)
    load(next)
  }

  return (
    <PageContainer width="full">
      <PageHeader breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Audit Logs' }]} eyebrow="Administration" title="Audit Logs" />

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <MetricCard icon={History} label="Total Logs" tone="indigo" topAccent value={pagination ? pagination.total : '-'} />
        <MetricCard icon={ListTree} label="Distinct Actions" tone="blue" topAccent value={actions.length} />
        <MetricCard icon={Tags} label="Entity Types Tracked" tone="emerald" topAccent value={entityTypes.length} />
      </div>

      <form className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center" onSubmit={submitFilters}>
        <SearchInput onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))} placeholder="Search" value={filters.search} />
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:shrink-0">
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, action: event.target.value }))} value={filters.action}>
            <option value="">All actions</option>
            {actions.map((action) => <option key={action} value={action}>{humanizeAuditAction(action)}</option>)}
          </Select>
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, entity_type: event.target.value }))} value={filters.entity_type}>
            <option value="">All entity types</option>
            {entityTypes.map((entityType) => <option key={entityType} value={entityType}>{entityType}</option>)}
          </Select>
          <Button type="submit" variant="secondary">
            <Filter aria-hidden="true" className="h-4 w-4" />
            Filter
          </Button>
        </div>
      </form>

      <TableContainer>
        <Table>
          <Thead>
            <tr>
              <Th>Date</Th>
              <Th>User</Th>
              <Th>Action</Th>
              <Th>Entity</Th>
              <Th>IP</Th>
              <Th>Actions</Th>
            </tr>
          </Thead>
          <Tbody>
            {logs.map((log) => (
              <Tr key={log.id}>
                <Td className="whitespace-nowrap" density="dense">
                  <time dateTime={log.created_at} title={log.created_at}>{formatAuditTimestamp(log.created_at)}</time>
                </Td>
                <Td density="dense">{log.user?.email ?? '-'}</Td>
                <Td density="dense" primary>
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <Badge tone={auditActionTone(log.action)}>{humanizeAuditAction(log.action)}</Badge>
                    </div>
                    <p className="font-mono text-xs font-normal text-slate-500">{log.action}</p>
                  </div>
                </Td>
                <Td className="font-mono text-xs" density="dense">{log.entity_type} #{log.entity_id ?? '-'}</Td>
                <Td className="font-mono text-xs" density="dense">{log.ip_address ?? '-'}</Td>
                <Td density="dense">
                  <Button onClick={() => setSelectedLogId(log.id)} size="sm" variant="secondary">View</Button>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <Pagination onPageChange={goToPage} pagination={pagination} />

      <Sheet
        description={selectedLog ? selectedLog.action : 'Loading audit event details.'}
        onOpenChange={(open) => {
          if (!open) setSelectedLogId(null)
        }}
        open={Boolean(selectedLogId)}
        title={selectedLog ? humanizeAuditAction(selectedLog.action) : 'Audit Detail'}
      >
        {selectedLog ? (
          <div className="space-y-4">
            <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div><dt className="font-semibold text-slate-500">Timestamp</dt><dd>{formatAuditTimestamp(selectedLog.created_at)}</dd></div>
              <div><dt className="font-semibold text-slate-500">Raw timestamp</dt><dd className="break-all font-mono text-xs">{selectedLog.created_at}</dd></div>
              <div><dt className="font-semibold text-slate-500">Actor</dt><dd>{selectedLog.user?.email ?? 'System'}</dd></div>
              <div><dt className="font-semibold text-slate-500">Entity</dt><dd>{selectedLog.entity_type ?? '-'} #{selectedLog.entity_id ?? '-'}</dd></div>
              <div><dt className="font-semibold text-slate-500">IP Address</dt><dd>{selectedLog.ip_address ?? '-'}</dd></div>
            </dl>
            <p className="break-words text-xs text-slate-500">{selectedLog.user_agent ?? 'No user agent captured.'}</p>
            <JsonBlock title="Metadata" value={selectedLog.metadata} />
            <JsonBlock title="Old Values" value={selectedLog.old_values} />
            <JsonBlock title="New Values" value={selectedLog.new_values} />
            <Button as={Link} to={`/admin/audit-logs/${selectedLog.id}`} variant="secondary">Open technical page</Button>
          </div>
        ) : (
          <div className="space-y-3">
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-40 w-full" />
          </div>
        )}
      </Sheet>
    </PageContainer>
  )
}
