import { useEffect, useRef, useState } from 'react'
import { Camera, Image, Paperclip, Send, Smile, StickyNote, Ticket, Video, X } from 'lucide-react'
import { clsx } from 'clsx'
import { Progress, ProgressIndicator } from '../animate-ui/progress'
import Textarea from '../ui/Textarea'

const quickEmojis = [
  '\u{1F600}',
  '\u{1F603}',
  '\u{1F604}',
  '\u{1F602}',
  '\u{1F642}',
  '\u{1F609}',
  '\u{1F60A}',
  '\u{1F60D}',
  '\u{1F970}',
  '\u{1F618}',
  '\u{1F60B}',
  '\u{1F917}',
  '\u{1F914}',
  '\u{1F62E}',
  '\u{1F622}',
  '\u{1F62D}',
  '\u{1F621}',
  '\u{1F64F}',
  '\u{1F44D}',
  '\u2764\uFE0F',
  '\u{1F525}',
  '\u2705',
  '\u{1F389}',
  '\u{1F4AF}',
]

export default function TicketReplyForm({ allowAttachments = true, internal = false, onCancelReply, onSubmit, onTyping, replyingTo }) {
  const [message, setMessage] = useState('')
  const [attachments, setAttachments] = useState([])
  const [submitting, setSubmitting] = useState(false)
  const [uploadProgress, setUploadProgress] = useState(null)
  const [uploadComplete, setUploadComplete] = useState(false)
  const [emojiOpen, setEmojiOpen] = useState(false)
  const emojiRef = useRef(null)
  const uploadAbortRef = useRef(null)
  const attachmentIdRef = useRef(0)
  const attachmentsRef = useRef([])
  const hasText = message.trim().length > 0
  const hasAttachments = attachments.length > 0
  const canSubmit = !submitting && (hasText || hasAttachments || !internal)
  const submitLabel = internal ? 'Add internal note' : hasText ? 'Send message' : hasAttachments ? 'Send media' : 'Send ticket emoji'
  const sendProgress = uploadComplete ? 100 : uploadProgress === null ? 45 : uploadProgress < 0 ? 25 : Math.max(uploadProgress, 12)

  useEffect(() => {
    if (!emojiOpen) return undefined

    function closeOnOutsidePointer(event) {
      if (emojiRef.current?.contains(event.target)) return
      setEmojiOpen(false)
    }

    document.addEventListener('pointerdown', closeOnOutsidePointer)

    return () => {
      document.removeEventListener('pointerdown', closeOnOutsidePointer)
    }
  }, [emojiOpen])

  useEffect(() => {
    attachmentsRef.current = attachments
  }, [attachments])

  useEffect(() => () => {
    attachmentsRef.current.forEach((attachment) => {
      if (attachment.previewUrl) URL.revokeObjectURL(attachment.previewUrl)
    })
  }, [])

  async function send() {
    if (!canSubmit) return
    const outgoingMessage = hasText ? message : !internal && !hasAttachments ? '\u{1F3AB}' : message
    const abortController = new AbortController()
    uploadAbortRef.current = abortController
    setSubmitting(true)
    setUploadProgress(attachments.length ? -1 : null)
    setUploadComplete(false)
    try {
      await onSubmit(
        { message: outgoingMessage, attachments: attachments.map((attachment) => attachment.file), reply_to_message_id: replyingTo?.id },
        { onUploadProgress: setUploadProgress, signal: abortController.signal },
      )
      if (attachments.length) {
        setUploadProgress(100)
        setUploadComplete(true)
        await new Promise((resolve) => window.setTimeout(resolve, 700))
      }
      onTyping?.('')
      setMessage('')
      clearAttachments()
      setUploadProgress(null)
      setUploadComplete(false)
    } catch {
      setUploadProgress(null)
      setUploadComplete(false)
      return
    } finally {
      uploadAbortRef.current = null
      setSubmitting(false)
    }
  }

  async function submit(event) {
    event.preventDefault()
    await send()
  }

  async function handleMessageKeyDown(event) {
    if (event.key !== 'Enter' || event.shiftKey || event.nativeEvent.isComposing) return
    event.preventDefault()
    await send()
  }

  function addFiles(event) {
    const files = Array.from(event.target.files)
    if (!files.length) return

    setAttachments((current) => [
      ...current,
      ...files.map((file) => {
        const isPreviewable = file.type.startsWith('image/') || file.type.startsWith('video/')
        attachmentIdRef.current += 1

        return {
          id: `${Date.now()}-${attachmentIdRef.current}-${file.name}-${file.size}-${file.lastModified}`,
          file,
          previewUrl: isPreviewable ? URL.createObjectURL(file) : '',
        }
      }),
    ])
    event.target.value = ''
  }

  function removeFile(index) {
    setAttachments((current) => {
      const attachment = current[index]
      if (attachment?.previewUrl) URL.revokeObjectURL(attachment.previewUrl)
      return current.filter((_, fileIndex) => fileIndex !== index)
    })
  }

  function clearAttachments() {
    setAttachments((current) => {
      current.forEach((attachment) => {
        if (attachment.previewUrl) URL.revokeObjectURL(attachment.previewUrl)
      })
      return []
    })
  }

  function cancelOrRemoveFile(index) {
    if (submitting) {
      uploadAbortRef.current?.abort()
      removeFile(index)
      return
    }

    removeFile(index)
  }

  function appendEmoji(value) {
    setMessage((current) => {
      const next = `${current}${value}`
      onTyping?.(next)
      return next
    })
  }

  return (
    <form
      className={internal ? 'mt-4 space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4' : 'mt-4 space-y-3'}
      onSubmit={submit}
    >
      {internal ? <p className="text-xs font-bold uppercase tracking-wide text-amber-700">Internal note (not visible to customer)</p> : null}
      <div className="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
        {replyingTo ? (
          <div className="mb-2 flex items-start justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-sm">
            <div className="min-w-0">
              <p className="text-xs font-semibold uppercase tracking-wide text-indigo-700">Replying to {replyingTo.user?.name ?? 'message'}</p>
              <p className="mt-1 truncate text-slate-700">{replyingTo.is_deleted ? 'Original message was deleted.' : replyingTo.message}</p>
            </div>
            <button className="shrink-0 rounded-full p-1 text-slate-500 hover:bg-white hover:text-slate-900" onClick={onCancelReply} type="button">
              <X aria-hidden="true" className="h-4 w-4" />
              <span className="sr-only">Cancel reply</span>
            </button>
          </div>
        ) : null}
        <Textarea
          className="h-24 min-h-24 resize-none overflow-y-auto border-0 px-2 shadow-none focus:border-transparent focus:ring-0 sm:h-28 sm:min-h-28"
          onChange={(event) => {
            setMessage(event.target.value)
            onTyping?.(event.target.value)
          }}
          onKeyDown={handleMessageKeyDown}
          placeholder={internal ? 'Add an internal note' : 'Message'}
          value={message}
        />
        {attachments.length ? (
          <ul className="mt-2 flex flex-wrap gap-2 border-t border-slate-100 pt-2">
            {attachments.map((file, index) => (
              <li className="relative" key={file.id}>
                <SelectedAttachment attachment={file} complete={uploadComplete} progress={uploadProgress} submitting={submitting} />
                <button
                  className="absolute -right-2 -top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-600 shadow ring-1 ring-slate-200 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-60"
                  onClick={() => cancelOrRemoveFile(index)}
                  type="button"
                >
                  <X aria-hidden="true" className="h-3.5 w-3.5" />
                  <span className="sr-only">Remove file</span>
                </button>
              </li>
            ))}
          </ul>
        ) : null}
        <div className="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2">
          <div className="relative flex items-center gap-1" ref={emojiRef}>
            {allowAttachments ? (
              <>
                <label className="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="Attach files">
                  <Paperclip aria-hidden="true" className="h-4 w-4" />
                  <input accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm,.pdf,.doc,.docx,.xls,.xlsx" className="sr-only" multiple onChange={addFiles} type="file" />
                </label>
                <label className="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="Add image">
                  <Image aria-hidden="true" className="h-4 w-4" />
                  <input accept="image/*" className="sr-only" multiple onChange={addFiles} type="file" />
                </label>
                <label className="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="Use camera">
                  <Camera aria-hidden="true" className="h-4 w-4" />
                  <input accept="image/*" capture="environment" className="sr-only" onChange={addFiles} type="file" />
                </label>
                <label className="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="Add video">
                  <Video aria-hidden="true" className="h-4 w-4" />
                  <input accept="video/mp4,video/quicktime,video/webm" className="sr-only" multiple onChange={addFiles} type="file" />
                </label>
              </>
            ) : null}
            <button className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900" onClick={() => setEmojiOpen((open) => !open)} title="Emoji" type="button">
              <Smile aria-hidden="true" className="h-4 w-4" />
              <span className="sr-only">Emoji</span>
            </button>
            {emojiOpen ? (
              <div className="absolute bottom-11 left-0 z-20 w-72 max-w-[calc(100vw-3rem)] rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                <div className="grid w-full grid-cols-[repeat(6,2.25rem)] justify-between gap-y-1.5">
                  {quickEmojis.map((emoji) => (
                    <button className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg text-lg leading-none hover:bg-slate-100" key={emoji} onClick={() => appendEmoji(emoji)} type="button">
                      {emoji}
                    </button>
                  ))}
                </div>
              </div>
            ) : null}
          </div>
          <ReplySubmitButton
            canSubmit={canSubmit}
            hasAttachments={hasAttachments}
            hasText={hasText}
            internal={internal}
            label={submitLabel}
            progress={sendProgress}
            submitting={submitting}
          />
        </div>
      </div>
    </form>
  )
}

