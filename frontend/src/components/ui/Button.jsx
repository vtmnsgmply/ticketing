import { forwardRef } from 'react'
import { Loader2 } from 'lucide-react'
import { clsx } from 'clsx'

const base =
  'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-[var(--shadow-xs)] ' +
  'transition-colors duration-150 ease-standard ' +
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 ' +
  'motion-reduce:transition-none'

const variants = {
  primary: 'bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 focus-visible:ring-indigo-500/30',
  secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-slate-300',
  danger: 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-500/30',
  ghost: 'rounded-lg px-3 py-2 text-slate-600 shadow-none hover:bg-slate-100 hover:text-slate-900 active:bg-slate-200 focus-visible:ring-slate-300',
}

const sizes = {
  md: '',
  sm: 'px-3 py-2 text-sm',
}

const Button = forwardRef(function Button(
  { as: Component = 'button', variant = 'primary', size = 'md', loading = false, disabled = false, className, children, type = 'button', ...props },
  ref,
) {
  const isButtonEl = Component === 'button'

  return (
    <Component
      className={clsx(base, variants[variant], size === 'sm' && sizes.sm, (disabled || loading) && 'pointer-events-none opacity-50', className)}
      disabled={isButtonEl ? disabled || loading : undefined}
      ref={ref}
      type={isButtonEl ? type : undefined}
      {...props}
    >
      {loading ? <Loader2 aria-hidden="true" className="h-4 w-4 animate-spin motion-reduce:animate-none" /> : null}
      {children}
    </Component>
  )
})

export default Button
