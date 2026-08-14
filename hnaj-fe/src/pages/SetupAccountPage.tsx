import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { FormField } from '../components/FormField'
import { completeAccountSetup } from '../services/authService'
import { ApiRequestError } from '../services/httpClient'

export function SetupAccountPage() {
  const [searchParams] = useSearchParams()
  const tokenFromUrl = searchParams.get('token') ?? ''
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [status, setStatus] = useState<'idle' | 'pending' | 'success' | 'error'>('idle')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!tokenFromUrl) {
      setStatus('error')
      setMessage('Liên kết kích hoạt thiếu token. Vui lòng mở lại từ email.')
    }
  }, [tokenFromUrl])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setMessage('')
    setStatus('pending')

    try {
      await completeAccountSetup(tokenFromUrl, password, passwordConfirmation)
      setStatus('success')
      setMessage('Tài khoản đã được kích hoạt. Bạn có thể đăng nhập ngay.')
    } catch (error) {
      setStatus('error')
      if (error instanceof ApiRequestError && error.status === 429) {
        setMessage('Bạn đã thử quá nhiều lần. Vui lòng chờ một lúc rồi thử lại.')
      } else if (error instanceof ApiRequestError && error.status === 422) {
        setMessage('Liên kết kích hoạt không hợp lệ, đã hết hạn hoặc đã được sử dụng.')
      } else {
        setMessage('Không thể kích hoạt tài khoản lúc này. Vui lòng thử lại sau.')
      }
    }
  }

  if (status === 'success') {
    return (
      <AuthShell title="Đã kích hoạt" description="Tài khoản quản lý địa điểm đã sẵn sàng.">
        <div className="auth-success" role="status" aria-live="polite">
          <span className="auth-success__mark" aria-hidden="true">✓</span>
          <h3>{message}</h3>
          <p>Đăng nhập để vào trang người dùng và khu vực quản lý của bạn.</p>
        </div>
        <Link className="button button--primary button--link" to="/login">
          Đăng nhập
        </Link>
      </AuthShell>
    )
  }

  return (
    <AuthShell
      title="Kích hoạt tài khoản"
      description="Đặt mật khẩu mới để kích hoạt tài khoản quản lý địa điểm."
    >
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        <FormField
          id="setup-password"
          name="password"
          label="Mật khẩu mới"
          type="password"
          autoComplete="new-password"
          minLength={8}
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          error={status === 'error' ? message : undefined}
          helper="Tối thiểu 8 ký tự."
          required
        />
        <FormField
          id="setup-password-confirmation"
          name="password_confirmation"
          label="Nhập lại mật khẩu"
          type="password"
          autoComplete="new-password"
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          required
        />
        <button
          className="button button--primary"
          type="submit"
          disabled={status === 'pending' || !tokenFromUrl || password.length < 8 || password !== passwordConfirmation}
        >
          {status === 'pending' ? 'Đang kích hoạt…' : 'Kích hoạt tài khoản'}
        </button>
      </form>

      <p className="auth-switch">
        Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
      </p>
    </AuthShell>
  )
}
