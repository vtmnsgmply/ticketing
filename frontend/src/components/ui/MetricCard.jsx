import { clsx } from 'clsx'
import AnimatedMetric from './AnimatedMetric'
import { normalizeTone } from '../../utils/tone'

const trendToneClasses = {
  emerald: 'text-emerald-700',
  amber: 'text-amber-700',
  red: 'text-red-700',
  slate: 'text-slate-500',
}

// Top accent stays within the existing 5-hue semantic vocabulary (slate is
// intentionally omitted — a neutral card carries no accent at all).
const accentClasses = {
  indigo: 'border-t-2 border-t-indigo-400',
  emerald: 'border-t-2 border-t-emerald-400',
  amber: 'border-t-2 border-t-amber-400',
  red: 'border-t-2 border-t-red-400',
  blue: 'border-t-2 border-t-blue-400',
  violet: 'border-t-2 border-t-violet-400',
  orange: 'border-t-2 border-t-orange-400',
}

export default function MetricCard({ label, value, hint, trend, icon: Icon, tone = 'slate', critical = false, topAccent = false, animated = false }) {
  const cardTone = normalizeTone(tone)
  // The one sanctioned gradient in the product: a two-stop tint strictly
  // bounded to this small icon capsule — never the card, header, or page.
  const toneClasses = {
    slate: 'bg-linear-to-br from-slate-100 to-slate-200 text-slate-600',
    indigo: 'bg-linear-to-br from-indigo-50 to-indigo-100 text-indigo-600',
    violet: 'bg-linear-to-br from-violet-50 to-violet-100 text-violet-600',
    emerald: 'bg-linear-to-br from-emerald-50 to-emerald-100 text-emerald-600',
    amber: 'bg-linear-to-br from-amber-50 to-amber-100 text-amber-600',
    orange: 'bg-linear-to-br from-orange-50 to-orange-100 text-orange-600',
    red: 'bg-linear-to-br from-red-50 to-red-100 text-red-600',
    blue: 'bg-linear-to-br from-blue-50 to-blue-100 text-blue-600',
  }

  return (
    <article
      className={clsx(
        'rounded-xl border p-5 shadow-(--shadow-xs) transition-all duration-200 ease-standard hover:-translate-y-0.5 hover:shadow-(--shadow-sm)',
        critical ? 'border-red-200 bg-red-50/30' : 'border-slate-200 bg-white',
        topAccent && cardTone !== 'slate' ? accentClasses[cardTone] : null,
      )}
    >
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-slate-500">{label}</p>
          <div className="mt-2 flex items-baseline gap-2">
            <p className="text-3xl font-bold tabular-nums tracking-tight text-slate-950"><AnimatedMetric animated={animated} value={value} /></p>
            {trend ? <span className={clsx('text-xs font-semibold', trendToneClasses[trend.tone ?? 'slate'])}>{trend.label}</span> : null}
          </div>
          {hint ? <p className="mt-1 text-xs font-medium text-slate-500">{hint}</p> : null}
        </div>
        {Icon ? (
          <div className={clsx('shrink-0 rounded-lg p-2.5', toneClasses[cardTone])}>
            <Icon aria-hidden="true" className="h-5 w-5" strokeWidth={1.75} />
          </div>
        ) : null}
      </div>
    </article>
  )
}
