import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { FormField } from '../components/FormField'
import { useAuth } from '../hooks/useAuth'
import { getGoogleAuthorizationUrl } from '../services/authService'
import { ApiRequestError } from '../services/httpClient'

function loginMessage(error: unknown): string {
  if (!(error instanceof ApiRequestError)) {
    return 'Không thể kết nối tới máy chủ. Hãy thử lại.'
  }

  if (error.code === 'EMAIL_NOT_VERIFIED') {
    return 'Email chưa được xác thực. Hãy mở liên kết trong email trước khi đăng nhập.'
  }

  if (error.code === 'ACCOUNT_NOT_ACTIVE') {
    return 'Tài khoản hiện không hoạt động. Vui lòng liên hệ quản trị viên.'
  }

  if (error.code === 'INVALID_CREDENTIALS') {
    return 'Tên tài khoản hoặc mật khẩu chưa đúng. Hãy kiểm tra và thử lại.'
  }

  return error.message
}

export function LoginPage() {
  const { signIn } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isGoogleLoading, setIsGoogleLoading] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setIsSubmitting(true)

    try {
      await signIn(username, password)
      const destination = (location.state as { from?: string } | null)?.from ?? '/account'
      navigate(destination, { replace: true })
    } catch (requestError) {
      setError(loginMessage(requestError))
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleGoogleLogin() {
    setError('')
    setIsGoogleLoading(true)

    try {
      const response = await getGoogleAuthorizationUrl()
      window.location.assign(response.data.authorization_url)
    } catch (requestError) {
      setError(loginMessage(requestError))
      setIsGoogleLoading(false)
    }
  }

  return (
    <AuthShell
      title="Đăng nhập"
      description="Dùng tên tài khoản và mật khẩu đã đăng ký."
    >
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        <FormField
          id="username"
          name="username"
          label="Tên tài khoản"
          autoComplete="username"
          value={username}
          onChange={(event) => setUsername(event.target.value)}
          helper="Ví dụ: minh.anh"
          required
        />
        <FormField
          id="password"
          name="password"
          label="Mật khẩu"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />

        <div className="form-feedback" aria-live="polite">
          {error}
        </div>

        <button className="button button--primary" type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Đang đăng nhập…' : 'Đăng nhập'}
        </button>

        <div className="auth-separator" aria-hidden="true">
          <span>hoặc</span>
        </div>

        <button
          className="button button--google"
          type="button"
          disabled={isGoogleLoading}
          onClick={() => void handleGoogleLogin()}
        >
          <span className="google-mark" aria-hidden="true">G</span>
          {isGoogleLoading ? 'Đang chuyển tới Google…' : 'Tiếp tục với Google'}
        </button>
      </form>

      <p className="auth-switch">
        Chưa có tài khoản? <Link to="/register">Tạo tài khoản</Link>
      </p>
      <p className="auth-secondary-link">
        Chưa nhận được email? <Link to="/verify-email">Gửi lại liên kết</Link>
      </p>
    </AuthShell>
  )
}
