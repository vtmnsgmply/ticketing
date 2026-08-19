import { useEffect, useState } from 'react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, Field, Input, PageHeader } from '../../components/ui'
import { changeManagerPassword, getManagerProfile, updateManagerProfile } from '../../services/managerService'
import { showError, showSuccess } from '../../utils/alerts'

export default function ManagerProfile() {
  const [profile, setProfile] = useState({ name: '', email: '', phone: '' })
  const [password, setPassword] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})

  useEffect(() => {
    getManagerProfile().then((user) => setProfile({ name: user.name ?? '', email: user.email ?? '', phone: user.phone ?? '' })).catch((error) => showError(error.message || 'Unable to load profile.'))
  }, [])

  async function submitProfile(event) {
    event.preventDefault()
    setErrors({})
    try {
      const user = await updateManagerProfile(profile)
      setProfile({ name: user.name ?? '', email: user.email ?? '', phone: user.phone ?? '' })
      await showSuccess('Profile updated.')
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to update profile.')
    }
  }

  async function submitPassword(event) {
    event.preventDefault()
    setErrors({})
    try {
      await changeManagerPassword(password)
      setPassword({ current_password: '', password: '', password_confirmation: '' })
      await showSuccess('Password changed.')
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to change password.')
    }
  }

  return (
    <AppShell width="standard">
      <PageHeader eyebrow="Manager Workspace" title="Profile" />
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <h2 className="mb-4 text-lg font-semibold tracking-tight text-slate-950">Personal Details</h2>
          <form className="space-y-4" onSubmit={submitProfile}>
            <Field error={errors.name?.[0]} label="Name"><Input name="name" onChange={(event) => setProfile((current) => ({ ...current, name: event.target.value }))} value={profile.name} /></Field>
            <Field error={errors.email?.[0]} label="Email"><Input name="email" onChange={(event) => setProfile((current) => ({ ...current, email: event.target.value }))} type="email" value={profile.email} /></Field>
            <Field error={errors.phone?.[0]} label="Phone"><Input name="phone" onChange={(event) => setProfile((current) => ({ ...current, phone: event.target.value }))} value={profile.phone} /></Field>
            <Button type="submit">Save Profile</Button>
          </form>
        </Card>
        <Card>
          <h2 className="mb-4 text-lg font-semibold tracking-tight text-slate-950">Change Password</h2>
          <form className="space-y-4" onSubmit={submitPassword}>
            <Field error={errors.current_password?.[0]} label="Current Password"><Input name="current_password" onChange={(event) => setPassword((current) => ({ ...current, current_password: event.target.value }))} type="password" value={password.current_password} /></Field>
            <Field error={errors.password?.[0]} label="New Password"><Input name="password" onChange={(event) => setPassword((current) => ({ ...current, password: event.target.value }))} type="password" value={password.password} /></Field>
            <Field error={errors.password_confirmation?.[0]} label="Confirm New Password"><Input name="password_confirmation" onChange={(event) => setPassword((current) => ({ ...current, password_confirmation: event.target.value }))} type="password" value={password.password_confirmation} /></Field>
            <Button type="submit" variant="secondary">Change Password</Button>
          </form>
        </Card>
      </div>
    </AppShell>
  )
}
