import { useEffect, useState } from 'react'
import { Building2, Filter, Pencil, PlusCircle, UserCheck, UserX } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import CatalogStatusBand from '../../components/ui/CatalogStatusBand'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import SearchInput from '../../components/ui/SearchInput'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { confirmAction, promptDepartmentForm, showError, showSuccess } from '../../utils/alerts'

// Fetched as one bounded batch (not paginated) — department catalogs stay
// small in practice, and this keeps the KPI row's counts accurate across
// the whole list instead of just the current page.
export default function Departments() {
  const [departments, setDepartments] = useState([])
  const [filters, setFilters] = useState({ search: '', per_page: 100 })

  async function load(params = filters) {
    try {
      const data = await adminService.getDepartments(params)
      setDepartments(data.departments)
    } catch (error) {
      await showError(error.message || 'Unable to load departments.')
    }
  }

  useEffect(() => {
    load()
  }, [])

  const totalMembers = departments.reduce((sum, department) => sum + (department.users_count ?? 0), 0)
  const activeCount = departments.filter((department) => department.is_active).length

  async function openCreate() {
    const values = await promptDepartmentForm()
    if (!values) return
    try {
      await adminService.saveDepartment(values)
      await showSuccess('Department created.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to create department.')
    }
  }

  async function openEdit(department) {
    const values = await promptDepartmentForm(department)
    if (!values) return
    try {
      await adminService.saveDepartment(values, department.id)
      await showSuccess('Department updated.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to update department.')
    }
  }

  async function toggle(department) {
    const next = !department.is_active
    const result = await confirmAction(`${next ? 'Activate' : 'Disable'} ${department.name}?`)
    if (!result.isConfirmed) return
    await adminService.changeDepartmentStatus(department.id, next)
    await showSuccess('Department status updated.')
    await load()
  }

  return (
    <PageContainer width="full">
      <PageHeader
        actions={
          <Button onClick={openCreate}>
            <PlusCircle aria-hidden="true" className="h-4 w-4" />
            Add Department
          </Button>
        }
        breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Departments' }]}
        eyebrow="Administration"
        title="Departments"
      >
        Manage the small set of departments used for routing, ownership, and reporting.
      </PageHeader>

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard icon={Building2} label="Total Departments" tone="blue" topAccent value={departments.length} />
        <MetricCard icon={UserCheck} label="Active" tone="emerald" topAccent value={activeCount} />
        <MetricCard icon={UserX} label="Disabled" tone="slate" value={departments.length - activeCount} />
        <MetricCard icon={Building2} label="Total Members" tone="blue" topAccent value={totalMembers} />
      </div>

      <form
        className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center"
        onSubmit={(event) => {
          event.preventDefault()
          load(filters)
        }}
      >
        <SearchInput
          onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
          placeholder="Search departments"
          value={filters.search}
        />
        <Button type="submit" variant="secondary">
          <Filter aria-hidden="true" className="h-4 w-4" />
          Filter
        </Button>
      </form>

      <TableContainer>
        <Table>
          <Thead>
            <tr>
              <Th>Name</Th>
              <Th>Slug</Th>
              <Th>Members</Th>
              <Th>Status</Th>
              <Th>Action</Th>
            </tr>
          </Thead>
          <Tbody>
            {departments.map((department) => (
              <Tr key={department.id}>
                <Td primary>{department.name}</Td>
                <Td className="font-mono text-xs">{department.slug}</Td>
                <Td numeric>{department.users_count ?? 0}</Td>
                <Td>
                  <ActiveBadge active={department.is_active} />
                </Td>
                <Td>
                  <div className="flex flex-wrap items-center gap-2">
                    <Button onClick={() => openEdit(department)} size="sm" variant="secondary">
                      <Pencil aria-hidden="true" className="h-4 w-4" />
                      Edit
                    </Button>
                    <Button onClick={() => toggle(department)} size="sm" variant="secondary">
                      {department.is_active ? 'Disable' : 'Activate'}
                    </Button>
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <CatalogStatusBand>
        {departments.length} departments configured, {activeCount} active, {totalMembers} assigned members.
      </CatalogStatusBand>
    </PageContainer>
  )
}
