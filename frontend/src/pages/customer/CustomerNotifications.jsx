import { useEffect, useState } from 'react'
import { BellOff, CheckCheck } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import NotificationItem from '../../components/notifications/NotificationItem'
import { Button, EmptyState, PageHeader, Pagination } from '../../components/ui'
import { getCustomerNotifications, markAllNotificationsRead, markNotificationRead } from '../../services/customerService'
import { showError, showSuccess } from '../../utils/alerts'

export default function CustomerNotifications() {
  const [items, setItems] = useState([])
  const [pagination, setPagination] = useState(null)

  async function load() {
    try {
      const data = await getCustomerNotifications({ per_page: 20 })
      setItems(data.notifications)
      setPagination(data.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load notifications.')
    }
  }

  useEffect(() => {
    load()
  }, [])

  async function read(id) {
    await markNotificationRead(id)
    await load()
  }

  async function readAll() {
    await markAllNotificationsRead()
    await showSuccess('Notifications marked as read.')
    await load()
  }

  return (
    <AppShell width="standard">
      <PageHeader
        actions={<Button onClick={readAll} variant="secondary"><CheckCheck className="h-4 w-4" />Mark All Read</Button>}
        eyebrow="Customer Portal"
        title="Notifications"
      />
      {items.length ? (
        <div className="max-w-3xl space-y-3">
          {items.map((item) => (
            <NotificationItem item={item} key={item.id} onRead={read} ticketPath={`/customer/tickets/${item.ticket_id}`} />
          ))}
          <Pagination pagination={pagination} />
        </div>
      ) : (
        <EmptyState description="Customer ticket updates will appear here." icon={BellOff} title="No notifications" />
      )}
    </AppShell>
  )
}
