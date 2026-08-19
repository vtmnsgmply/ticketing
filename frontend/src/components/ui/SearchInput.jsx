import { forwardRef } from 'react'
import { Search } from 'lucide-react'
import { clsx } from 'clsx'

const SearchInput = forwardRef(function SearchInput({ className, ...props }, ref) {
  return (
    <div className={clsx('relative min-w-0 flex-1', className)}>
      <Search aria-hidden="true" className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
      <input
        className="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
        ref={ref}
        type="search"
        {...props}
      />
    </div>
  )
})

export default SearchInput
