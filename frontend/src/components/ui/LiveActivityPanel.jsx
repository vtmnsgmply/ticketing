import { clsx } from 'clsx'
import { AnimatePresence, motion, useReducedMotion } from 'motion/react'
import Card from './Card'
import EmptyState from './EmptyState'
import { ActivitySkeleton } from './Skeleton'
import { normalizeTone } from '../../utils/tone'

// Same 5-hue semantic vocabulary used by Badge/MetricCard — no extra hues.
const dotTone = {
  emerald: 'bg-emerald-500',
  blue: 'bg-blue-500',
  indigo: 'bg-indigo-500',
  violet: 'bg-violet-500',
  amber: 'bg-amber-500',
  orange: 'bg-orange-500',
  red: 'bg-red-500',
  slate: 'bg-slate-400',
}

export default function LiveActivityPanel({ title = 'Live Activity', items, loading = false, emptyLabel = 'No recent activity yet.', action, className, limit = 8 }) {
  const reducedMotion = useReducedMotion()
  const visibleItems = items?.slice(0, limit)

  return (
    <Card className={className} padded={false}>
      <div className="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div className="flex items-center gap-2">
          <p className="text-sm font-semibold text-slate-900">{title}</p>
          <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
            <span aria-hidden="true" className="h-2 w-2 rounded-full bg-emerald-500 motion-safe:animate-pulse motion-reduce:animate-none" />
            Live
          </span>
        </div>
        {action ? <div className="shrink-0 text-xs font-semibold text-indigo-600">{action}</div> : null}
      </div>

      {loading ? (
        <ActivitySkeleton />
      ) : visibleItems?.length ? (
        <ul className="max-h-96 divide-y divide-slate-100 overflow-y-auto px-5">
          <AnimatePresence initial={false}>
            {visibleItems.map((item) => (
              <motion.li
                animate={{ opacity: 1, y: 0 }}
                className="flex gap-3 border-b border-slate-100 py-3 last:border-0"
                exit={{ opacity: reducedMotion ? 1 : 0, y: reducedMotion ? 0 : -4 }}
                initial={{ opacity: reducedMotion ? 1 : 0, y: reducedMotion ? 0 : 6 }}
                key={item.id}
                transition={{ duration: reducedMotion ? 0 : 0.18, ease: 'easeOut' }}
              >
                <span className={clsx('mt-1.5 h-2 w-2 shrink-0 rounded-full', dotTone[normalizeTone(item.tone)])} />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-slate-900">{item.label}</p>
                  {item.meta ? <p className="mt-0.5 truncate text-xs text-slate-500">{item.meta}</p> : null}
                </div>
                {item.timestamp ? <time className="shrink-0 text-xs text-slate-400">{item.timestamp}</time> : null}
              </motion.li>
            ))}
          </AnimatePresence>
        </ul>
      ) : (
        <div className="p-5">
          <EmptyState description={emptyLabel} title="All quiet" />
        </div>
      )}
    </Card>
  )
}
