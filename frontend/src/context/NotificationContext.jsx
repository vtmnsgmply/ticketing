import { useCallback, useEffect, useMemo, useState } from 'react'
import { useAuth } from '../hooks/useAuth'
import { useLoadingProgress } from '../hooks/useLoadingProgress'
import { disconnectEcho, getEchoClient } from '../services/echoClient'
import { getNotifications, markAllAsRead, markAsRead } from '../services/notificationService'
import { NotificationContext } from './notificationContextValue'

const customerToastTypes = new Set([
  'status_changed',
  'ticket_resolved',
  'ticket_closed',
  'ticket_cancelled',
])

function shouldShowToast(role, notification) {
  if (!role) return false
  if (role !== 'customer') return true
  return customerToastTypes.has(notification.type)
}

export function NotificationProvider({ children }) {
  const { isAuthenticated, user } = useAuth()
  const [items, setItems] = useState([])
  const [unreadCount, setUnreadCount] = useState(0)
  const [highlightedIds, setHighlightedIds] = useState([])
  const [toastItems, setToastItems] = useState([])
  const [loading, setLoading] = useState(false)
  const [bootstrapped, setBootstrapped] = useState(false)
  const { completeTask, startTask } = useLoadingProgress()

  const load = useCallback(async () => {
    if (!isAuthenticated || !user?.role?.slug) {
      setItems([])
      setUnreadCount(0)
      setHighlightedIds([])
      setToastItems([])
      setBootstrapped(true)
      completeTask('notifications')
      completeTask('workspace')
      return null
    }

    startTask('notifications')
    setLoading(true)
    try {
      const data = await getNotifications({ per_page: 5 })
      setItems(data.notifications)
      setUnreadCount(data.unread_count ?? 0)
      setHighlightedIds([])
      setBootstrapped(true)
      completeTask('notifications')
      completeTask('workspace')
      return data
    } catch {
      setBootstrapped(true)
      completeTask('notifications')
      completeTask('workspace')
      return null
    } finally {
      setLoading(false)
    }
  }, [completeTask, isAuthenticated, startTask, user?.role?.slug])

  useEffect(() => {
    let active = true

    async function loadInitial() {
      if (!active) return
      await load().catch(() => {})
    }

    loadInitial()
    const timer = window.setInterval(() => {
      if (!document.hidden) load().catch(() => {})
    }, 300000)

    return () => {
      active = false
      window.clearInterval(timer)
    }
  }, [load])

  useEffect(() => {
    if (!isAuthenticated || !user?.id) {
      disconnectEcho()
      return undefined
    }

    const echo = getEchoClient()
    if (!echo) return undefined

    const channelName = `notifications.${user.id}`
    const channel = echo.private(channelName)

    channel.listen('.notification.created', (event) => {
      const notification = event.notification
      if (!notification?.id) return

      setItems((current) => {
        const withoutDuplicate = current.filter((item) => item.id !== notification.id)
        return [notification, ...withoutDuplicate].slice(0, 5)
      })
      setUnreadCount((current) => current + (notification.is_read ? 0 : 1))
      if (!notification.is_read) {
        setHighlightedIds((current) => [notification.id, ...current.filter((id) => id !== notification.id)].slice(0, 5))
      }
      if (shouldShowToast(user?.role?.slug, notification)) {
        setToastItems((current) => [notification, ...current.filter((item) => item.id !== notification.id)].slice(0, 3))
      }
    })

    return () => {
      echo.leave(channelName)
    }
  }, [isAuthenticated, user?.id, user?.role?.slug])

  const read = useCallback(async (id) => {
    await markAsRead(id)
    await load()
  }, [load])

  const dismissToast = useCallback((id) => {
    setToastItems((current) => current.filter((item) => item.id !== id))
  }, [])

  const acknowledgeVisible = useCallback(async () => {
    const unreadIds = items.filter((item) => !item.is_read).map((item) => item.id)
    if (!unreadIds.length) {
      setUnreadCount(0)
      return
    }

    setHighlightedIds(unreadIds)
    setUnreadCount(0)
    setItems((current) => current.map((item) => unreadIds.includes(item.id) ? { ...item, is_read: true } : item))
    await markAllAsRead()
  }, [items])

  const value = useMemo(
    () => ({
      items,
      unreadCount,
      toastItems,
      highlightedIds,
      loading,
      bootstrapped,
      refresh: load,
      markAsRead: read,
      acknowledgeVisible,
      dismissToast,
    }),
    [items, unreadCount, toastItems, highlightedIds, loading, bootstrapped, load, read, acknowledgeVisible, dismissToast],
  )

  return <NotificationContext.Provider value={value}>{children}</NotificationContext.Provider>
}
