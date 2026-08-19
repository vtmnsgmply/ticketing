import { clsx } from 'clsx'

// Elevation follows the layered shadow tokens (contact + ambient), not raw
// Tailwind shadow-sm/md — level 1 for resting/static surfaces, level 2 for
// interactive or primary surfaces (KPI cards, clickable list items).
const levels = {
  1: 'shadow-[var(--shadow-xs)]',
  2: 'shadow-[var(--shadow-sm)]',
  3: 'shadow-[var(--shadow-md)]',
}

export default function Card({ as: Component = 'div', className, padded = true, level = 1, tone = 'default', children, ...props }) {
  const toneClasses = {
    default: 'border-slate-200 bg-white',
    danger: 'border-red-200 bg-red-50/30',
    warning: 'border-amber-200 bg-amber-50/30',
  }

  return (
    <Component
      className={clsx('rounded-xl border', toneClasses[tone], levels[level], padded && 'p-5 sm:p-6', className)}
      {...props}
    >
      {children}
    </Component>
  )
}
