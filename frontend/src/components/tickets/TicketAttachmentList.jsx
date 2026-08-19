import { Paperclip } from 'lucide-react'
import { ProtectedMedia } from './TicketConversation'
import EmptyState from '../ui/EmptyState'

function formatFileSize(bytes) {
  const value = Number(bytes ?? 0)
  if (!Number.isFinite(value) || value <= 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = value
  let unitIndex = 0

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }

  const precision = size >= 10 || unitIndex === 0 ? 0 : 1
  return `${size.toFixed(precision)} ${units[unitIndex]}`
}

export default function TicketAttachmentList({ attachments }) {
  if (!attachments.length) {
    return <EmptyState description="Files added to this ticket will show up here." icon={Paperclip} title="No attachments" />
  }

  return (
    <ul className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-2 2xl:grid-cols-4">
      {attachments.map((attachment) => (
        <li key={attachment.id}>
          {attachment.is_image || attachment.is_video ? (
            <div className="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
              <ProtectedMedia attachment={attachment} compact />
              <span className="absolute bottom-1.5 right-1.5 rounded-full bg-slate-950/70 px-2 py-0.5 text-[10px] font-semibold text-white">
                {formatFileSize(attachment.file_size)}
              </span>
            </div>
          ) : (
            <span className="flex aspect-square flex-col items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100">
              <Paperclip aria-hidden="true" className="h-3.5 w-3.5 shrink-0 text-slate-400" />
              <span className="text-[10px] text-slate-400">{formatFileSize(attachment.file_size)}</span>
            </span>
          )}
        </li>
      ))}
    </ul>
  )
}
