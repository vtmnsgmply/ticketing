import { Link } from 'react-router-dom'
import { BellRing, CheckCircle2 } from 'lucide-react'
import { clsx } from 'clsx'
import { Button, Card } from '../ui'

function typeTone(type = '') {
  if (type.includes('cancel') || type.includes('closed')) return 'rose'
  if (type.includes('resolved')) return 'emerald'
  if (type.includes('assigned') || type.includes('reply')) return 'indigo'
  if (type.includes('priority') || type.includes('sla')) return 'amber'
  return 'blue'
}

const tones = {
  amber: {
    accent: 'bg-amber-500',
    badge: 'bg-amber-100 text-amber-800 ring-amber-200',
    icon: 'bg-amber-100 text-amber-700',
  },
  blue: {
    accent: 'bg-blue-500',
    badge: 'bg-blue-100 text-blue-800 ring-blue-200',
    icon: 'bg-blue-100 text-blue-700',
  },
  emerald: {
    accent: 'bg-emerald-500',
    badge: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    icon: 'bg-emerald-100 text-emerald-700',
  },
  indigo: {
    accent: 'bg-indigo-500',
    badge: 'bg-indigo-100 text-indigo-800 ring-indigo-200',
    icon: 'bg-indigo-100 text-indigo-700',
  },
  rose: {
    accent: 'bg-rose-500',
    badge: 'bg-rose-100 text-rose-800 ring-rose-200',
    icon: 'bg-rose-100 text-rose-700',
  },
  slate: {
    accent: 'bg-slate-300',
    badge: 'bg-slate-100 text-slate-600 ring-slate-200',
    icon: 'bg-slate-100 text-slate-500',
  },
}

export default function NotificationItem({ item, ticketPath, onRead }) {
  const tone = item.is_read ? tones.slate : tones[typeTone(item.type)]

  return (
    <Card className={clsx('relative overflow-hidden p-0 transition-shadow', !item.is_read && 'shadow-[var(--shadow-md)]')}>
      <span className={clsx('absolute inset-y-0 left-0 w-1.5', tone.accent)} />
      <div className={clsx('flex flex-col gap-4 p-4 pl-5 sm:flex-row sm:items-start sm:justify-between', !item.is_read ? 'bg-white' : 'bg-slate-50/70')}>
        <div className="flex min-w-0 gap-3">
          <span className={clsx('mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', tone.icon)}>
            {item.is_read ? <CheckCircle2 aria-hidden="true" className="h-5 w-5" /> : <BellRing aria-hidden="true" className="h-5 w-5" />}
          </span>
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <p className={clsx('font-semibold', item.is_read ? 'text-slate-700' : 'text-slate-950')}>{item.title}</p>
              <span className={clsx('rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1', tone.badge)}>
                {item.is_read ? 'Read' : 'Unread'}
              </span>
            </div>
            <p className={clsx('mt-1 text-sm leading-6', item.is_read ? 'text-slate-500' : 'text-slate-700')}>{item.message}</p>
            <p className="mt-2 text-xs font-medium text-slate-500">{new Date(item.created_at).toLocaleString()}</p>
          </div>
        </div>
        <div className="flex shrink-0 flex-wrap gap-2 sm:justify-end">
          {item.ticket_id ? <Button as={Link} to={ticketPath} variant={item.is_read ? 'secondary' : 'primary'}>Open to view</Button> : null}
          {!item.is_read ? <Button onClick={() => onRead(item.id)} variant="secondary">Mark Read</Button> : null}
        </div>
      </div>
    </Card>
  )
}
