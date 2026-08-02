import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import type { UserRole } from '../services/authService'

export function RequireRole({ role }: { role: UserRole }) {
  const { adminUser, isAdminLoading } = useAuth()

  if (isAdminLoading) {
    return <div className="route-loader" aria-label="Đang tải quyền quản trị" />
  }

  if (!adminUser || !adminUser.roles.includes(role)) {
    return <Navigate to="/admin/login" replace />
  }

  return <Outlet />
}
