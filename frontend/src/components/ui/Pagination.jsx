import { ChevronLeft, ChevronRight } from 'lucide-react'
import Button from './Button'

export default function Pagination({ pagination, onPageChange }) {
  if (!pagination) return null
  const currentPage = pagination.current_page ?? 1
  const lastPage = pagination.last_page ?? 1
  const canGoPrevious = currentPage > 1
  const canGoNext = currentPage < lastPage

  function goTo(page) {
    if (!onPageChange || page < 1 || page > lastPage || page === currentPage) return
    onPageChange(page)
  }

  return (
    <nav aria-label="Pagination" className="mt-4 flex flex-col gap-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
      <p className="text-xs text-slate-500">{pagination.total} total</p>
      <div className="flex items-center gap-2">
        <Button disabled={!canGoPrevious || !onPageChange} onClick={() => goTo(currentPage - 1)} size="sm" variant="secondary">
          <ChevronLeft aria-hidden="true" className="h-4 w-4" />
          Previous
        </Button>
        <span className="min-w-24 text-center text-xs font-semibold text-slate-500">
          Page {currentPage} of {lastPage}
        </span>
        <Button disabled={!canGoNext || !onPageChange} onClick={() => goTo(currentPage + 1)} size="sm" variant="secondary">
          Next
          <ChevronRight aria-hidden="true" className="h-4 w-4" />
        </Button>
      </div>
    </nav>
  )
}
