import { Link, useNavigate } from 'react-router-dom'
import { AuthLoginForm } from '../components/AuthLoginForm'
import { AuthShell } from '../components/AuthShell'
import { useAuth } from '../hooks/useAuth'
import { ApiRequestError } from '../services/httpClient'
import { useState } from 'react'

export function AdminLoginPage() {
  const { signInAdmin } = useAuth()
  const navigate = useNavigate()
  const [error, setError] = useState('')

  async function handleSubmit(username: string, password: string) {
    setError('')
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
      throw requestError
    }
  }

  return (
    <AuthShell title="Đăng nhập admin" description="Khu vực này chỉ dành cho tài khoản có role admin." admin>
      <AuthLoginForm
        admin
        submitLabel="Vào khu vực quản trị"
        submittingLabel="Đang kiểm tra…"
        usernameLabel="Tên tài khoản admin"
        error={error}
        onSubmit={handleSubmit}
      />
      <p className="auth-switch"><Link to="/login">Quay lại đăng nhập người dùng</Link></p>
    </AuthShell>
  )
}
