import { useEffect, useState } from 'react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, Field, Input, PageHeader } from '../../components/ui'
import { changeCustomerPassword, getCustomerProfile, updateCustomerProfile } from '../../services/customerService'
import { showError, showSuccess } from '../../utils/alerts'

export default function CustomerProfile() {
  const [profile, setProfile] = useState({ name: '', email: '', phone: '', company: '', telegram_profile: '' })
  const [password, setPassword] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    getCustomerProfile().then((user) => setProfile({
      name: user.name ?? '',
      email: user.email ?? '',
      phone: user.phone ?? '',
      company: user.company ?? '',
      telegram_profile: user.telegram_profile ?? '',
    })).catch((error) => showError(error.message || 'Unable to load profile.'))
  }, [])

  function updateProfileField(event) {
    setProfile((current) => ({ ...current, [event.target.name]: event.target.value }))
  }

  function updatePasswordField(event) {
    setPassword((current) => ({ ...current, [event.target.name]: event.target.value }))
  }

  async function submitProfile(event) {
    event.preventDefault()
    setSaving(true)
    setErrors({})
    try {
      const user = await updateCustomerProfile(profile)
      setProfile({ name: user.name ?? '', email: user.email ?? '', phone: user.phone ?? '', company: user.company ?? '', telegram_profile: user.telegram_profile ?? '' })
      await showSuccess('Profile updated.')
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to update profile.')
    } finally {
      setSaving(false)
    }
  }

  async function submitPassword(event) {
    event.preventDefault()
    setErrors({})
    try {
      await changeCustomerPassword(password)
      setPassword({ current_password: '', password: '', password_confirmation: '' })
      await showSuccess('Password changed.')
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to change password.')
    }
  }

  return (
    <AppShell width="standard">
      <PageHeader eyebrow="Customer Portal" title="Profile" />
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <h2 className="mb-4 text-lg font-semibold tracking-tight text-slate-950">Account Details</h2>
          <form className="space-y-4" onSubmit={submitProfile}>
            <Field error={errors.name?.[0]} label="Name"><Input name="name" onChange={updateProfileField} value={profile.name} /></Field>
            <Field error={errors.email?.[0]} label="Email"><Input name="email" onChange={updateProfileField} type="email" value={profile.email} /></Field>
            <Field error={errors.phone?.[0]} label="Phone"><Input name="phone" onChange={updateProfileField} value={profile.phone} /></Field>
            <Field error={errors.company?.[0]} label="Company"><Input name="company" onChange={updateProfileField} value={profile.company} /></Field>
            <Field
              error={errors.telegram_profile?.[0]}
              hint="Send /start to the Telegram bot, then save the numeric chat ID it replies with."
              label="Telegram Chat ID"
            >
              <Input name="telegram_profile" onChange={updateProfileField} placeholder="Example: 123456789" value={profile.telegram_profile} />
            </Field>
            <Button disabled={saving} loading={saving} type="submit">Save Profile</Button>
          </form>
        </Card>

        <Card>
          <h2 className="mb-4 text-lg font-semibold tracking-tight text-slate-950">Change Password</h2>
          <form className="space-y-4" onSubmit={submitPassword}>
            <Field error={errors.current_password?.[0]} label="Current Password"><Input name="current_password" onChange={updatePasswordField} type="password" value={password.current_password} /></Field>
            <Field error={errors.password?.[0]} label="New Password"><Input name="password" onChange={updatePasswordField} type="password" value={password.password} /></Field>
            <Field error={errors.password_confirmation?.[0]} label="Confirm New Password"><Input name="password_confirmation" onChange={updatePasswordField} type="password" value={password.password_confirmation} /></Field>
            <Button type="submit" variant="secondary">Change Password</Button>
          </form>
        </Card>
      </div>
    </AppShell>
  )
}
