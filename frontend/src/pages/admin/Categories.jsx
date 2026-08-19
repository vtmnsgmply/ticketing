import { useEffect, useState } from 'react'
import { Filter, Pencil, PlusCircle, Tags, UserCheck, UserX } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import CatalogStatusBand from '../../components/ui/CatalogStatusBand'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import SearchInput from '../../components/ui/SearchInput'
import Select from '../../components/ui/Select'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { confirmAction, promptCategoryForm, showError, showSuccess } from '../../utils/alerts'

// Fetched as one bounded batch (not paginated) — category catalogs stay
// small in practice, and this keeps the KPI row's counts accurate across
// the whole list instead of just the current page.
export default function Categories() {
  const [categories, setCategories] = useState([])
  const [departments, setDepartments] = useState([])
  const [filters, setFilters] = useState({ search: '', department_id: '', per_page: 100 })

  async function load(params = filters) {
    try {
      const [categoryData, departmentData] = await Promise.all([adminService.getCategories(params), adminService.getDepartments({ per_page: 100 })])
      setCategories(categoryData.categories)
      setDepartments(departmentData.departments)
    } catch (error) {
      await showError(error.message || 'Unable to load categories.')
    }
  }

  useEffect(() => {
    load()
  }, [])

  const activeCount = categories.filter((category) => category.is_active).length
  const unassignedCount = categories.filter((category) => !category.department_id).length

  async function openCreate() {
    const values = await promptCategoryForm({ departments })
    if (!values) return
    try {
      await adminService.saveCategory({ ...values, department_id: values.department_id || null })
      await showSuccess('Category created.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to create category.')
    }
  }

  async function openEdit(category) {
    const values = await promptCategoryForm({ category, departments })
    if (!values) return
    try {
      await adminService.saveCategory({ ...values, department_id: values.department_id || null }, category.id)
      await showSuccess('Category updated.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to update category.')
    }
  }

  async function toggle(category) {
    const next = !category.is_active
    const result = await confirmAction(`${next ? 'Activate' : 'Disable'} ${category.name}?`)
    if (!result.isConfirmed) return
    await adminService.changeCategoryStatus(category.id, next)
    await showSuccess('Category status updated.')
    await load()
  }

  return (
    <PageContainer width="full">
      <PageHeader
        actions={
          <Button onClick={openCreate}>
            <PlusCircle aria-hidden="true" className="h-4 w-4" />
            Add Category
          </Button>
        }
        breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Categories' }]}
        eyebrow="Administration"
        title="Categories"
      >
        Keep the bounded ticket category catalog aligned to active departments.
      </PageHeader>

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard icon={Tags} label="Total Categories" tone="blue" topAccent value={categories.length} />
        <MetricCard icon={UserCheck} label="Active" tone="emerald" topAccent value={activeCount} />
        <MetricCard icon={UserX} label="Disabled" tone="slate" value={categories.length - activeCount} />
        <MetricCard icon={Tags} label="Unassigned" tone={unassignedCount ? 'amber' : 'slate'} topAccent={unassignedCount > 0} value={unassignedCount} />
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
          placeholder="Search categories"
          value={filters.search}
        />
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:shrink-0">
          <Select className="lg:w-48" onChange={(event) => setFilters((current) => ({ ...current, department_id: event.target.value }))} value={filters.department_id}>
            <option value="">All departments</option>
            {departments.map((department) => (
              <option key={department.id} value={department.id}>
                {department.name}
              </option>
            ))}
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
              <Th>Name</Th>
              <Th>Slug</Th>
              <Th>Department</Th>
              <Th>Status</Th>
              <Th>Action</Th>
            </tr>
          </Thead>
          <Tbody>
            {categories.map((category) => (
              <Tr key={category.id}>
                <Td primary>{category.name}</Td>
                <Td className="font-mono text-xs">{category.slug}</Td>
                <Td>{category.department?.name ?? '-'}</Td>
                <Td>
                  <ActiveBadge active={category.is_active} />
                </Td>
                <Td>
                  <div className="flex flex-wrap items-center gap-2">
                    <Button onClick={() => openEdit(category)} size="sm" variant="secondary">
                      <Pencil aria-hidden="true" className="h-4 w-4" />
                      Edit
                    </Button>
                    <Button onClick={() => toggle(category)} size="sm" variant="secondary">
                      {category.is_active ? 'Disable' : 'Activate'}
                    </Button>
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <CatalogStatusBand>
        {categories.length} categories configured, {activeCount} active, {unassignedCount} not assigned to a department.
      </CatalogStatusBand>
    </PageContainer>
  )
}
