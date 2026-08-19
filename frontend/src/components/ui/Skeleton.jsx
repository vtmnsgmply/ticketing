import { clsx } from 'clsx'

export default function Skeleton({ className }) {
  return <div className={clsx('animate-pulse rounded-lg bg-slate-200 motion-reduce:animate-none', className)} />
}

export function TableSkeleton({ rows = 5, columns = 5 }) {
  return (
    <div className="space-y-3 p-4">
      {Array.from({ length: rows }).map((_, rowIndex) => (
        // eslint-disable-next-line react/no-array-index-key
        <div className="flex gap-4" key={rowIndex}>
          {Array.from({ length: columns }).map((__, columnIndex) => (
            // eslint-disable-next-line react/no-array-index-key
            <Skeleton className="h-4 flex-1" key={columnIndex} />
          ))}
        </div>
      ))}
    </div>
  )
}

// Matches the shape of a MetricCard so dashboard loading states don't jump
// once real data arrives.
export function SkeletonCard() {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-(--shadow-xs)">
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0 flex-1 space-y-3">
          <Skeleton className="h-3.5 w-24" />
          <Skeleton className="h-8 w-16" />
          <Skeleton className="h-3 w-32" />
        </div>
        <Skeleton className="h-10 w-10 shrink-0 rounded-lg" />
      </div>
    </div>
  )
}

// Matches a LiveActivityPanel row.
export function ActivitySkeleton({ rows = 4 }) {
  return (
    <div className="space-y-4 p-5">
      {Array.from({ length: rows }).map((_, index) => (
        // eslint-disable-next-line react/no-array-index-key
        <div className="flex items-start gap-3" key={index}>
          <Skeleton className="mt-1 h-2 w-2 shrink-0 rounded-full" />
          <div className="flex-1 space-y-2">
            <Skeleton className="h-3.5 w-3/4" />
            <Skeleton className="h-3 w-1/3" />
          </div>
        </div>
      ))}
    </div>
  )
}
