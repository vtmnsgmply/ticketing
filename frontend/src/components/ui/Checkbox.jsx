import { forwardRef } from 'react'
import { clsx } from 'clsx'

const Checkbox = forwardRef(function Checkbox({ className, label, id, ...props }, ref) {
  const input = (
    <input
      className={clsx(
        'h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 shadow-sm',
        'focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:ring-offset-0',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      id={id}
      ref={ref}
      type="checkbox"
      {...props}
    />
  )

  if (!label) return input

  return (
    <label className="inline-flex items-center gap-2 text-sm font-medium text-slate-700" htmlFor={id}>
      {input}
      {label}
    </label>
  )
})

export default Checkbox
