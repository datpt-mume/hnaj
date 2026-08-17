import { Navigate, Route, Routes } from 'react-router-dom'
import { RequireAuth } from '../components/RequireAuth'
import { RequireRole } from '../components/RequireRole'
import { AccountPage } from '../pages/AccountPage'
import { AdminDashboardPage } from '../pages/AdminDashboardPage'
import { AdminLoginPage } from '../pages/AdminLoginPage'
import { AdminManagerApplicationsPage } from '../pages/AdminManagerApplicationsPage'
import { AdminPlaceEditPage } from '../pages/AdminPlaceEditPage'
import { AdminPlacesPage } from '../pages/AdminPlacesPage'
import { AdminPlaceVerificationPage } from '../pages/AdminPlaceVerificationPage'
import { BookmarksPage } from '../pages/BookmarksPage'
import { GoogleCallbackPage } from '../pages/GoogleCallbackPage'
import { HomePage } from '../pages/HomePage'
import { SearchPage } from '../pages/SearchPage'
import { PlaceDetailsPage } from '../pages/PlaceDetailsPage'
import { LoginPage } from '../pages/LoginPage'
import { RegisterPage } from '../pages/RegisterPage'
import { SetupAccountPage } from '../pages/SetupAccountPage'
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
      <Route path="/setup-account" element={<SetupAccountPage />} />
      <Route path="/auth/google/callback" element={<GoogleCallbackPage />} />
      <Route path="/admin/login" element={<AdminLoginPage />} />

      <Route element={<RequireAuth />}>
        <Route path="/account" element={<AccountPage />} />
        <Route path="/bookmarks" element={<BookmarksPage />} />
      </Route>

      <Route element={<RequireRole role="admin" />}>
        <Route path="/admin" element={<AdminDashboardPage />} />
        <Route path="/admin/places" element={<AdminPlacesPage />} />
        <Route path="/admin/places/:placeId/edit" element={<AdminPlaceEditPage />} />
        <Route path="/admin/places/verification" element={<AdminPlaceVerificationPage />} />
        <Route path="/admin/manager-applications" element={<AdminManagerApplicationsPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
