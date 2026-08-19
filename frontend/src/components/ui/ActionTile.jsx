import { Link } from 'react-router-dom'
import { clsx } from 'clsx'

const variants = {
  primary: 'border-indigo-600 bg-indigo-600 text-white hover:bg-indigo-700',
  accent: 'border-amber-400 bg-amber-400 text-slate-950 hover:bg-amber-500',
  outline: 'border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300',
  critical: 'border-red-600 bg-red-600 text-white hover:bg-red-700',
  neutral: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300',
}

export default function ActionTile({ icon: Icon, label, to, onClick, variant = 'outline', className }) {
  const Component = to ? Link : 'button'

  return (
    <Component
      className={clsx(
        'group flex min-h-24 flex-col items-center justify-center gap-2 rounded-xl border p-4 text-center text-sm font-semibold',
        'transition-all duration-200 ease-standard hover:-translate-y-0.5 hover:shadow-(--shadow-sm)',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/30 focus-visible:ring-offset-2',
        variants[variant],
        className,
      )}
      onClick={onClick}
      to={to}
      type={to ? undefined : 'button'}
    >
      {Icon ? <Icon aria-hidden="true" className="h-5 w-5" strokeWidth={1.75} /> : null}
      <span className="leading-tight">{label}</span>
    </Component>
  )
}
