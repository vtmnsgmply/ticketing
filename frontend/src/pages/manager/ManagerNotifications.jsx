import { useEffect, useState } from 'react'
import { BellOff, CheckCheck } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import NotificationItem from '../../components/notifications/NotificationItem'
import { Button, EmptyState, PageHeader, Pagination } from '../../components/ui'
import { getManagerNotifications, markAllManagerNotificationsRead, markManagerNotificationRead } from '../../services/managerService'
import { showError, showSuccess } from '../../utils/alerts'

export default function ManagerNotifications() {
  const [items, setItems] = useState([])
  const [pagination, setPagination] = useState(null)

  async function load() {
    try {
      const data = await getManagerNotifications({ per_page: 20 })
      setItems(data.notifications)
      setPagination(data.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load notifications.')
    }
  }

  useEffect(() => { load() }, [])

  async function readAll() {
    await markAllManagerNotificationsRead()
    await showSuccess('Notifications marked as read.')
    await load()
  }

  async function read(id) {
    await markManagerNotificationRead(id)
    await load()
  }

  return (
    <AppShell width="standard">
      <PageHeader actions={<Button onClick={readAll} variant="secondary"><CheckCheck className="h-4 w-4" />Mark All Read</Button>} eyebrow="Manager Workspace" title="Notifications" />
      {items.length ? (
        <div className="max-w-3xl space-y-3">
          {items.map((item) => (
            <NotificationItem item={item} key={item.id} onRead={read} ticketPath={`/manager/tickets/${item.ticket_id}`} />
          ))}
          <Pagination pagination={pagination} />
        </div>
      ) : (
        <EmptyState description="Operational notifications will appear here." icon={BellOff} title="No notifications" />
      )}
    </AppShell>
  )
}
