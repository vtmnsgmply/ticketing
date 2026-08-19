import { Outlet } from 'react-router-dom'
import AppShell from '../../components/layout/AppShell'

export default function AdminLayout() {
  return (
    <AppShell width="none">
      <Outlet />
    </AppShell>
  )
}
