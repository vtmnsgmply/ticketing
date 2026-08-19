import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { BellRing, X } from 'lucide-react'
import { useAuth } from '../../hooks/useAuth'
import { useNotifications } from '../../hooks/useNotifications'

function ticketPath(role, ticketId) {
  if (!ticketId) return null
  if (role === 'agent') return `/staff/tickets/${ticketId}`
  if (role === 'manager') return `/manager/tickets/${ticketId}`
  if (role === 'customer') return `/customer/tickets/${ticketId}`
  return `/tickets/${ticketId}`
}

function NotificationToast({ item, onDismiss, role }) {
  const path = ticketPath(role, item.ticket_id)

  useEffect(() => {
    const timer = window.setTimeout(() => onDismiss(item.id), 7000)
    return () => window.clearTimeout(timer)
  }, [item.id, onDismiss])

  return (
    <div className="pointer-events-auto w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-2xl ring-1 ring-indigo-100">
      <div className="flex gap-3 border-l-4 border-indigo-500 p-4">
        <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
          <BellRing aria-hidden="true" className="h-5 w-5" />
        </span>
        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-3">
            <p className="text-sm font-bold text-slate-950">{item.title}</p>
            <button className="-mr-1 -mt-1 rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" onClick={() => onDismiss(item.id)} type="button">
              <X aria-hidden="true" className="h-4 w-4" />
              <span className="sr-only">Dismiss notification</span>
            </button>
          </div>
          <p className="mt-1 line-clamp-2 text-sm leading-5 text-slate-600">{item.message}</p>
          {path ? (
            <Link className="mt-3 inline-flex text-xs font-bold uppercase text-indigo-600 hover:text-indigo-800" onClick={() => onDismiss(item.id)} to={path}>
              Open to view
            </Link>
          ) : null}
        </div>
      </div>
    </div>
  )
}

export default function NotificationToastStack() {
  const { user } = useAuth()
  const { dismissToast, toastItems } = useNotifications()
  const role = user?.role?.slug

  if (!role || !toastItems.length) return null

  return (
    <div className="pointer-events-none fixed bottom-4 right-4 z-[1000] flex flex-col gap-3">
      {toastItems.map((item) => (
        <NotificationToast item={item} key={item.id} onDismiss={dismissToast} role={role} />
      ))}
    </div>
  )
}
