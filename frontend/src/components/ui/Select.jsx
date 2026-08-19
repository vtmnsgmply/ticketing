import { forwardRef } from 'react'
import { ChevronDown } from 'lucide-react'
import { clsx } from 'clsx'

const Select = forwardRef(function Select({ className, invalid = false, children, ...props }, ref) {
  return (
    <div className="relative">
      <select
        className={clsx(
          'block w-full appearance-none rounded-lg border bg-white px-3 py-2.5 pr-9 text-sm text-slate-900 shadow-sm outline-none transition',
          'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
          invalid
            ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20',
          className,
        )}
        ref={ref}
        {...props}
      >
        {children}
      </select>
      <ChevronDown aria-hidden="true" className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
    </div>
  )
})

export default Select
