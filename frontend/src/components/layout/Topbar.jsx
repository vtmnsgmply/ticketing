import { LogOut, Menu } from 'lucide-react'
import Avatar from '../ui/Avatar'
import IconButton from '../ui/IconButton'
import NotificationBell from './NotificationBell'

const roleLabels = {
  customer: 'Customer',
  agent: 'Staff / Agent',
  manager: 'Manager',
  administrator: 'Administrator',
}

export default function Topbar({ user, onOpenMobileMenu, onLogout }) {
  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur-sm sm:px-6 lg:px-8">
      <div className="flex items-center gap-2">
        <IconButton aria-label="Open navigation" className="lg:hidden" onClick={onOpenMobileMenu}>
          <Menu aria-hidden="true" className="h-5 w-5" />
        </IconButton>
      </div>

      <div className="flex items-center gap-2 sm:gap-3">
        <NotificationBell />
        <div className="mx-1 hidden h-8 w-px bg-slate-200 sm:block" />
        <div className="hidden items-center gap-3 sm:flex">
          <Avatar name={user?.name} size="sm" />
          <div className="leading-tight">
            <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
            <p className="text-xs text-slate-500">{roleLabels[user?.role?.slug] ?? user?.role?.name}</p>
          </div>
        </div>
        <IconButton aria-label="Log out" onClick={onLogout} title="Log out">
          <LogOut aria-hidden="true" className="h-5 w-5" />
        </IconButton>
      </div>
    </header>
  )
}
