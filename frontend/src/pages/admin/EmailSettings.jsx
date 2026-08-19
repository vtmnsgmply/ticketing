import { useEffect, useState } from 'react'
import { Send } from 'lucide-react'
import { adminService } from '../../services/adminService'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import Field from '../../components/ui/Field'
import Input from '../../components/ui/Input'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import Select from '../../components/ui/Select'
import { confirmAction, showError, showSuccess } from '../../utils/alerts'

const defaults = { sender_name: '', sender_email: '', reply_to_email: '', smtp_host: '', smtp_port: '', smtp_username: '', smtp_password: '', encryption: 'tls' }

function toForm(settings) {
  return settings.reduce((carry, setting) => ({ ...carry, [setting.key]: setting.value === '[configured]' ? '' : (setting.value ?? '') }), defaults)
}

export default function EmailSettings() {
  const [form, setForm] = useState(defaults)
  const [testEmail, setTestEmail] = useState('')

  useEffect(() => {
    adminService.getEmailSettings().then((settings) => setForm(toForm(settings))).catch((error) => showError(error.message || 'Unable to load email settings.'))
  }, [])

  function update(event) {
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }))
  }

  async function save(event) {
    event.preventDefault()
    const result = await confirmAction('Save email settings? Secrets will not be returned after storage.')
    if (!result.isConfirmed) return
    try {
      await adminService.updateEmailSettings({ ...form, smtp_port: form.smtp_port ? Number(form.smtp_port) : null })
      await showSuccess('Email settings saved.')
    } catch (error) {
      await showError(error.message || 'Unable to save email settings.')
    }
  }

  async function test(event) {
    event.preventDefault()
    try {
      await adminService.testEmailSettings({ email: testEmail })
      await showSuccess('Test email request accepted.')
    } catch (error) {
      await showError(error.message || 'Unable to send test email.')
    }
  }

  return (
    <PageContainer className="space-y-6" width="standard">
      <PageHeader eyebrow="Administration" title="Email Settings" />

      <Card>
        <h2 className="text-base font-semibold text-slate-950">Outbound Mail</h2>
        <p className="mt-1 text-sm text-slate-500">Sender identity and SMTP transport used for outbound notifications.</p>

        <form className="mt-6 space-y-5" onSubmit={save}>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Sender name">
              <Input name="sender_name" onChange={update} value={form.sender_name} />
            </Field>
            <Field label="Sender email">
              <Input name="sender_email" onChange={update} type="email" value={form.sender_email} />
            </Field>
            <Field label="Reply-to email">
              <Input name="reply_to_email" onChange={update} type="email" value={form.reply_to_email} />
            </Field>
            <Field label="Encryption">
              <Select name="encryption" onChange={update} value={form.encryption ?? 'tls'}>
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="none">None</option>
              </Select>
            </Field>
            <Field label="SMTP host">
              <Input name="smtp_host" onChange={update} value={form.smtp_host} />
            </Field>
            <Field label="SMTP port">
              <Input name="smtp_port" onChange={update} type="number" value={form.smtp_port} />
            </Field>
            <Field label="SMTP username">
              <Input name="smtp_username" onChange={update} value={form.smtp_username} />
            </Field>
            <Field label="SMTP password">
              <Input name="smtp_password" onChange={update} type="password" value={form.smtp_password} />
            </Field>
          </div>

          <Button type="submit">Save Email Settings</Button>
        </form>
      </Card>

      <Card>
        <h2 className="text-base font-semibold text-slate-950">Send Test Email</h2>
        <p className="mt-1 text-sm text-slate-500">Verify the configuration above by sending a test message.</p>

        <form className="mt-4 flex flex-col gap-3 sm:flex-row" onSubmit={test}>
          <Input className="sm:max-w-xs" onChange={(event) => setTestEmail(event.target.value)} placeholder="Test recipient" required type="email" value={testEmail} />
          <Button type="submit" variant="secondary">
            <Send aria-hidden="true" className="h-4 w-4" />
            Send Test
          </Button>
        </form>
      </Card>
    </PageContainer>
  )
}
