import { forwardRef } from 'react'
import { clsx } from 'clsx'

const Textarea = forwardRef(function Textarea({ className, invalid = false, ...props }, ref) {
  return (
    <textarea
      className={clsx(
        'block min-h-28 w-full resize-y rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition',
        'placeholder:text-slate-400 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
        invalid
          ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20'
          : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20',
        className,
      )}
      ref={ref}
      {...props}
    />
  )
})

export default Textarea
