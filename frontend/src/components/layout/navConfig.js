import {
  Building2,
  History,
  LayoutDashboard,
  Mail,
  PlusCircle,
  Settings,
  SlidersHorizontal,
  Tags,
  Ticket,
  Timer,
  Users,
  BarChart3,
  ShieldAlert,
} from 'lucide-react'

export function getNavSections(role) {
  const dashboardPath =
    role === 'customer' ? '/customer/dashboard'
    : role === 'agent' ? '/staff/dashboard'
    : role === 'manager' ? '/manager/dashboard'
    : role === 'administrator' ? '/admin/dashboard'
    : '/dashboard'
  const ticketsPath = role === 'customer' ? '/customer/tickets' : role === 'agent' ? '/staff/tickets' : role === 'manager' ? '/manager/tickets' : '/tickets'
  const notificationsPath = role === 'customer' ? '/customer/notifications' : role === 'agent' ? '/staff/notifications' : role === 'manager' ? '/manager/notifications' : null

  const overview = {
    title: 'Overview',
    items: [{ to: dashboardPath, label: 'Dashboard', icon: LayoutDashboard, end: true }],
  }

  const work = {
    title: 'Work',
    items: [
      { to: ticketsPath, label: role === 'customer' ? 'My Tickets' : 'Tickets', icon: Ticket },
      ...(role === 'customer' ? [{ to: '/customer/tickets/create', label: 'Create Ticket', icon: PlusCircle }] : []),
      ...(notificationsPath ? [{ to: notificationsPath, label: 'Notifications', icon: Mail }] : []),
    ],
  }

  const management = role === 'manager'
    ? [
        {
          title: 'Management',
          items: [
            { to: '/manager/team', label: 'Team', icon: Users },
            { to: '/manager/reports', label: 'Reports', icon: BarChart3 },
            { to: '/manager/moderation/events', label: 'Moderation Events', icon: ShieldAlert },
          ],
        },
      ]
    : []

  const account = {
    title: 'Account',
    items: [
      { to: '/notification-preferences', label: 'Preferences', icon: Settings },
      { to: role === 'customer' ? '/customer/profile' : role === 'agent' ? '/staff/profile' : '/manager/profile', label: 'Profile', icon: Users },
    ],
  }

  if (role !== 'administrator') {
    return [overview, work, ...management, account]
  }

  return [
    overview,
    work,
    {
      title: 'Administration',
      items: [
        { to: '/admin/users', label: 'Users', icon: Users },
        { to: '/admin/departments', label: 'Departments', icon: Building2 },
        { to: '/admin/categories', label: 'Categories', icon: Tags },
        { to: '/admin/priorities', label: 'Priorities', icon: SlidersHorizontal },
        { to: '/admin/sla', label: 'SLA', icon: Timer },
        { to: '/admin/reports', label: 'Reports', icon: BarChart3 },
        { to: '/admin/moderation', label: 'Moderation Rules', icon: ShieldAlert },
        { to: '/admin/moderation/events', label: 'Moderation Events', icon: ShieldAlert },
        { to: '/admin/audit-logs', label: 'Audit Logs', icon: History },
      ],
    },
    {
      title: 'Settings',
      items: [
        { to: '/admin/settings/system', label: 'System', icon: Settings },
        { to: '/admin/settings/email', label: 'Email', icon: Mail },
      ],
    },
  ]
}
