import { SlidersHorizontal } from 'lucide-react'
import Button from '../ui/Button'
import SearchInput from '../ui/SearchInput'
import Select from '../ui/Select'

export default function TicketFilters({ filters, options, onChange, onSubmit }) {
  return (
    <form className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center" onSubmit={onSubmit}>
      <SearchInput
        aria-label="Search tickets"
        name="search"
        onChange={onChange}
        placeholder="Search ticket number or subject"
        value={filters.search}
      />
      <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:shrink-0">
        <Select aria-label="Status" className="lg:w-44" name="status" onChange={onChange} value={filters.status}>
          <option value="">All statuses</option>
          {options.statuses.map((status) => (
            <option key={status} value={status}>
              {status.replaceAll('_', ' ')}
            </option>
          ))}
        </Select>
        <Select aria-label="Priority" className="lg:w-40" name="priority" onChange={onChange} value={filters.priority}>
          <option value="">All priorities</option>
          {options.priorities.map((priority) => (
            <option key={priority.id} value={priority.id}>
              {priority.name}
            </option>
          ))}
        </Select>
        <Button className="justify-center" type="submit" variant="secondary">
          <SlidersHorizontal aria-hidden="true" className="h-4 w-4" />
          Filter
        </Button>
      </div>
    </form>
  )
}
