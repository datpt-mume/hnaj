import { Navigate, Route, Routes } from 'react-router-dom'
import { RequireAuth } from '../components/RequireAuth'
import { RequireRole } from '../components/RequireRole'
import { AccountPage } from '../pages/AccountPage'
import { AdminDashboardPage } from '../pages/AdminDashboardPage'
import { AdminLoginPage } from '../pages/AdminLoginPage'
import { AdminPlaceVerificationPage } from '../pages/AdminPlaceVerificationPage'
import { GoogleCallbackPage } from '../pages/GoogleCallbackPage'
import { HomePage } from '../pages/HomePage'
import { SearchPage } from '../pages/SearchPage'
import { PlaceDetailsPage } from '../pages/PlaceDetailsPage'
import { LoginPage } from '../pages/LoginPage'
import { RegisterPage } from '../pages/RegisterPage'
import { VerifyEmailPage } from '../pages/VerifyEmailPage'

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/search" element={<SearchPage />} />
      <Route path="/places/:placeId" element={<PlaceDetailsPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/verify-email" element={<VerifyEmailPage />} />
      <Route path="/auth/google/callback" element={<GoogleCallbackPage />} />
      <Route path="/admin/login" element={<AdminLoginPage />} />

      <Route element={<RequireAuth />}>
        <Route path="/account" element={<AccountPage />} />
      </Route>

      <Route element={<RequireRole role="admin" />}>
        <Route path="/admin" element={<AdminDashboardPage />} />
        <Route path="/admin/places/verification" element={<AdminPlaceVerificationPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
