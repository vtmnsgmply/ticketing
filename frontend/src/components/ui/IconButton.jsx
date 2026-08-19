import { forwardRef } from 'react'
import { clsx } from 'clsx'
import Tooltip from './Tooltip'

const IconButton = forwardRef(function IconButton({ className, children, type = 'button', tooltip, title, 'aria-label': ariaLabel, ...props }, ref) {
  const button = (
    <button
      aria-label={ariaLabel}
      className={clsx(
        'inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition-colors duration-150',
        'hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20',
        'disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none',
        className,
      )}
      ref={ref}
      title={undefined}
      type={type}
      {...props}
    >
      {children}
    </button>
  )

  return <Tooltip content={tooltip ?? title ?? ariaLabel}>{button}</Tooltip>
})

export default IconButton
