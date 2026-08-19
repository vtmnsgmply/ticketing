import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { clsx } from 'clsx'
import { Download, MessageSquare, MoreHorizontal, Play, Reply, RotateCcw, Trash2, X, ZoomIn, ZoomOut } from 'lucide-react'
import { apiBaseUrl, getAuthToken } from '../../services/apiClient'
import Avatar from '../ui/Avatar'
import EmptyState from '../ui/EmptyState'

const typeLabels = {
  customer_reply: 'Customer',
  agent_reply: 'Agent',
  internal_note: 'Internal',
}

const quickReactions = [
  '\u{1F600}',
  '\u{1F603}',
  '\u{1F604}',
  '\u{1F601}',
  '\u{1F606}',
  '\u{1F605}',
  '\u{1F602}',
  '\u{1F642}',
  '\u{1F609}',
  '\u{1F60A}',
  '\u{1F60D}',
  '\u{1F970}',
  '\u{1F618}',
  '\u{1F60B}',
  '\u{1F61C}',
  '\u{1F917}',
  '\u{1F914}',
  '\u{1F62E}',
  '\u{1F44D}',
  '\u2764\uFE0F',
  '\u{1F622}',
  '\u{1F62D}',
  '\u{1F631}',
  '\u{1F624}',
  '\u{1F621}',
  '\u{1F634}',
  '\u{1F64F}',
  '\u{1F525}',
  '\u2705',
  '\u{1F389}',
  '\u{1F4AF}',
  '\u{1F44F}',
  '\u{1F60E}',
  '\u{1F680}',
]

function attachmentUrl(attachment) {
  return `${apiBaseUrl}${attachment.download_url}`
}

function attachmentThumbnailUrl(attachment) {
  return attachment.thumbnail_url ? `${apiBaseUrl}${attachment.thumbnail_url}` : null
}

function isEmojiOnlyMessage(message) {
  const value = `${message ?? ''}`.trim()
  if (!value) return false

  const textWithoutEmojiParts = value.replace(/[\p{Emoji}\uFE0F\u200D\s]/gu, '')
  return textWithoutEmojiParts.length === 0
}

function isMediaOnlyMessage(message) {
  return !message.is_deleted && !message.reply_to && !message.message && message.attachments.length > 0 && message.attachments.every((attachment) => attachment.is_image || attachment.is_video)
}