function ReplySubmitButton({ canSubmit, hasAttachments, hasText, internal, label, progress, submitting }) {
  const normalized = Math.min(Math.max(progress, 0), 100)

  return (
    <button
      aria-label={label}
      className={clsx(
        'relative inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl text-sm font-semibold shadow-[var(--shadow-xs)] transition-colors duration-150 ease-standard focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none',
        internal
          ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-slate-300'
          : 'bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 focus-visible:ring-indigo-500/30',
      )}
      disabled={!canSubmit}
      title={label}
      type="submit"
    >
      {submitting ? (
        <Progress aria-label="Sending reply" className="absolute inset-0 rounded-xl bg-white" value={normalized}>
          <div className="absolute -left-1 top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border border-slate-300 bg-slate-50" />
          <div className="absolute -right-1 top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border border-slate-300 bg-slate-50" />
          <ProgressIndicator
            className={clsx(
              'absolute inset-y-0 left-0 motion-reduce:transition-none',
              normalized >= 75 ? 'bg-emerald-500' : normalized >= 50 ? 'bg-indigo-500' : normalized >= 25 ? 'bg-blue-500' : 'bg-slate-300',
            )}
            transition={{ type: 'spring', stiffness: 100, damping: 30 }}
            value={normalized}
          />
          <span className="absolute inset-0 flex items-center justify-center">
            <Ticket aria-hidden="true" className={clsx('h-4 w-4', normalized >= 50 ? 'text-white' : 'text-slate-700')} strokeWidth={1.9} />
          </span>
        </Progress>
      ) : internal ? (
        <StickyNote aria-hidden="true" className="h-4 w-4" />
      ) : hasText || hasAttachments ? (
        <Send aria-hidden="true" className="h-4 w-4" />
      ) : (
        <span aria-hidden="true" className="text-lg leading-none">
          {'\u{1F3AB}'}
        </span>
      )}
    </button>
  )
}

