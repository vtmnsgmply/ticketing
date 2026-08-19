import { useEffect, useState } from 'react'
import { adminService } from '../../services/adminService'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import Checkbox from '../../components/ui/Checkbox'
import Field from '../../components/ui/Field'
import Input from '../../components/ui/Input'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import { confirmAction, showError, showSuccess } from '../../utils/alerts'

const defaults = {
  ticket_prefix: 'TKT',
  ticket_start_number: 10000,
  attachment_max_size_mb: 10,
  notification_web_enabled: true,
  notification_email_enabled: true,
  allow_customer_reopen_resolved: true,
  customer_reopen_limit: 3,
}

function toForm(settings) {
  return settings.reduce((carry, setting) => ({
    ...carry,
    [setting.key]: setting.value_type === 'boolean'
      ? setting.value === '1'
      : setting.value_type === 'integer'
        ? Number(setting.value)
        : setting.value,
  }), defaults)
}

export default function SystemSettings() {
  const [form, setForm] = useState(defaults)

  useEffect(() => {
    adminService.getSystemSettings().then((settings) => setForm(toForm(settings))).catch((error) => showError(error.message || 'Unable to load settings.'))
  }, [])

  function update(event) {
    const { name, type, checked, value } = event.target
    setForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : type === 'number' ? Number(value) : value }))
  }

  async function save(event) {
    event.preventDefault()
    const result = await confirmAction('Save system settings? Ticket numbering changes affect future tickets only.')
    if (!result.isConfirmed) return
    try {
      await adminService.updateSystemSettings({
        ...form,
        attachment_max_size_mb: Number(form.attachment_max_size_mb),
        customer_reopen_limit: Number(form.customer_reopen_limit),
        ticket_start_number: Number(form.ticket_start_number),
      })
      await showSuccess('System settings saved.')
    } catch (error) {
      await showError(error.message || 'Unable to save system settings.')
    }
  }

  return (
    <PageContainer width="standard">
      <PageHeader eyebrow="Administration" title="System Settings" />
      <Card>
        <h2 className="text-base font-semibold text-slate-950">Ticketing</h2>
        <p className="mt-1 text-sm text-slate-500">Controls how new tickets are numbered and how large attachments may be.</p>

        <form className="mt-6 space-y-5" onSubmit={save}>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Field label="Ticket Prefix">
              <Input name="ticket_prefix" onChange={update} value={form.ticket_prefix ?? ''} />
            </Field>
            <Field label="Ticket Start Number">
              <Input min="1" name="ticket_start_number" onChange={update} type="number" value={form.ticket_start_number ?? 10000} />
            </Field>
            <Field label="Attachment Max MB">
              <Input min="1" name="attachment_max_size_mb" onChange={update} type="number" value={form.attachment_max_size_mb ?? 10} />
            </Field>
          </div>

          <div className="space-y-3 border-t border-slate-100 pt-5">
            <Checkbox checked={Boolean(form.notification_web_enabled)} label="Web notifications" name="notification_web_enabled" onChange={update} />
            <Checkbox checked={Boolean(form.notification_email_enabled)} label="Email notifications" name="notification_email_enabled" onChange={update} />
            <Checkbox checked={Boolean(form.allow_customer_reopen_resolved)} label="Customers can reopen resolved tickets" name="allow_customer_reopen_resolved" onChange={update} />
            <Field label="Customer Reopen Limit">
              <Input min="0" name="customer_reopen_limit" onChange={update} type="number" value={form.customer_reopen_limit ?? 3} />
            </Field>
          </div>

          <Button type="submit">Save Settings</Button>
        </form>
      </Card>
    </PageContainer>
  )
}
