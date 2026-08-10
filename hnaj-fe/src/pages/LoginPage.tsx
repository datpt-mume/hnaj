import { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { AuthLoginForm } from '../components/AuthLoginForm'
import { AuthShell } from '../components/AuthShell'
import { useAuth } from '../hooks/useAuth'
import { ApiRequestError } from '../services/httpClient'
import { clearAuthReturnPath, getAuthReturnPath } from '../services/authReturnPath'

function loginMessage(error: unknown): string {
  if (!(error instanceof ApiRequestError)) return 'Không thể kết nối tới máy chủ. Hãy thử lại.'
  if (error.code === 'EMAIL_NOT_VERIFIED') return 'Email chưa được xác thực. Hãy mở liên kết trong email trước khi đăng nhập.'
  if (error.code === 'ACCOUNT_NOT_ACTIVE') return 'Tài khoản hiện không hoạt động. Vui lòng liên hệ quản trị viên.'
  if (error.code === 'INVALID_CREDENTIALS') return 'Tên tài khoản hoặc mật khẩu chưa đúng. Hãy kiểm tra và thử lại.'
  return error.message
}

export function LoginPage() {
  const { signIn } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [error, setError] = useState('')

  async function handleSubmit(username: string, password: string) {
    setError('')
    try {
      await signIn(username, password)
      const destination = getAuthReturnPath() ?? (location.state as { from?: string } | null)?.from ?? '/account'
      clearAuthReturnPath()
      navigate(destination, { replace: true })
    } catch (requestError) {
      const message = loginMessage(requestError)
      setError(message)
      throw requestError
    }
  }

  return (
    <AuthShell title="Đăng nhập" description="Dùng tên tài khoản và mật khẩu đã đăng ký.">
      <AuthLoginForm
        submitLabel="Đăng nhập"
        submittingLabel="Đang đăng nhập…"
        usernameLabel="Tên tài khoản"
        usernameHelper="Ví dụ: minh.anh"
        error={error}
        onSubmit={handleSubmit}
        onGoogleError={setError}
      />
      <p className="auth-switch">Chưa có tài khoản? <Link to="/register">Tạo tài khoản</Link></p>
      <p className="auth-secondary-link">Chưa nhận được email? <Link to="/verify-email">Gửi lại liên kết</Link></p>
    </AuthShell>
  )
}
