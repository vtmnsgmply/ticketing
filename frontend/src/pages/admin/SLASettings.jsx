import { useEffect, useState } from 'react'
import { Clock3, Pencil, Timer, UserCheck } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import CatalogStatusBand from '../../components/ui/CatalogStatusBand'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { promptSlaForm, showError, showSuccess } from '../../utils/alerts'

export default function SLASettings() {
  const [rules, setRules] = useState([])

  async function load() {
    try {
      setRules(await adminService.getSla())
    } catch (error) {
      await showError(error.message || 'Unable to load SLA rules.')
    }
  }

  useEffect(() => {
    load()
  }, [])

  const activeCount = rules.filter((rule) => rule.is_active).length
  const businessHoursCount = rules.filter((rule) => rule.use_business_hours).length

  async function openEdit(rule) {
    const values = await promptSlaForm(rule)
    if (!values) return
    try {
      await adminService.updateSla(rule.id, values)
      await showSuccess('SLA rule updated.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to update SLA rule.')
    }
  }

  return (
    <PageContainer width="full">
      <PageHeader breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'SLA' }]} eyebrow="Administration" title="SLA">
        Review response and resolution targets attached to the priority catalog.
      </PageHeader>

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <MetricCard icon={Timer} label="Total Rules" tone="blue" topAccent value={rules.length} />
        <MetricCard icon={UserCheck} label="Active" tone="emerald" topAccent value={activeCount} />
        <MetricCard icon={Clock3} label="Business Hours Rules" tone={businessHoursCount ? 'blue' : 'slate'} topAccent={businessHoursCount > 0} value={businessHoursCount} />
      </div>

      <TableContainer>
        <Table>
          <Thead>
            <tr>
              <Th>Priority</Th>
              <Th>First Response (min)</Th>
              <Th>Resolution (min)</Th>
              <Th>Pause Waiting</Th>
              <Th>Business Hours</Th>
              <Th>Active</Th>
              <Th>Action</Th>
            </tr>
          </Thead>
          <Tbody>
            {rules.map((rule) => (
              <Tr key={rule.id}>
                <Td primary>{rule.priority?.name}</Td>
                <Td numeric>{rule.first_response_minutes}</Td>
                <Td numeric>{rule.resolution_minutes}</Td>
                <Td>{rule.pause_on_waiting_customer ? 'Yes' : 'No'}</Td>
                <Td>{rule.use_business_hours ? 'Yes' : 'No'}</Td>
                <Td>
                  <ActiveBadge active={rule.is_active} />
                </Td>
                <Td>
                  <Button onClick={() => openEdit(rule)} size="sm" variant="secondary">
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
        {rules.length} SLA rules configured, {activeCount} active, {businessHoursCount} using business-hours timing.
      </CatalogStatusBand>
    </PageContainer>
  )
}
