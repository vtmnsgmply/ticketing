import { useContext } from 'react'
import { NotificationContext } from '../context/notificationContextValue'

export function useNotifications() {
  const context = useContext(NotificationContext)

  if (context === null) {
    throw new Error('useNotifications must be used within a NotificationProvider')
  }

  return context
}
