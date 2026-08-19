import { Link } from 'react-router-dom'
import { PlusCircle, Ticket } from 'lucide-react'
import { useAuth } from '../hooks/useAuth'
import AppShell from '../components/layout/AppShell'
import Avatar from '../components/ui/Avatar'
import Button from '../components/ui/Button'
import Card from '../components/ui/Card'
import PageHeader from '../components/ui/PageHeader'
import { ActiveBadge } from '../components/ui/Badge'

const roleLabels = {
  customer: 'Customer',
  agent: 'Staff / Agent',
  manager: 'Manager',
  administrator: 'Administrator',
}

export default function Dashboard() {
  const { user } = useAuth()
  const role = user?.role?.slug ?? 'customer'

  if (!user) return null

  return (
    <AppShell width="standard">
      <PageHeader eyebrow={roleLabels[role] ?? 'User'} title={`Welcome back, ${user.name.split(' ')[0]}`} />

      <Card className="max-w-3xl">
        <div className="flex items-center gap-4">
          <Avatar name={user.name} size="lg" />
          <div className="min-w-0">
            <h2 className="truncate text-lg font-semibold text-slate-900">{user.name}</h2>
            <p className="truncate text-sm text-slate-500">{user.email}</p>
          </div>
        </div>

        <dl className="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3">
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt>
            <dd className="mt-1 text-sm font-medium text-slate-900">{roleLabels[role] ?? user.role?.name}</dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
            <dd className="mt-1">
              <ActiveBadge active={user.is_active} />
            </dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
            <dd className="mt-1 truncate text-sm font-medium text-slate-900">{user.email}</dd>
          </div>
        </dl>

        <div className="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
          <Button as={Link} to="/tickets">
            <Ticket aria-hidden="true" className="h-4 w-4" />
            Open tickets
          </Button>
          <Button as={Link} to="/tickets/create" variant="secondary">
            <PlusCircle aria-hidden="true" className="h-4 w-4" />
            Create ticket
          </Button>
        </div>
      </Card>
    </AppShell>
  )
}
