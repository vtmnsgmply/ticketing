import { useCallback, useEffect, useState } from 'react'
import { Filter } from 'lucide-react'
import { Link } from 'react-router-dom'
import { adminService } from '../../services/adminService'
import { useAuth } from '../../hooks/useAuth'
import {
  dismissManagerModerationEvent,
  escalateManagerModerationEvent,
  getManagerModerationEvents,
  reviewManagerModerationEvent,
} from '../../services/managerService'
import { Badge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import Pagination from '../../components/ui/Pagination'
import Select from '../../components/ui/Select'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { showError, showSuccess } from '../../utils/alerts'

const actionLabels = { mask: 'Masked', block: 'Blocked', flag: 'Flagged', mask_flag: 'Masked + Flagged' }
const severityTones = { low: 'slate', medium: 'blue', high: 'amber', critical: 'red' }

export default function ModerationEvents() {
  const { user } = useAuth()
  const isManager = user?.role?.slug === 'manager'
  const [events, setEvents] = useState([])
  const [pagination, setPagination] = useState(null)
  const [filters, setFilters] = useState({ severity: '', action: '', review_status: '', per_page: 20 })

  const load = useCallback(async (params = filters) => {
    try {
      const data = isManager ? await getManagerModerationEvents(params) : await adminService.getModerationEvents(params)
      setEvents(data.events)
      setPagination(data.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load moderation events.')
    }
  }, [filters, isManager])

  useEffect(() => { load() }, [load])

  async function reviewAction(id, action) {
    try {
      if (action === 'review') await (isManager ? reviewManagerModerationEvent(id) : adminService.reviewModerationEvent(id))
      if (action === 'dismiss') await (isManager ? dismissManagerModerationEvent(id) : adminService.dismissModerationEvent(id))
      if (action === 'escalate') await (isManager ? escalateManagerModerationEvent(id) : adminService.escalateModerationEvent(id))
      await showSuccess('Moderation event updated.')
      await load()
    } catch (error) {
      await showError(error.message || 'Unable to update moderation event.')
    }
  }

  function submitFilters(event) {
    event.preventDefault()
    const next = { ...filters, page: 1 }
    setFilters(next)
  }

  function goToPage(page) {
    const next = { ...filters, page }
    setFilters(next)
  }

  function ticketPath(ticketId) {
    return isManager ? `/manager/tickets/${ticketId}` : `/tickets/${ticketId}`
  }

  return (
    <PageContainer width="full">
      <PageHeader breadcrumbs={[{ label: isManager ? 'Management' : 'Administration', to: isManager ? '/manager/dashboard' : '/admin' }, { label: 'Moderation Events' }]} eyebrow={isManager ? 'Management' : 'Administration'} title="Moderation Events" />
      <form className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center" onSubmit={submitFilters}>
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:flex lg:shrink-0">
          <Select className="lg:w-40" onChange={(event) => setFilters((current) => ({ ...current, severity: event.target.value }))} value={filters.severity}>
            <option value="">All severities</option>
            {['low', 'medium', 'high', 'critical'].map((value) => <option key={value} value={value}>{value}</option>)}
          </Select>
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, action: event.target.value }))} value={filters.action}>
            <option value="">All actions</option>
            {Object.entries(actionLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
          </Select>
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, review_status: event.target.value }))} value={filters.review_status}>
            <option value="">Any review status</option>
            {['not_required', 'pending', 'reviewed', 'dismissed', 'escalated'].map((value) => <option key={value} value={value}>{value.replaceAll('_', ' ')}</option>)}
          </Select>
          <Button type="submit" variant="secondary"><Filter className="h-4 w-4" />Filter</Button>
        </div>
      </form>

      <TableContainer>
        <Table>
          <Thead><tr><Th>Date</Th><Th>Ticket</Th><Th>User</Th><Th>Severity</Th><Th>Action</Th><Th>Terms</Th><Th>Status</Th><Th>Actions</Th></tr></Thead>
          <Tbody>
            {events.map((event) => (
              <Tr key={event.id}>
                <Td>{event.created_at ? new Date(event.created_at).toLocaleString() : '-'}</Td>
                <Td>{event.ticket ? <Link className="font-mono text-xs font-semibold text-indigo-600" to={ticketPath(event.ticket.id)}>{event.ticket.ticket_number}</Link> : '-'}</Td>
                <Td>{event.user?.name ?? '-'}</Td>
                <Td><Badge tone={severityTones[event.severity]}>{event.severity}</Badge></Td>
                <Td>{actionLabels[event.action] ?? event.action}</Td>
                <Td numeric>{event.matched_term_count}</Td>
                <Td><Badge tone={event.review_status === 'pending' ? 'amber' : event.review_status === 'escalated' ? 'red' : 'slate'}>{event.review_status?.replaceAll('_', ' ')}</Badge></Td>
                <Td>
                  <div className="flex flex-wrap gap-2">
                    <Button onClick={() => reviewAction(event.id, 'review')} size="sm" variant="secondary">Review</Button>
                    <Button onClick={() => reviewAction(event.id, 'dismiss')} size="sm" variant="secondary">Dismiss</Button>
                    <Button onClick={() => reviewAction(event.id, 'escalate')} size="sm" variant="danger">Escalate</Button>
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <Pagination onPageChange={goToPage} pagination={pagination} />
    </PageContainer>
  )
}
