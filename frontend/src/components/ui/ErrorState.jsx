import { AlertTriangle } from 'lucide-react'

export default function ErrorState({ title = 'Something went wrong', description }) {
  return (
    <div className="rounded-xl border border-red-200 bg-red-50 p-4">
      <div className="flex items-start gap-3">
        <AlertTriangle aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
        <div>
          <p className="font-semibold text-red-800">{title}</p>
          {description ? <p className="mt-1 text-sm text-red-700">{description}</p> : null}
        </div>
      </div>
    </div>
  )
}
