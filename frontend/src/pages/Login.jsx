import { useState } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { Eye, EyeOff, ShieldCheck } from 'lucide-react'
import { useAuth } from '../hooks/useAuth'
import { showError, showSuccess } from '../utils/alerts'
import Button from '../components/ui/Button'
import Field from '../components/ui/Field'
import Input from '../components/ui/Input'
import VantaBirdsBackground from '../components/VantaBirdsBackground'

const highlights = [
  'Centralized ticket routing across every department',
  'SLA tracking that keeps response times accountable',
  'Full audit trail for every action taken on a ticket',
]

export default function Login() {
  const { login, isAuthenticated, loading } = useAuth()
  const [form, setForm] = useState({ email: '', password: '' })
  const [showPassword, setShowPassword] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [errors, setErrors] = useState({})
  const navigate = useNavigate()
  const location = useLocation()
  const from = location.state?.from?.pathname ?? '/dashboard'

  if (!loading && isAuthenticated) {
    return <Navigate to="/dashboard" replace />
  }

  function updateField(event) {
    setForm((current) => ({
      ...current,
      [event.target.name]: event.target.value,
    }))
  }

  async function submit(event) {
    event.preventDefault()
    setSubmitting(true)
    setErrors({})

    try {
      await login(form)
      await showSuccess('You are now signed in.', 'Login successful')
      navigate(from, { replace: true })
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to sign in.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="min-h-screen bg-slate-50 lg:grid lg:grid-cols-2">
      <section className="relative hidden flex-col justify-between overflow-hidden bg-slate-950 p-12 text-white lg:flex">
        <VantaBirdsBackground className="absolute inset-0" />
        <div className="relative z-10 flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold">TS</span>
          <span className="text-lg font-semibold">Ticketing System</span>
        </div>
        <div className="relative z-10 max-w-md">
          <h2 className="text-3xl font-bold tracking-tight">Operational clarity for every support request.</h2>
          <ul className="mt-8 space-y-4">
            {highlights.map((item) => (
              <li className="flex items-start gap-3 text-sm text-slate-300" key={item}>
                <ShieldCheck aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0 text-indigo-400" />
                {item}
              </li>
            ))}
          </ul>
        </div>
        <p className="relative z-10 text-xs text-slate-500">&copy; {new Date().getFullYear()} Ticketing System. All rights reserved.</p>
      </section>

      <section className="flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div className="w-full max-w-md">
          <div className="mb-8 flex items-center gap-3 lg:hidden">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">TS</span>
            <span className="text-lg font-semibold text-slate-900">Ticketing System</span>
          </div>

          <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Sign in</h1>
          <p className="mt-1.5 text-sm text-slate-500">Sign in to manage support requests.</p>

          <form className="mt-8 space-y-5" noValidate onSubmit={submit}>
            <Field error={errors.email?.[0]} htmlFor="login-email" label="Email">
              <Input
                autoComplete="email"
                id="login-email"
                invalid={Boolean(errors.email)}
                name="email"
                onChange={updateField}
                type="email"
                value={form.email}
              />
            </Field>

            <Field error={errors.password?.[0]} htmlFor="login-password" label="Password">
              <div className="relative">
                <Input
                  autoComplete="current-password"
                  className="pr-11"
                  id="login-password"
                  invalid={Boolean(errors.password)}
                  name="password"
                  onChange={updateField}
                  type={showPassword ? 'text' : 'password'}
                  value={form.password}
                />
                <button
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  className="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  onClick={() => setShowPassword((value) => !value)}
                  type="button"
                >
                  {showPassword ? <EyeOff aria-hidden="true" className="h-4 w-4" /> : <Eye aria-hidden="true" className="h-4 w-4" />}
                </button>
              </div>
            </Field>

            <Button className="w-full" loading={submitting} type="submit">
              {submitting ? 'Signing in...' : 'Sign in'}
            </Button>
          </form>
        </div>
      </section>
    </main>
  )
}
