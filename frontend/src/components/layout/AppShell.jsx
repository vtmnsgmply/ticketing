import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { showError } from '../../utils/alerts'
import PageContainer from '../ui/PageContainer'
import NotificationToastStack from './NotificationToastStack'
import Sidebar from './Sidebar'
import Topbar from './Topbar'

export default function AppShell({ children, width = 'standard' }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [mobileNavOpen, setMobileNavOpen] = useState(false)

  async function handleLogout() {
    try {
      await logout()
      navigate('/login', { replace: true })
    } catch {
      await showError('Unable to complete logout. Please try again.')
    }
  }

  return (
    <div className="min-h-screen bg-linear-to-br from-slate-50 via-white to-emerald-50/30 lg:flex">
      <Sidebar mobileOpen={mobileNavOpen} onCloseMobile={() => setMobileNavOpen(false)} role={user?.role?.slug} />
      <div className="min-w-0 flex-1">
        <Topbar onLogout={handleLogout} onOpenMobileMenu={() => setMobileNavOpen(true)} user={user} />
        <main className="py-6">
          {/* width="none" hands horizontal control to the page — e.g. AdminLayout,
              whose nested routes each pick their own PageContainer tier */}
          {width === 'none' ? children : <PageContainer width={width}>{children}</PageContainer>}
        </main>
      </div>
      <NotificationToastStack />
    </div>
  )
}
