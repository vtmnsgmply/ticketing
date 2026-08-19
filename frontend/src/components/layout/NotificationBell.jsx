import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { Bell, BellOff, BellRing, CheckCircle2 } from 'lucide-react'
import { clsx } from 'clsx'
import { useAuth } from '../../hooks/useAuth'
import { useNotifications } from '../../hooks/useNotifications'
import IconButton from '../ui/IconButton'

function ticketPath(role, ticketId) {
  if (!ticketId) return null
  if (role === 'agent') return `/staff/tickets/${ticketId}`
  if (role === 'manager') return `/manager/tickets/${ticketId}`
  if (role === 'customer') return `/customer/tickets/${ticketId}`
  return `/tickets/${ticketId}`
}

export default function NotificationBell() {
  const { user } = useAuth()
  const { items, unreadCount, highlightedIds, acknowledgeVisible } = useNotifications()
  const [open, setOpen] = useState(false)
  const containerRef = useRef(null)

  useEffect(() => {
    function handleClickAway(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickAway)
    return () => document.removeEventListener('mousedown', handleClickAway)
  }, [])

  async function toggleOpen() {
    setOpen((value) => !value)
    if (!open && unreadCount > 0) {
      await acknowledgeVisible()
    }
  }

  return (
    <div className="relative" ref={containerRef}>
      <IconButton aria-label="Notifications" onClick={toggleOpen}>
        <Bell aria-hidden="true" className="h-5 w-5" />
        {unreadCount > 0 ? (
          <span className="absolute -right-0.5 -top-0.5 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white">
            {unreadCount}
          </span>
        ) : null}
      </IconButton>

      {open ? (
        <div className="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl sm:w-96">
          <div className="border-b border-slate-100 px-4 py-3">
            <p className="text-sm font-semibold text-slate-900">Notifications</p>
          </div>
          {items.length ? (
            <div className="max-h-96 overflow-y-auto">
              {items.map((item) => (
                <div
                  className={clsx(
                    'relative border-b border-slate-100 px-4 py-3 pl-5 last:border-b-0',
                    !item.is_read || highlightedIds.includes(item.id) ? 'bg-indigo-50/80' : 'bg-white',
                  )}
                  key={item.id}
                >
                  <span className={clsx('absolute inset-y-0 left-0 w-1', !item.is_read || highlightedIds.includes(item.id) ? 'bg-indigo-500' : 'bg-slate-200')} />
                  <div className="flex items-start gap-3">
                    <span className={clsx('mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg', !item.is_read || highlightedIds.includes(item.id) ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500')}>
                      {!item.is_read || highlightedIds.includes(item.id) ? <BellRing aria-hidden="true" className="h-4 w-4" /> : <CheckCircle2 aria-hidden="true" className="h-4 w-4" />}
                    </span>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-start justify-between gap-3">
                        <p className="text-sm font-semibold text-slate-900">{item.title}</p>
                        <span className={clsx('mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1', !item.is_read || highlightedIds.includes(item.id) ? 'bg-indigo-100 text-indigo-700 ring-indigo-200' : 'bg-slate-100 text-slate-500 ring-slate-200')}>
                          {!item.is_read || highlightedIds.includes(item.id) ? 'New' : 'Read'}
                        </span>
                      </div>
                      <p className="mt-1 text-xs leading-5 text-slate-600">{item.message}</p>
                      <div className="mt-2 flex gap-2">
                        {item.ticket_id ? <Link className="text-xs font-semibold text-indigo-600" onClick={() => setOpen(false)} to={ticketPath(user?.role?.slug, item.ticket_id)}>Open to view</Link> : null}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
              <Link className="block px-4 py-3 text-center text-sm font-semibold text-indigo-600" onClick={() => setOpen(false)} to={user?.role?.slug === 'agent' ? '/staff/notifications' : user?.role?.slug === 'manager' ? '/manager/notifications' : user?.role?.slug === 'customer' ? '/customer/notifications' : '/admin/audit-logs'}>View all</Link>
            </div>
          ) : (
            <div className="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
              <BellOff aria-hidden="true" className="h-6 w-6 text-slate-400" />
              <p className="text-sm text-slate-500">You&apos;re all caught up.</p>
            </div>
          )}
        </div>
      ) : null}
    </div>
  )
}