function SelectedAttachment({ attachment, complete, progress, submitting }) {
  const [loaded, setLoaded] = useState(false)
  const { file, previewUrl } = attachment
  const isImage = file.type.startsWith('image/')
  const isVideo = file.type.startsWith('video/')

  useEffect(() => {
    setLoaded(false)
  }, [previewUrl])

  if (!isImage && !isVideo) {
    return (
      <span className="inline-flex h-16 max-w-48 items-center rounded-xl bg-slate-100 px-3 py-1.5 pr-8 text-xs text-slate-700">
        <span className="truncate">{file.name}</span>
      </span>
    )
  }

  const showPreparing = !submitting && !loaded

  return (
    <div className="relative h-16 w-16 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
      {isVideo ? (
        <>
          <div className="absolute inset-0 bg-slate-800" />
          {previewUrl ? (
            <video className={`relative h-full w-full object-cover ${loaded ? 'opacity-100' : 'opacity-0'}`} muted onLoadedData={() => setLoaded(true)} preload="metadata" src={previewUrl} />
          ) : null}
          <div className="absolute inset-0 flex items-center justify-center bg-slate-950/20">
            <span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-indigo-600 shadow">
              <Video aria-hidden="true" className="h-4 w-4" />
            </span>
          </div>
        </>
      ) : (
        <>
          {previewUrl ? <img alt="" className="h-full w-full object-cover" onLoad={() => setLoaded(true)} src={previewUrl} /> : null}
          {!previewUrl ? (
            <div className="absolute inset-0 flex items-center justify-center bg-slate-100 text-slate-400">
              <Image aria-hidden="true" className="h-5 w-5" />
            </div>
          ) : null}
        </>
      )}
      {showPreparing ? (
        <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-slate-950/55 px-2 text-[10px] font-bold text-white">
          <span>Loading</span>
          <span className="h-1 w-full overflow-hidden rounded-full bg-white/30">
            <span className="block h-full w-1/2 animate-pulse rounded-full bg-white" />
          </span>
        </div>
      ) : !submitting ? (
        <div className="absolute bottom-1 left-1 rounded-full bg-slate-950/65 px-1.5 py-0.5 text-[10px] font-bold text-white">
          Ready
        </div>
      ) : null}
      {submitting && progress !== null ? (
        <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-slate-950/65 px-2 text-xs font-bold text-white">
          <span>{complete ? 'Uploaded' : progress < 0 ? 'Preparing...' : progress >= 100 ? 'Processing...' : `${progress}%`}</span>
          <span className="h-1 w-full overflow-hidden rounded-full bg-white/30">
            <span
              className={progress < 0 ? 'block h-full w-1/2 animate-pulse rounded-full bg-white' : 'block h-full rounded-full bg-white'}
              style={progress < 0 ? undefined : { width: `${Math.min(progress, 100)}%` }}
            />
          </span>
        </div>
      ) : null}
    </div>
  )
}
