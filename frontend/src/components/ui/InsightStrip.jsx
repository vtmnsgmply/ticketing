import { clsx } from 'clsx'
import { normalizeTone } from '../../utils/tone'

const dotTone = {
  slate: 'bg-slate-400',
  blue: 'bg-blue-500',
  indigo: 'bg-indigo-500',
  violet: 'bg-violet-500',
  emerald: 'bg-emerald-500',
  amber: 'bg-amber-500',
  orange: 'bg-orange-500',
  red: 'bg-red-500',
}

// A single slim band of 2-4 real, current operational facts — never a card
// grid, never a placeholder. Omit the whole strip if there's nothing genuine
// to surface (see "Insight Strip" in the design system).
export default function InsightStrip({ items }) {
  if (!items?.length) return null

  return (
    <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) sm:flex-row sm:items-center sm:divide-x sm:divide-slate-100">
      {items.map((item) => (
        <div className="flex items-center gap-2 text-sm text-slate-700 first:sm:pl-0 sm:px-4" key={item.label}>
          <span className={clsx('h-1.5 w-1.5 shrink-0 rounded-full', dotTone[normalizeTone(item.tone)])} />
          {item.label}
        </div>
      ))}
    </div>
  )
}
