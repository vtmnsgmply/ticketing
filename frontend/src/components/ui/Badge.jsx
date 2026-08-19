import { Pause } from 'lucide-react'
import { clsx } from 'clsx'
import { normalizeTone } from '../../utils/tone'

const base = 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset'
const toneStyles = {
  slate: 'bg-slate-100 text-slate-600 ring-slate-200',
  blue: 'bg-blue-50 text-blue-700 ring-blue-200',
  indigo: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
  violet: 'bg-violet-50 text-violet-700 ring-violet-200',
  emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  amber: 'bg-amber-50 text-amber-700 ring-amber-200',
  orange: 'bg-orange-50 text-orange-700 ring-orange-200',
  red: 'bg-red-50 text-red-700 ring-red-200',
}

export function Badge({ className, children, tone = 'slate', ...props }) {
  return (
    <span className={clsx(base, toneStyles[normalizeTone(tone)], className)} {...props}>
      {children}
    </span>
  )
}

// Global semantic hue vocabulary: slate = neutral, blue = informational,
// amber = active attention, emerald = positive, red = critical. Every
// status/priority/SLA badge draws from exactly these five hues so a color
// means the same thing everywhere it appears in the product.
const statusStyles = {
  new: 'bg-blue-50 text-blue-700 ring-blue-200',
  open: 'bg-blue-50 text-blue-700 ring-blue-200',
  assigned: 'bg-slate-100 text-slate-600 ring-slate-200',
  in_progress: 'bg-amber-50 text-amber-700 ring-amber-200',
  waiting_for_customer: 'bg-slate-100 text-slate-600 ring-slate-200',
  resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  closed: 'bg-slate-100 text-slate-600 ring-slate-200',
  cancelled: 'bg-red-50 text-red-700 ring-red-200',
}

export function StatusBadge({ status, className }) {
  return (
    <span className={clsx(base, statusStyles[status] ?? statusStyles.closed, className)}>
      {status === 'waiting_for_customer' ? <Pause aria-hidden="true" className="h-3 w-3" /> : null}
      {status ? status.replaceAll('_', ' ') : 'Unknown'}
    </span>
  )
}

// Medium -> High is expressed as intensity within the same amber hue
// (weight, not a new color); only Critical breaks out into red.
const priorityStyles = {
  low: 'bg-slate-100 text-slate-600 ring-slate-200',
  medium: 'bg-amber-50 text-amber-700 ring-amber-200',
  high: 'bg-amber-100 text-amber-800 ring-amber-300',
  critical: 'bg-red-50 text-red-700 ring-red-200',
  urgent: 'bg-red-50 text-red-700 ring-red-200',
}

export function PriorityBadge({ priority, className }) {
  const slug = priority?.slug ?? 'none'
  return (
    <span className={clsx(base, priorityStyles[slug] ?? 'bg-slate-100 text-slate-600 ring-slate-200', className)}>
      {priority?.name ?? 'None'}
    </span>
  )
}

const slaStyles = {
  on_track: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  due_soon: 'bg-amber-50 text-amber-700 ring-amber-200',
  overdue: 'bg-red-50 text-red-700 ring-red-200',
  responded: 'bg-blue-50 text-blue-700 ring-blue-200',
  resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
}

const slaLabels = {
  on_track: 'On Track',
  due_soon: 'Due Soon',
  overdue: 'Overdue',
  responded: 'Responded',
  resolved: 'Resolved',
}

export function SLABadge({ state, className }) {
  return (
    <span className={clsx(base, 'normal-case', slaStyles[state] ?? slaStyles.on_track, className)}>
      {slaLabels[state] ?? state}
    </span>
  )
}

export function ActiveBadge({ active, className }) {
  return (
    <span
      className={clsx(
        base,
        active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200',
        className,
      )}
    >
      {active ? 'Active' : 'Disabled'}
    </span>
  )
}

export default Badge
