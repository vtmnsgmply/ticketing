import { clsx } from 'clsx'

export function TableContainer({ children, className }) {
  return <div className={clsx('overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[var(--shadow-xs)]', className)}>{children}</div>
}

export function Table({ children, className }) {
  return (
    <div className="overflow-x-auto">
      <table className={clsx('min-w-full divide-y divide-slate-200', className)}>{children}</table>
    </div>
  )
}

export function Thead({ children }) {
  return <thead className="bg-slate-50">{children}</thead>
}

export function Th({ children, className }) {
  return (
    <th className={clsx('whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500', className)} scope="col">
      {children}
    </th>
  )
}

export function Tbody({ children }) {
  return <tbody className="divide-y divide-slate-100">{children}</tbody>
}

export function Tr({ children, className, sla, ...props }) {
  const slaAccent = {
    overdue: 'border-l-2 border-l-red-500',
    due_soon: 'border-l-2 border-l-amber-400',
  }

  return (
    <tr
      className={clsx(
        'transition-colors duration-100 ease-standard hover:bg-slate-50/80',
        sla && slaAccent[sla],
        className,
      )}
      {...props}
    >
      {children}
    </tr>
  )
}

const densityClasses = {
  comfortable: 'px-4 py-4',
  compact: 'px-4 py-3',
  dense: 'px-3 py-2.5',
}

export function Td({ children, className, primary = false, numeric = false, density = 'comfortable' }) {
  return (
    <td className={clsx(densityClasses[density] ?? densityClasses.comfortable, 'text-sm text-slate-600', primary && 'font-semibold text-slate-900', numeric && 'tabular-nums', className)}>
      {children}
    </td>
  )
}
