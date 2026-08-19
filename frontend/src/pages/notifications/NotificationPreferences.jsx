import { useEffect, useState } from 'react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, Checkbox, PageHeader, Skeleton } from '../../components/ui'
import { getNotificationPreferences, updateNotificationPreferences } from '../../services/notificationService'
import { showError, showSuccess } from '../../utils/alerts'

export default function NotificationPreferences() {
  const [preferences, setPreferences] = useState(null)

  useEffect(() => {
    getNotificationPreferences().then(setPreferences).catch((error) => showError(error.message || 'Unable to load preferences.'))
  }, [])

  async function save() {
    try {
      setPreferences(await updateNotificationPreferences(preferences))
      await showSuccess('Notification preferences updated.')
    } catch (error) {
      await showError(error.message || 'Unable to update preferences.')
    }
  }

  if (!preferences) {
    return <AppShell width="standard"><Skeleton className="h-48 w-full" /></AppShell>
  }

  return (
    <AppShell width="standard">
      <PageHeader eyebrow="Notifications" title="Preferences" />
      <Card className="max-w-2xl space-y-4">
        <Checkbox
          checked={preferences.web_notifications_enabled}
          disabled={!preferences.system_web_enabled}
          label="Web notifications"
          onChange={(event) => setPreferences((current) => ({ ...current, web_notifications_enabled: event.target.checked }))}
        />
        <Checkbox
          checked={preferences.email_notifications_enabled}
          disabled={!preferences.system_email_enabled}
          label="Email notifications"
          onChange={(event) => setPreferences((current) => ({ ...current, email_notifications_enabled: event.target.checked }))}
        />
        <Button onClick={save}>Save Preferences</Button>
      </Card>
    </AppShell>
  )
}
