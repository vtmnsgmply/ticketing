import { Check, RotateCcw, Ticket } from 'lucide-react'
import { clsx } from 'clsx'
import { Progress, ProgressIndicator } from '../animate-ui/progress'
import AnimatedMetric from './AnimatedMetric'
import Button from './Button'

function progressTone(progress, error) {
  if (error) return 'bg-rose-500'
  if (progress >= 75) return 'bg-emerald-500'
  if (progress >= 50) return 'bg-indigo-500'
  if (progress >= 25) return 'bg-blue-500'
  return 'bg-slate-300'
}

function stepTone(status) {
  if (status === 'failed') return 'border-rose-200 bg-rose-50 text-rose-700'
  if (status === 'complete') return 'border-emerald-200 bg-emerald-50 text-emerald-700'
  if (status === 'active') return 'border-blue-200 bg-blue-50 text-blue-700'
  return 'border-slate-200 bg-white text-slate-400'
}

export default function TicketLoadingProgress({
  progress,
  status,
  tasks = [],
  error = false,
  compact = false,
  onRetry,
  onSignInAgain,
}) {
  const normalized = Math.min(Math.max(progress, 0), 100)
  const fillTone = progressTone(normalized, error)

  return (
    <div className={clsx('flex flex-col items-center justify-center text-center', compact ? 'py-8' : 'min-h-screen px-4 py-12')}>
      <div className="w-full max-w-sm">
        <Progress
          aria-label="Loading workspace"
          className="relative mx-auto h-24 w-40 overflow-hidden rounded-xl border-2 border-slate-300 bg-white shadow-[var(--shadow-md)] sm:h-28 sm:w-48"
          value={normalized}
        >
          <div className="absolute -left-3 top-1/2 h-7 w-7 -translate-y-1/2 rounded-full border-2 border-slate-300 bg-slate-50" />
          <div className="absolute -right-3 top-1/2 h-7 w-7 -translate-y-1/2 rounded-full border-2 border-slate-300 bg-slate-50" />
          <ProgressIndicator
            className={clsx('absolute inset-y-0 left-0 motion-reduce:transition-none', fillTone)}
            transition={{ type: 'spring', stiffness: 100, damping: 30 }}
            value={normalized}
          />
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-1">
            <Ticket aria-hidden="true" className={clsx('h-7 w-7', normalized >= 50 && !error ? 'text-white' : 'text-slate-700')} strokeWidth={1.8} />
            <span className={clsx('text-xs font-bold uppercase tracking-normal', normalized >= 50 && !error ? 'text-white' : 'text-slate-700')}>Ticket</span>
          </div>
        </Progress>

        <p className="mt-5 text-3xl font-bold tracking-tight text-slate-950"><AnimatedMetric animated value={`${normalized}%`} /></p>
        <p className="mt-2 text-sm font-medium text-slate-500">{status}</p>
      </div>

      {tasks.length ? (
        <div className="mt-5 hidden w-full max-w-md grid-cols-3 gap-2 sm:grid">
          {tasks.map((task) => (
            <div className={clsx('flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold', stepTone(task.status))} key={task.id}>
              {task.status === 'complete' ? <Check aria-hidden="true" className="h-3.5 w-3.5" /> : null}
              <span className="truncate">{task.label}</span>
            </div>
          ))}
        </div>
      ) : null}

      {error ? (
        <div className="mt-5 flex flex-wrap justify-center gap-3">
          {onRetry ? <Button onClick={onRetry} variant="secondary"><RotateCcw aria-hidden="true" className="h-4 w-4" />Retry</Button> : null}
          {onSignInAgain ? <Button onClick={onSignInAgain}>Sign In Again</Button> : null}
        </div>
      ) : null}
    </div>
  )
}
