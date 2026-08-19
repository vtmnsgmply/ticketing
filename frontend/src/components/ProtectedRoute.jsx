import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useLoadingProgress } from '../hooks/useLoadingProgress'
import TicketLoadingProgress from './ui/TicketLoadingProgress'

export default function ProtectedRoute({ children, roles = [] }) {
  const { user, loading, isAuthenticated } = useAuth()
  const { failed, progress, status, tasks } = useLoadingProgress()
  const location = useLocation()
  const authFailed = failed && tasks.some((task) => task.id === 'auth' && task.status === 'failed')

  if (loading || authFailed) {
    return (
      <TicketLoadingProgress
        error={authFailed}
        onRetry={() => window.location.reload()}
        onSignInAgain={() => {
          window.localStorage.removeItem('ticketing_auth_token')
          window.location.assign('/login')
        }}
        progress={progress}
        status={status}
        tasks={tasks}
      />
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location }} />
  }

  if (roles.length > 0 && !roles.includes(user.role?.slug)) {
    return <Navigate to="/dashboard" replace />
  }

  return children
}
