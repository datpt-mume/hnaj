import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { FormField } from '../components/FormField'
import { useAuth } from '../hooks/useAuth'
import { ApiRequestError } from '../services/httpClient'

export function AdminLoginPage() {
  const { signInAdmin } = useAuth()
  const navigate = useNavigate()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setIsSubmitting(true)

    try {
      await signInAdmin(username, password)
      navigate('/admin', { replace: true })
    } catch (requestError) {
      if (requestError instanceof ApiRequestError && requestError.code === 'FORBIDDEN_ROLE') {
        setError('Tài khoản này không có quyền admin.')
      } else if (requestError instanceof ApiRequestError && requestError.code === 'INVALID_CREDENTIALS') {
        setError('Tên tài khoản hoặc mật khẩu admin chưa đúng.')
      } else if (requestError instanceof ApiRequestError) {
        setError(requestError.message)
      } else {
        setError('Không thể đăng nhập khu vực quản trị lúc này.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthShell
      title="Đăng nhập admin"
      description="Khu vực này chỉ dành cho tài khoản có role admin."
      admin
    >
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        <FormField
          id="admin-username"
          name="username"
          label="Tên tài khoản admin"
          autoComplete="username"
          value={username}
          onChange={(event) => setUsername(event.target.value)}
          required
        />
        <FormField
          id="admin-password"
          name="password"
          label="Mật khẩu"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
        <div className="form-feedback" aria-live="polite">{error}</div>
        <button className="button button--primary" type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Đang kiểm tra…' : 'Vào khu vực quản trị'}
        </button>
      </form>
      <p className="auth-switch">
        <Link to="/login">Quay lại đăng nhập người dùng</Link>
      </p>
    </AuthShell>
  )
}
