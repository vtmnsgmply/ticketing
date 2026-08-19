import { useEffect, useState } from 'react'
import { Filter, Pencil, ShieldCheck, UserCheck, UserPlus, UserX, Users as UsersIcon } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge, Badge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Checkbox from '../../components/ui/Checkbox'
import Field from '../../components/ui/Field'
import Input from '../../components/ui/Input'
import MetricCard from '../../components/ui/MetricCard'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import Pagination from '../../components/ui/Pagination'
import SearchInput from '../../components/ui/SearchInput'
import Select from '../../components/ui/Select'
import Sheet from '../../components/ui/Sheet'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { confirmAction, showError, showSuccess } from '../../utils/alerts'

const blankUserForm = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_id: '',
  primary_department_id: '',
  phone: '',
  company: '',
  customer_label: '',
  telegram_profile: '',
  is_active: true,
}

const customerLabels = [
  { value: 'priority', label: 'Priority', tone: 'amber' },
  { value: 'vip', label: 'VIP', tone: 'violet' },
  { value: 'watchlist', label: 'Watchlist', tone: 'blue' },
  { value: 'at_risk', label: 'At Risk', tone: 'red' },
]

function labelMeta(value) {
  return customerLabels.find((label) => label.value === value)
}

export default function Users() {
  const [users, setUsers] = useState([])
  const [roles, setRoles] = useState([])
  const [departments, setDepartments] = useState([])
  const [pagination, setPagination] = useState(null)
  const [filters, setFilters] = useState({ search: '', role_id: '', is_active: '', per_page: 20 })
  const [summary, setSummary] = useState(null)
  const [sheetMode, setSheetMode] = useState(null)
  const [editingUser, setEditingUser] = useState(null)
  const [userForm, setUserForm] = useState(blankUserForm)
  const [savingUser, setSavingUser] = useState(false)

  async function load(params = filters) {
    try {
      const [userData, roleData, departmentData] = await Promise.all([adminService.getUsers(params), adminService.getRoles(), adminService.getDepartments({ per_page: 100 })])
      setUsers(userData.users)
      setPagination(userData.pagination)
      setRoles(roleData)
      setDepartments(departmentData.departments)
    } catch (error) {
      await showError(error.message || 'Unable to load users.')
    }
  }

  useEffect(() => {
    load()
    adminService.getDashboard().then(setSummary).catch(() => setSummary(null))
  }, [])

  const administrators = summary
    ? summary.total_users - summary.total_customers - summary.total_agents - summary.total_managers
    : null
  const customerRoleId = roles.find((role) => role.slug === 'customer')?.id
  const selectedRoleIsCustomer = customerRoleId !== undefined && Number(userForm.role_id) === Number(customerRoleId)

  function openCreateUser() {
    setEditingUser(null)
    setUserForm(blankUserForm)
    setSheetMode('create')
  }

  function openEditUser(user) {
    setEditingUser(user)
    setUserForm({
      name: user.name ?? '',
      email: user.email ?? '',
      password: '',
      password_confirmation: '',
      role_id: user.role_id ? String(user.role_id) : '',
      primary_department_id: user.primary_department_id ? String(user.primary_department_id) : '',
      phone: user.phone ?? '',
      company: user.company ?? '',
      customer_label: user.customer_label ?? '',
      telegram_profile: user.telegram_profile ?? '',
      is_active: Boolean(user.is_active),
    })
    setSheetMode('edit')
  }

  function updateUserForm(event) {
    const { name, value, checked, type } = event.target
    setUserForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
  }

  async function submitUserForm(event) {
    event.preventDefault()

    if (!userForm.name || !userForm.email || !userForm.role_id) {
      await showError('Please fill in name, email, and role.')
      return
    }
    if (sheetMode === 'create' && (!userForm.password || !userForm.password_confirmation)) {
      await showError('Please enter and confirm the password.')
      return
    }
    if (userForm.password !== userForm.password_confirmation) {
      await showError('Passwords do not match.')
      return
    }

    try {
      setSavingUser(true)
      const payload = {
        ...userForm,
        role_id: Number(userForm.role_id),
        primary_department_id: userForm.primary_department_id || null,
        customer_label: selectedRoleIsCustomer ? userForm.customer_label || null : null,
        telegram_profile: selectedRoleIsCustomer ? userForm.telegram_profile || null : null,
      }
      if (sheetMode === 'edit' && !payload.password) {
        delete payload.password
        delete payload.password_confirmation
      }

      if (sheetMode === 'edit') {
        await adminService.updateUser(editingUser.id, payload)
        await showSuccess('User updated.')
      } else {
        await adminService.createUser(payload)
        await showSuccess('User created.')
      }
      setSheetMode(null)
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to save user.')
    } finally {
      setSavingUser(false)
    }
  }

  async function toggle(user) {
    const next = !user.is_active
    const result = await confirmAction(`${next ? 'Activate' : 'Disable'} ${user.email}?`)
    if (!result.isConfirmed) return
    try {
      await adminService.changeUserStatus(user.id, next)
      await showSuccess('User status updated.')
      await load()
    } catch (error) {
      await showError(error.message || 'Unable to update user status.')
    }
  }

  async function changeRole(user, roleId) {
    const result = await confirmAction(`Change role for ${user.email}?`)
    if (!result.isConfirmed) return
    try {
      await adminService.changeUserRole(user.id, Number(roleId))
      await showSuccess('Role updated.')
      await load()
    } catch (error) {
      await showError(error.message || 'Unable to update role.')
    }
  }

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
      <PageHeader
        actions={
          <Button onClick={openCreateUser}>
            <UserPlus aria-hidden="true" className="h-4 w-4" />
            Add User
          </Button>
        }
        breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Users' }]}
        eyebrow="Administration"
        title="Users"
      />

      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <MetricCard animated icon={UsersIcon} label="Total Users" tone="indigo" topAccent value={summary ? summary.total_users : '-'} />
        <MetricCard animated icon={UserCheck} label="Active" tone="emerald" topAccent value={summary ? summary.active_users : '-'} />
        <MetricCard icon={UserX} label="Disabled" tone="slate" value={summary ? summary.disabled_users : '-'} />
        <MetricCard animated icon={ShieldCheck} label="Administrators" tone="blue" topAccent value={summary ? administrators : '-'} />
        <MetricCard animated icon={UsersIcon} label="Agents" tone="violet" topAccent value={summary ? summary.total_agents : '-'} />
        <MetricCard animated icon={UsersIcon} label="Customers" tone="blue" topAccent value={summary ? summary.total_customers : '-'} />
      </div>

      <form className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center" onSubmit={submitFilters}>
        <SearchInput onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))} placeholder="Search users" value={filters.search} />
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:shrink-0">
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, role_id: event.target.value }))} value={filters.role_id}>
            <option value="">All roles</option>
            {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
          </Select>
          <Select className="lg:w-40" onChange={(event) => setFilters((current) => ({ ...current, is_active: event.target.value }))} value={filters.is_active}>
            <option value="">Any status</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
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
              <Th>Email</Th>
              <Th>Role</Th>
              <Th>Customer Label</Th>
              <Th>Department</Th>
              <Th>Status</Th>
              <Th>Last Login</Th>
              <Th>Actions</Th>
            </tr>
          </Thead>
          <Tbody>
            {users.map((user) => (
              <Tr key={user.id}>
                <Td primary>{user.name}</Td>
                <Td>{user.email}</Td>
                <Td>{user.role?.name}</Td>
                <Td>
                  {user.role?.slug === 'customer' && user.customer_label ? (
                    <Badge tone={labelMeta(user.customer_label)?.tone ?? 'slate'}>
                      {labelMeta(user.customer_label)?.label ?? user.customer_label.replaceAll('_', ' ')}
                    </Badge>
                  ) : '-'}
                </Td>
                <Td>{user.primary_department?.name ?? '-'}</Td>
                <Td><ActiveBadge active={user.is_active} /></Td>
                <Td>{user.last_login_at ?? '-'}</Td>
                <Td>
                  <div className="flex flex-wrap items-center gap-2">
                    <Select className="w-36" onChange={(event) => changeRole(user, event.target.value)} value={user.role_id}>
                      {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
                    </Select>
                    <Button onClick={() => openEditUser(user)} size="sm" variant="secondary">
                      <Pencil aria-hidden="true" className="h-4 w-4" />
                      Edit
                    </Button>
                    <Button onClick={() => toggle(user)} size="sm" variant="secondary">
                      {user.is_active ? 'Disable' : 'Activate'}
                    </Button>
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <Pagination onPageChange={goToPage} pagination={pagination} />

      <Sheet
        description={sheetMode === 'edit' ? 'Update profile, role, department, and account status.' : 'Create a user without leaving the table context.'}
        onOpenChange={(open) => {
          if (!open) setSheetMode(null)
        }}
        open={Boolean(sheetMode)}
        title={sheetMode === 'edit' ? 'Edit User' : 'Create User'}
      >
        <form className="space-y-4" onSubmit={submitUserForm}>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field htmlFor="user-name" label="Name"><Input id="user-name" name="name" onChange={updateUserForm} value={userForm.name} /></Field>
            <Field htmlFor="user-email" label="Email"><Input id="user-email" name="email" onChange={updateUserForm} type="email" value={userForm.email} /></Field>
            <Field htmlFor="user-password" label={sheetMode === 'edit' ? 'New password' : 'Password'}><Input autoComplete="new-password" id="user-password" name="password" onChange={updateUserForm} type="password" value={userForm.password} /></Field>
            <Field htmlFor="user-password-confirmation" label="Confirm password"><Input autoComplete="new-password" id="user-password-confirmation" name="password_confirmation" onChange={updateUserForm} type="password" value={userForm.password_confirmation} /></Field>
            <Field htmlFor="user-role" label="Role">
              <Select id="user-role" name="role_id" onChange={updateUserForm} value={userForm.role_id}>
                <option value="">Select role</option>
                {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
              </Select>
            </Field>
            <Field htmlFor="user-department" label="Primary department">
              <Select id="user-department" name="primary_department_id" onChange={updateUserForm} value={userForm.primary_department_id}>
                <option value="">None</option>
                {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
              </Select>
            </Field>
            <Field htmlFor="user-phone" label="Phone"><Input id="user-phone" name="phone" onChange={updateUserForm} value={userForm.phone} /></Field>
            <Field htmlFor="user-company" label="Company"><Input id="user-company" name="company" onChange={updateUserForm} value={userForm.company} /></Field>
            {selectedRoleIsCustomer ? (
              <>
                <Field htmlFor="user-customer-label" label="Customer label">
                  <Select id="user-customer-label" name="customer_label" onChange={updateUserForm} value={userForm.customer_label}>
                    <option value="">No label</option>
                    {customerLabels.map((label) => <option key={label.value} value={label.value}>{label.label}</option>)}
                  </Select>
                </Field>
                <Field htmlFor="user-telegram-profile" label="Telegram Chat ID">
                  <Input id="user-telegram-profile" name="telegram_profile" onChange={updateUserForm} placeholder="Example: 123456789" value={userForm.telegram_profile} />
                </Field>
              </>
            ) : null}
          </div>
          <Checkbox checked={userForm.is_active} id="user-active" label="Active" name="is_active" onChange={updateUserForm} />
          <div className="flex justify-end gap-2 pt-2">
            <Button onClick={() => setSheetMode(null)} type="button" variant="secondary">Cancel</Button>
            <Button loading={savingUser} type="submit">{sheetMode === 'edit' ? 'Save User' : 'Create User'}</Button>
          </div>
        </form>
      </Sheet>
    </PageContainer>
  )
}