export function ProtectedMedia({ attachment, compact = false }) {
  const [url, setUrl] = useState('')
  const [fullUrl, setFullUrl] = useState('')
  const [previewOpen, setPreviewOpen] = useState(false)

  useEffect(() => {
    if (attachment.is_video) return undefined

    let active = true
    let objectUrl = ''

    async function load() {
      const previewUrl = attachmentThumbnailUrl(attachment) ?? attachmentUrl(attachment)
      const response = await fetch(previewUrl, {
        headers: {
          Accept: attachment.mime_type,
          Authorization: `Bearer ${getAuthToken()}`,
        },
      })

      if (!response.ok) return
      objectUrl = URL.createObjectURL(await response.blob())
      if (active) setUrl(objectUrl)
    }

    load()

    return () => {
      active = false
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [attachment])

  async function openPreview() {
    setPreviewOpen(true)

    if (attachment.is_image && !fullUrl) {
      const response = await fetch(attachmentUrl(attachment), {
        headers: {
          Accept: attachment.mime_type,
          Authorization: `Bearer ${getAuthToken()}`,
        },
      })

      if (response.ok) {
        const objectUrl = URL.createObjectURL(await response.blob())
        setFullUrl(objectUrl)
      }
    }
  }

  useEffect(() => () => {
    if (fullUrl) URL.revokeObjectURL(fullUrl)
  }, [fullUrl])

  if (attachment.is_video) {
    return <VideoPreview attachment={attachment} compact={compact} />
  }

  if (!url) {
    return <div className={clsx('animate-pulse rounded-xl bg-slate-100', compact ? 'aspect-square w-full' : 'h-28 w-44')} />
  }

  return (
    <>
      <button className={clsx('block max-w-full overflow-hidden rounded-xl text-left', compact && 'w-full')} onClick={openPreview} type="button">
        <img
          alt={attachment.original_name}
          className={clsx(
            'rounded-xl border border-black/5',
            compact ? 'aspect-square w-full bg-slate-100 object-cover' : 'max-h-72 max-w-full object-contain',
          )}
          src={url}
        />
      </button>
      {previewOpen ? <ImagePreview attachment={attachment} onClose={() => setPreviewOpen(false)} url={fullUrl || url} /> : null}
    </>
  )
}

function ImagePreview({ attachment, onClose, url }) {
  const [scale, setScale] = useState(1)
  const [position, setPosition] = useState({ x: 0, y: 0 })
  const dragRef = useRef(null)

  function updateScale(nextScale) {
    const boundedScale = Math.min(Math.max(nextScale, 1), 4)
    setScale(boundedScale)
    if (boundedScale === 1) {
      setPosition({ x: 0, y: 0 })
    }
  }

  function startDrag(event) {
    if (scale === 1) return
    event.preventDefault()
    dragRef.current = {
      pointerId: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      originX: position.x,
      originY: position.y,
    }
    event.currentTarget.setPointerCapture(event.pointerId)
  }

  function drag(event) {
    const dragState = dragRef.current
    if (!dragState || dragState.pointerId !== event.pointerId) return
    setPosition({
      x: dragState.originX + event.clientX - dragState.startX,
      y: dragState.originY + event.clientY - dragState.startY,
    })
  }

  function stopDrag(event) {
    if (dragRef.current?.pointerId === event.pointerId) {
      dragRef.current = null
    }
  }

  return createPortal(
    <div
      className="fixed inset-0 z-[10000] flex items-center justify-center overflow-hidden bg-slate-950/85 p-4"
      onClick={onClose}
      onWheel={(event) => {
        event.preventDefault()
        updateScale(scale + (event.deltaY < 0 ? 0.25 : -0.25))
      }}
      role="dialog"
    >
      <div className="absolute right-4 top-4 z-10 flex items-center gap-2">
        <DownloadButton attachment={attachment} />
        <button
          className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950"
          onClick={(event) => {
            event.stopPropagation()
            updateScale(scale + 0.5)
          }}
          type="button"
        >
          <ZoomIn aria-hidden="true" className="h-5 w-5" />
          <span className="sr-only">Zoom in</span>
        </button>
        <button
          className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950"
          onClick={(event) => {
            event.stopPropagation()
            updateScale(scale - 0.5)
          }}
          type="button"
        >
          <ZoomOut aria-hidden="true" className="h-5 w-5" />
          <span className="sr-only">Zoom out</span>
        </button>
        <button
          className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950"
          onClick={(event) => {
            event.stopPropagation()
            updateScale(1)
          }}
          type="button"
        >
          <RotateCcw aria-hidden="true" className="h-5 w-5" />
          <span className="sr-only">Reset zoom</span>
        </button>
        <button
          className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950"
          onClick={onClose}
          type="button"
        >
          <X aria-hidden="true" className="h-5 w-5" />
          <span className="sr-only">Close image preview</span>
        </button>
      </div>
      <div
        className={clsx('flex h-full w-full items-center justify-center', scale > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in')}
        onClick={(event) => event.stopPropagation()}
        onDoubleClick={() => updateScale(scale === 1 ? 2 : 1)}
        onPointerCancel={stopDrag}
        onPointerDown={startDrag}
        onPointerMove={drag}
        onPointerUp={stopDrag}
      >
        {url ? (
          <img
            alt={attachment.original_name}
            className="max-h-[calc(100vh-2rem)] max-w-full select-none rounded-xl bg-white object-contain shadow-2xl"
            draggable={false}
            src={url}
            style={{
              transform: `translate(${position.x}px, ${position.y}px) scale(${scale})`,
              transition: dragRef.current ? 'none' : 'transform 150ms ease',
            }}
          />
        ) : (
          <div className="flex h-[60vh] w-[min(90vw,48rem)] items-center justify-center rounded-xl bg-white text-sm font-semibold text-slate-500 shadow-2xl">
            Loading image...
          </div>
        )}
      </div>
    </div>,
    document.body,
  )
}

function VideoPreview({ attachment, compact = false }) {
  const [previewOpen, setPreviewOpen] = useState(false)
  const [url, setUrl] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => () => {
    if (url) URL.revokeObjectURL(url)
  }, [url])

  async function openPreview() {
    setPreviewOpen(true)
    if (url || loading) return

    setLoading(true)
    try {
      const response = await fetch(attachmentUrl(attachment), {
        headers: {
          Accept: attachment.mime_type,
          Authorization: `Bearer ${getAuthToken()}`,
        },
      })

      if (!response.ok) return
      setUrl(URL.createObjectURL(await response.blob()))
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      <button
        className={clsx('group relative block max-w-full overflow-hidden rounded-xl bg-black text-left', compact ? 'aspect-square w-full' : 'h-44 w-72')}
        onClick={openPreview}
        type="button"
      >
        <div className="h-full w-full bg-slate-950" />
        <span className="absolute inset-0 flex items-center justify-center bg-black/20 transition group-hover:bg-black/30">
          <span className="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/95 text-indigo-600 shadow-lg">
            <Play aria-hidden="true" className="ml-1 h-7 w-7 fill-current" />
          </span>
        </span>
      </button>
      {previewOpen ? createPortal(
        <div
          className="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-950/85 p-4"
          onClick={() => setPreviewOpen(false)}
          role="dialog"
        >
          <div className="relative w-full max-w-5xl" onClick={(event) => event.stopPropagation()}>
            <DownloadButton attachment={attachment} className="absolute right-16 top-3 z-10" />
            <button
              className="absolute right-3 top-3 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950"
              onClick={() => setPreviewOpen(false)}
              type="button"
            >
              <X aria-hidden="true" className="h-5 w-5" />
              <span className="sr-only">Close video preview</span>
            </button>
            {url ? (
              <video
                aria-label={attachment.original_name}
                autoPlay
                className="max-h-[calc(100vh-2rem)] w-full rounded-xl bg-black object-contain shadow-2xl"
                controls
                controlsList="nodownload noplaybackrate"
                src={url}
              />
            ) : (
              <div className="flex h-[60vh] w-full items-center justify-center rounded-xl bg-black text-sm font-semibold text-white shadow-2xl">
                {loading ? 'Loading video...' : 'Unable to load video.'}
              </div>
            )}
          </div>
        </div>,
        document.body,
      ) : null}
    </>
  )
}

function DownloadButton({ attachment, className }) {
  return (
    <button
      className={clsx('inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-700 shadow-lg hover:bg-white hover:text-slate-950', className)}
      onClick={(event) => {
        event.stopPropagation()
        downloadAttachment(attachment)
      }}
      title="Save media"
      type="button"
    >
      <Download aria-hidden="true" className="h-5 w-5" />
      <span className="sr-only">Save media</span>
    </button>
  )
}

async function downloadAttachment(attachment) {
  const response = await fetch(attachmentUrl(attachment), {
    headers: {
      Accept: attachment.mime_type,
      Authorization: `Bearer ${getAuthToken()}`,
    },
  })

  if (!response.ok) return
  const objectUrl = URL.createObjectURL(await response.blob())
  const anchor = document.createElement('a')
  anchor.href = objectUrl
  anchor.download = attachment.original_name
  anchor.click()
  URL.revokeObjectURL(objectUrl)
}

function AttachmentItem({ attachment }) {
  async function download() {
    await downloadAttachment(attachment)
  }

  if (attachment.is_image || attachment.is_video) {
    return <ProtectedMedia attachment={attachment} />
  }

  return (
    <button
      className="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
      onClick={download}
      type="button"
    >
      <Download aria-hidden="true" className="h-4 w-4" />
      <span className="truncate">{attachment.original_name}</span>
    </button>
  )
}

export default function TicketConversation({ className, currentUser, messages, onDelete, onReact, onReply, repliesEnabled = true, typingUsers = [] }) {
  const [openMenu, setOpenMenu] = useState(null)
  const [newMessageNotice, setNewMessageNotice] = useState(null)
  const scrollRef = useRef(null)
  const holdTimer = useRef(null)
  const initializedScrollRef = useRef(false)
  const lastMessageIdRef = useRef(null)
  const shouldStickToBottomRef = useRef(true)

  useEffect(() => clearHold, [])

  useLayoutEffect(() => {
    const scrollElement = scrollRef.current
    if (!scrollElement) return
    const lastMessage = messages.at(-1)

    if (!initializedScrollRef.current) {
      scrollElement.scrollTop = scrollElement.scrollHeight
      initializedScrollRef.current = true
      lastMessageIdRef.current = lastMessage?.id ?? null
      shouldStickToBottomRef.current = true
      return
    }

    if (lastMessage && lastMessage.id !== lastMessageIdRef.current) {
      const isIncoming = Number(lastMessage.user?.id) !== Number(currentUser?.id)
      if (isIncoming && !shouldStickToBottomRef.current) {
        setNewMessageNotice(lastMessage)
      } else {
        setNewMessageNotice(null)
      }
      lastMessageIdRef.current = lastMessage.id
    }

    if (shouldStickToBottomRef.current) {
      scrollElement.scrollTop = scrollElement.scrollHeight
    }
  }, [currentUser?.id, messages, typingUsers.length])

  useEffect(() => {
    const scrollElement = scrollRef.current
    if (!scrollElement) return undefined

    const observer = new ResizeObserver(() => {
      if (shouldStickToBottomRef.current) {
        scrollElement.scrollTop = scrollElement.scrollHeight
      }
    })

    observer.observe(scrollElement)

    return () => {
      observer.disconnect()
    }
  }, [])

  if (!messages.length) {
    return <EmptyState description="Replies and notes will appear here." icon={MessageSquare} title="No conversation yet" />
  }

  function sendReaction(messageId, reaction) {
    onReact(messageId, reaction)
    setOpenMenu(null)
  }

  function startHold(messageId) {
    clearHold()
    holdTimer.current = window.setTimeout(() => {
      setOpenMenu(messageId)
    }, 450)
  }

  function clearHold() {
    if (!holdTimer.current) return
    window.clearTimeout(holdTimer.current)
    holdTimer.current = null
  }

  function updateScrollState(event) {
    const element = event.currentTarget
    shouldStickToBottomRef.current = element.scrollHeight - element.scrollTop - element.clientHeight < 96
    if (shouldStickToBottomRef.current) {
      setNewMessageNotice(null)
    }
  }

  function scrollToLatestMessage() {
    const scrollElement = scrollRef.current
    if (!scrollElement) return
    shouldStickToBottomRef.current = true
    scrollElement.scrollTo({ top: scrollElement.scrollHeight, behavior: 'smooth' })
    setNewMessageNotice(null)
  }

  return (
    <div className={clsx('relative space-y-4 overscroll-contain pb-4', className)} onScroll={updateScrollState} ref={scrollRef}>
      {newMessageNotice ? <NewMessageNotice message={newMessageNotice} onClick={scrollToLatestMessage} /> : null}
      {messages.map((message) => {
        const mine = message.user?.id === currentUser?.id
        const isInternal = message.message_type === 'internal_note'
        const canDelete = (mine || currentUser?.role?.slug === 'administrator') && !message.is_deleted
        const canReply = repliesEnabled && !message.is_deleted && message.message_type !== 'internal_note'
        const isEmojiOnly = !message.is_deleted && !message.reply_to && !message.attachments.length && isEmojiOnlyMessage(message.message)
        const isMediaOnly = isMediaOnlyMessage(message)
        const selectedReaction = message.reactions.find((reaction) => reaction.user_ids?.includes(currentUser?.id))?.reaction ?? null

        return (
          <article className={clsx('flex w-full min-w-0 gap-2 overflow-hidden', mine ? 'justify-end' : 'justify-start')} key={message.id}>
            {!mine ? <Avatar name={message.user?.name ?? 'System'} size="sm" /> : null}
            <div className={clsx('group flex min-w-0 max-w-[min(78%,680px)] flex-col overflow-visible', mine && 'items-end')}>
              <div className={clsx('mb-1 text-xs text-slate-500', mine ? 'text-right' : 'text-left')}>
                <div className={clsx('flex flex-wrap items-center gap-x-2 gap-y-0.5', mine && 'justify-end')}>
                  <span className="max-w-40 truncate font-semibold text-slate-700 sm:max-w-64">{message.user?.name ?? 'System'}</span>
                  <span>{typeLabels[message.message_type] ?? message.message_type.replaceAll('_', ' ')}</span>
                </div>
                <time className="mt-0.5 block">{new Date(message.created_at).toLocaleString()}</time>
              </div>
              <div className="flex items-end gap-1">
                {mine ? (
                  <MessageActions
                    canDelete={canDelete}
                    isOpen={openMenu === message.id}
                    messageId={message.id}
                    mine={mine}
                    onDelete={onDelete}
                    onOpen={setOpenMenu}
                    onReact={sendReaction}
                    selectedReaction={selectedReaction}
                  />
                ) : null}
                <div
                  className={clsx(
                    'relative min-w-0 max-w-full',
                    isEmojiOnly || isMediaOnly
                      ? clsx('shadow-none', isEmojiOnly ? 'px-1 py-0 text-5xl leading-none' : 'p-0')
                      : clsx(
                          'rounded-2xl px-4 py-3 shadow-sm',
                          message.is_deleted
                            ? 'border border-dashed border-slate-300 bg-slate-100 text-slate-700'
                            : mine
                              ? 'rounded-br-md bg-indigo-600 text-white'
                              : 'rounded-bl-md border border-slate-200 bg-white text-slate-800',
                          isInternal && !message.is_deleted && 'border-amber-200 bg-amber-50 text-amber-950',
                        ),
                  )}
                  onContextMenu={(event) => {
                    event.preventDefault()
                    setOpenMenu(message.id)
                  }}
                  onMouseDown={() => startHold(message.id)}
                  onMouseLeave={clearHold}
                  onMouseUp={clearHold}
                  onTouchCancel={clearHold}
                  onTouchEnd={clearHold}
                  onTouchStart={() => startHold(message.id)}
                >
                  {message.is_deleted ? (
                    <p className="text-sm italic">{message.deleted_by?.name ?? message.user?.name ?? 'Sender'} deleted this message.</p>
                  ) : (
                    <>
                      {message.reply_to ? <ReplyPreview mine={mine} replyTo={message.reply_to} /> : null}
                      {message.message ? (
                        <p className={clsx('max-w-full whitespace-pre-wrap [overflow-wrap:anywhere]', isEmojiOnly ? 'text-5xl leading-none' : 'break-all text-sm leading-6')}>{message.message}</p>
                      ) : null}
                      {message.attachments.length ? (
                        <div className={clsx('grid gap-2', message.message && 'mt-3', isMediaOnly && 'overflow-hidden rounded-2xl')}>
                          {message.attachments.map((attachment) => (
                            <AttachmentItem attachment={attachment} key={attachment.id} />
                          ))}
                        </div>
                      ) : null}
                    </>
                  )}
                  {message.reactions.length ? (
                    <div className={clsx('absolute -bottom-4 flex flex-wrap gap-1', mine ? 'right-2' : 'left-2')}>
                      {message.reactions.map((reaction) => (
                        <button
                          className="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs text-slate-700 shadow-sm hover:bg-slate-50"
                          key={reaction.reaction}
                          onClick={() => sendReaction(message.id, reaction.reaction)}
                          title={reaction.users.map((user) => user?.name).filter(Boolean).join(', ')}
                          type="button"
                        >
                          {reaction.reaction}
                        </button>
                      ))}
                    </div>
                  ) : null}
                </div>
                {!mine ? (
                  <MessageActions
                    canDelete={canDelete}
                    isOpen={openMenu === message.id}
                    messageId={message.id}
                    mine={mine}
                    onDelete={onDelete}
                    onOpen={setOpenMenu}
                    onReact={sendReaction}
                    selectedReaction={selectedReaction}
                  />
                ) : null}
              </div>
              {canReply ? (
                <button
                  className={clsx(
                    'mt-5 inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900',
                    mine ? 'self-end' : 'self-start',
                  )}
                  onClick={() => onReply?.(message)}
                  type="button"
                >
                  <Reply aria-hidden="true" className="h-3.5 w-3.5" />
                  Reply
                </button>
              ) : null}
              {mine ? <MessageSeenReceipt currentUser={currentUser} message={message} /> : null}
            </div>
          </article>
        )
      })}
      {typingUsers.length ? <TypingIndicator users={typingUsers} /> : null}
    </div>
  )
}

function MessageSeenReceipt({ currentUser, message }) {
  const readers = (message.read_by ?? [])
    .filter((read) => read.user?.id && Number(read.user.id) !== Number(currentUser?.id))

  if (!readers.length) return null

  const latestRead = readers.at(-1)
  const readerNames = readers.map((read) => read.user?.name).filter(Boolean)
  const label = readerNames.length > 1 ? `Seen by ${readerNames.length}` : `Seen by ${readerNames[0] ?? 'recipient'}`

  return (
    <div className="mt-1 flex max-w-full items-center justify-end gap-1 text-[11px] font-medium text-slate-400">
      <span className="truncate">{label}</span>
      {latestRead?.read_at ? <time className="shrink-0">{new Date(latestRead.read_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</time> : null}
    </div>
  )
}

function messagePreview(message) {
  if (message.message?.trim()) {
    return message.message.trim().replace(/\s+/g, ' ').slice(0, 72)
  }

  const imageCount = message.attachments.filter((attachment) => attachment.is_image).length
  const videoCount = message.attachments.filter((attachment) => attachment.is_video).length

  if (imageCount && videoCount) return 'Sent media attachments'
  if (imageCount) return imageCount === 1 ? 'Sent an image' : `Sent ${imageCount} images`
  if (videoCount) return videoCount === 1 ? 'Sent a video' : `Sent ${videoCount} videos`
  if (message.attachments.length) return 'Sent an attachment'
  return 'New message'
}

function NewMessageNotice({ message, onClick }) {
  return (
    <button
      className="sticky top-3 z-20 mx-auto flex max-w-[min(22rem,calc(100%-2rem))] items-center gap-2 rounded-full border border-indigo-100 bg-white/95 px-3 py-2 text-left text-xs shadow-lg shadow-slate-200/80 backdrop-blur hover:bg-indigo-50"
      onClick={onClick}
      type="button"
    >
      <span className="h-2 w-2 shrink-0 rounded-full bg-indigo-500" />
      <span className="min-w-0">
        <span className="block truncate font-semibold text-slate-900">{message.user?.name ?? 'New message'}</span>
        <span className="block truncate text-slate-500">{messagePreview(message)}</span>
      </span>
    </button>
  )
}

function TypingIndicator({ users }) {
  const names = users.map((user) => user.name).filter(Boolean)
  const label = names.length === 1 ? `${names[0]} is typing` : 'Someone is typing'

  return (
    <div className="flex items-center gap-2 pl-2 text-xs font-medium text-slate-500">
      <span>{label}</span>
      <span className="inline-flex items-center gap-1">
        <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.2s]" />
        <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.1s]" />
        <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" />
      </span>
    </div>
  )
}

function ReplyPreview({ mine, replyTo }) {
  return (
    <div
      className={clsx(
        'mb-2 max-w-full rounded-lg border-l-2 px-3 py-2 text-xs',
        mine ? 'border-white/60 bg-white/15 text-indigo-50' : 'border-indigo-300 bg-indigo-50 text-slate-700',
      )}
    >
      <p className={clsx('truncate font-semibold', mine ? 'text-white' : 'text-slate-800')}>{replyTo.user?.name ?? 'Message'}</p>
      <p className={clsx('mt-0.5 line-clamp-2 break-words', mine ? 'text-indigo-50' : 'text-slate-600')}>
        {replyTo.is_deleted ? 'Original message was deleted.' : replyTo.message}
      </p>
    </div>
  )
}

function MessageActions({ canDelete, isOpen, messageId, mine, onDelete, onOpen, onReact, selectedReaction }) {
  const rootRef = useRef(null)
  const popupRef = useRef(null)
  const [popupStyle, setPopupStyle] = useState(null)

  useEffect(() => {
    if (!isOpen) return undefined

    function closeOnOutsidePointer(event) {
      if (rootRef.current?.contains(event.target) || popupRef.current?.contains(event.target)) return
      onOpen(null)
    }

    document.addEventListener('pointerdown', closeOnOutsidePointer)

    return () => {
      document.removeEventListener('pointerdown', closeOnOutsidePointer)
    }
  }, [isOpen, onOpen])

  useEffect(() => {
    if (!isOpen || !rootRef.current) return

    const width = 288
    const margin = 12
    const rect = rootRef.current.getBoundingClientRect()
    const left = Math.min(
      Math.max(mine ? rect.right - width : rect.left, margin),
      window.innerWidth - width - margin,
    )
    const shouldOpenBelow = rect.top < 280

    setPopupStyle({
      left,
      top: shouldOpenBelow ? rect.bottom + 8 : rect.top - 8,
      transform: shouldOpenBelow ? 'none' : 'translateY(-100%)',
      width,
    })
  }, [isOpen, mine])

  return (
    <div className={clsx('relative shrink-0 opacity-100 sm:transition sm:group-hover:opacity-100', !isOpen && 'sm:opacity-0')} ref={rootRef}>
      <button
        className="mb-1 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900"
        onClick={() => onOpen(isOpen ? null : messageId)}
        type="button"
      >
        <MoreHorizontal aria-hidden="true" className="h-4 w-4" />
        <span className="sr-only">Message actions</span>
      </button>
      {isOpen && popupStyle ? createPortal(
        <div
          className="fixed z-[9999] max-w-[calc(100vw-3rem)] rounded-xl border border-slate-200 bg-white p-3 text-slate-900 shadow-xl"
          ref={popupRef}
          style={popupStyle}
        >
          <div className="grid w-full grid-cols-[repeat(6,2.25rem)] justify-between gap-y-1.5">
            {quickReactions.map((reaction) => (
              <button
                aria-pressed={selectedReaction === reaction}
                className={clsx(
                  'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg text-lg leading-none hover:bg-slate-100',
                  selectedReaction === reaction && 'bg-indigo-100 ring-2 ring-indigo-400',
                )}
                key={reaction}
                onClick={() => onReact(messageId, reaction)}
                type="button"
              >
                {reaction}
              </button>
            ))}
          </div>
          {canDelete ? (
            <button className="mt-2 flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm text-red-600 hover:bg-red-50" onClick={() => onDelete(messageId)} type="button">
              <Trash2 aria-hidden="true" className="h-4 w-4" />
              Delete
            </button>
          ) : null}
        </div>,
        document.body,
      ) : null}
    </div>
  )
}
