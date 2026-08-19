import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute'
import AppShell from './components/layout/AppShell'

const AdminLayout = lazy(() => import('./pages/admin/AdminLayout'))
const AdminDashboard = lazy(() => import('./pages/admin/AdminDashboard'))
const AuditLogs = lazy(() => import('./pages/admin/AuditLogs'))
const AuditLogDetail = lazy(() => import('./pages/admin/AuditLogDetail'))
const Categories = lazy(() => import('./pages/admin/Categories'))
const ConversationModeration = lazy(() => import('./pages/admin/ConversationModeration'))
const Departments = lazy(() => import('./pages/admin/Departments'))
const EmailSettings = lazy(() => import('./pages/admin/EmailSettings'))
const ModerationEvents = lazy(() => import('./pages/admin/ModerationEvents'))
const Priorities = lazy(() => import('./pages/admin/Priorities'))
const SLASettings = lazy(() => import('./pages/admin/SLASettings'))
const SystemSettings = lazy(() => import('./pages/admin/SystemSettings'))
const Users = lazy(() => import('./pages/admin/Users'))
const CustomerDashboard = lazy(() => import('./pages/customer/CustomerDashboard'))
const CustomerNotifications = lazy(() => import('./pages/customer/CustomerNotifications'))
const CustomerProfile = lazy(() => import('./pages/customer/CustomerProfile'))
const Dashboard = lazy(() => import('./pages/Dashboard'))
const Login = lazy(() => import('./pages/Login'))
const StaffDashboard = lazy(() => import('./pages/staff/StaffDashboard'))
const StaffNotifications = lazy(() => import('./pages/staff/StaffNotifications'))
const StaffProfile = lazy(() => import('./pages/staff/StaffProfile'))
const ManagerDashboard = lazy(() => import('./pages/manager/ManagerDashboard'))
const ManagerNotifications = lazy(() => import('./pages/manager/ManagerNotifications'))
const ManagerProfile = lazy(() => import('./pages/manager/ManagerProfile'))
const ManagerTeam = lazy(() => import('./pages/manager/ManagerTeam'))
const NotificationPreferences = lazy(() => import('./pages/notifications/NotificationPreferences'))
const ReportsOverview = lazy(() => import('./pages/reports/ReportsOverview'))
const TicketCreate = lazy(() => import('./pages/tickets/TicketCreate'))
const TicketDetail = lazy(() => import('./pages/tickets/TicketDetail'))
const TicketList = lazy(() => import('./pages/tickets/TicketList'))

function RouteFallback() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="h-10 w-10 animate-spin rounded-full border-2 border-slate-200 border-t-indigo-600" />
    </div>
  )
}

function App() {
  return (
    <Suspense fallback={<RouteFallback />}>
      <Routes>
        <Route element={<Login />} path="/login" />
        <Route
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
          path="/dashboard"
        />
        <Route
          element={
            <ProtectedRoute>
              <TicketList />
            </ProtectedRoute>
          }
          path="/tickets"
        />
        <Route
          element={
            <ProtectedRoute>
              <TicketCreate />
            </ProtectedRoute>
          }
          path="/tickets/create"
        />
        <Route
          element={
            <ProtectedRoute>
              <TicketDetail />
            </ProtectedRoute>
          }
          path="/tickets/:id"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <CustomerDashboard />
            </ProtectedRoute>
          }
          path="/customer/dashboard"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <TicketList />
            </ProtectedRoute>
          }
          path="/customer/tickets"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <TicketCreate />
            </ProtectedRoute>
          }
          path="/customer/tickets/create"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <TicketDetail />
            </ProtectedRoute>
          }
          path="/customer/tickets/:id"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <CustomerNotifications />
            </ProtectedRoute>
          }
          path="/customer/notifications"
        />
        <Route
          element={
            <ProtectedRoute roles={['customer']}>
              <CustomerProfile />
            </ProtectedRoute>
          }
          path="/customer/profile"
        />
        <Route
          element={
            <ProtectedRoute roles={['agent']}>
              <StaffDashboard />
            </ProtectedRoute>
          }
          path="/staff/dashboard"
        />
        <Route
          element={
            <ProtectedRoute roles={['agent']}>
              <TicketList />
            </ProtectedRoute>
          }
          path="/staff/tickets"
        />
        <Route
          element={
            <ProtectedRoute roles={['agent']}>
              <TicketDetail />
            </ProtectedRoute>
          }
          path="/staff/tickets/:id"
        />
        <Route
          element={
            <ProtectedRoute roles={['agent']}>
              <StaffNotifications />
            </ProtectedRoute>
          }
          path="/staff/notifications"
        />
        <Route
          element={
            <ProtectedRoute roles={['agent']}>
              <StaffProfile />
            </ProtectedRoute>
          }
          path="/staff/profile"
        />
        <Route
          element={
            <ProtectedRoute>
              <NotificationPreferences />
            </ProtectedRoute>
          }
          path="/notification-preferences"
        />
        <Route
          element={
            <ProtectedRoute roles={['manager']}>
              <ManagerDashboard />
            </ProtectedRoute>
          }
          path="/manager/dashboard"
        />
        <Route element={<ProtectedRoute roles={['manager']}><TicketList /></ProtectedRoute>} path="/manager/tickets" />
        <Route element={<ProtectedRoute roles={['manager']}><TicketDetail /></ProtectedRoute>} path="/manager/tickets/:id" />
        <Route element={<ProtectedRoute roles={['manager']}><ManagerTeam /></ProtectedRoute>} path="/manager/team" />
        <Route
          element={
            <ProtectedRoute roles={['manager']}>
              <AppShell width="none">
                <ModerationEvents />
              </AppShell>
            </ProtectedRoute>
          }
          path="/manager/moderation/events"
        />
        <Route
          element={
            <ProtectedRoute roles={['manager']}>
              <AppShell width="none">
                <ReportsOverview />
              </AppShell>
            </ProtectedRoute>
          }
          path="/manager/reports"
        />
        <Route element={<ProtectedRoute roles={['manager']}><ManagerNotifications /></ProtectedRoute>} path="/manager/notifications" />
        <Route element={<ProtectedRoute roles={['manager']}><ManagerProfile /></ProtectedRoute>} path="/manager/profile" />
        <Route
          element={
            <ProtectedRoute roles={['administrator']}>
              <AdminLayout />
            </ProtectedRoute>
          }
          path="/admin"
        >
          <Route element={<Navigate replace to="/admin/dashboard" />} index />
          <Route element={<AdminDashboard />} path="dashboard" />
          <Route element={<Users />} path="users" />
          <Route element={<Departments />} path="departments" />
          <Route element={<Categories />} path="categories" />
          <Route element={<Priorities />} path="priorities" />
          <Route element={<SLASettings />} path="sla" />
          <Route element={<ReportsOverview />} path="reports" />
          <Route element={<AuditLogs />} path="audit-logs" />
          <Route element={<AuditLogDetail />} path="audit-logs/:id" />
          <Route element={<ConversationModeration />} path="moderation" />
          <Route element={<ModerationEvents />} path="moderation/events" />
          <Route element={<SystemSettings />} path="settings/system" />
          <Route element={<EmailSettings />} path="settings/email" />
        </Route>
        <Route element={<Navigate replace to="/dashboard" />} path="*" />
      </Routes>
    </Suspense>
  )
}

export default App
