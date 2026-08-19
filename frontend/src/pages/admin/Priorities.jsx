import { useEffect, useState } from 'react'
import { Pencil, SlidersHorizontal, UserCheck, UserX } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import CatalogStatusBand from '../../components/ui/CatalogStatusBand'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { promptPriorityForm, showError, showSuccess } from '../../utils/alerts'

export default function Priorities() {
  const [priorities, setPriorities] = useState([])

  async function load() {
    try {
      setPriorities(await adminService.getPriorities())
    } catch (error) {
      await showError(error.message || 'Unable to load priorities.')
    }
  }

  useEffect(() => {
    load()
  }, [])

  const activeCount = priorities.filter((priority) => priority.is_active).length

  async function openEdit(priority) {
    const values = await promptPriorityForm(priority)
    if (!values) return
    try {
      await adminService.updatePriority(priority.id, values)
      await showSuccess('Priority updated.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to update priority.')
    }
  }

  return (
    <PageContainer width="full">
      <PageHeader breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Priorities' }]} eyebrow="Administration" title="Priorities">
        Maintain the compact priority scale used by ticket routing and SLA policy.
      </PageHeader>

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <MetricCard icon={SlidersHorizontal} label="Total Priorities" tone="blue" topAccent value={priorities.length} />
        <MetricCard icon={UserCheck} label="Active" tone="emerald" topAccent value={activeCount} />
        <MetricCard icon={UserX} label="Disabled" tone="slate" value={priorities.length - activeCount} />
      </div>

      <TableContainer>
        <Table>
          <Thead>
            <tr>
              <Th>Name</Th>
              <Th>Sort</Th>
              <Th>Status</Th>
              <Th>Action</Th>
            </tr>
          </Thead>
          <Tbody>
            {priorities.map((priority) => (
              <Tr key={priority.id}>
                <Td primary>{priority.name}</Td>
                <Td numeric>{priority.sort_order}</Td>
                <Td>
                  <ActiveBadge active={priority.is_active} />
                </Td>
                <Td>
                  <Button onClick={() => openEdit(priority)} size="sm" variant="secondary">
                    <Pencil aria-hidden="true" className="h-4 w-4" />
                    Edit
                  </Button>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <CatalogStatusBand>
        {priorities.length} priorities configured, {activeCount} active. Sort order controls display and escalation order.
      </CatalogStatusBand>
    </PageContainer>
  )
}
