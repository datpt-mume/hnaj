import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { FormField } from '../components/FormField'
import { resendVerification, verifyEmail } from '../services/authService'
import { ApiRequestError } from '../services/httpClient'
import { verifyEmailOnce } from '../services/inFlightAuthRequests'

export function VerifyEmailPage() {
  const [searchParams] = useSearchParams()
  const tokenFromUrl = searchParams.get('token') ?? ''
  const [token, setToken] = useState(tokenFromUrl)
  const [email, setEmail] = useState('')
  const [isVerifying, setIsVerifying] = useState(Boolean(tokenFromUrl))
  const [isResending, setIsResending] = useState(false)
  const [status, setStatus] = useState<'idle' | 'pending' | 'success' | 'error'>(
    tokenFromUrl ? 'pending' : 'idle',
  )
  const [resendMessage, setResendMessage] = useState('')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!tokenFromUrl) return

    async function verify() {
      try {
        await verifyEmailOnce(tokenFromUrl)
        setStatus('success')
        setMessage('Email đã được xác thực. Bạn có thể đăng nhập ngay.')
      } catch (error) {
        setStatus('error')
        setMessage(error instanceof ApiRequestError ? error.message : 'Liên kết xác thực không dùng được.')
      } finally {
        setIsVerifying(false)
      }
    }

    void verify()
  }, [tokenFromUrl])

  async function handleManualVerify(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setMessage('')
    setResendMessage('')
    setStatus('pending')
    setIsVerifying(true)

    try {
      await verifyEmail(token)
      setStatus('success')
      setMessage('Email đã được xác thực. Bạn có thể đăng nhập ngay.')
    } catch (error) {
      setStatus('error')
      setMessage(error instanceof ApiRequestError ? error.message : 'Liên kết xác thực không dùng được.')
    } finally {
      setIsVerifying(false)
    }
  }

  async function handleResend(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setResendMessage('')
    setIsResending(true)

    try {
      await resendVerification(email)
      setStatus((current) => (current === 'error' ? 'idle' : current))
      setResendMessage('Nếu email cần xác thực, liên kết mới đã được gửi.')
    } catch (error) {
      setStatus('error')
      setMessage(error instanceof ApiRequestError ? error.message : 'Không thể gửi lại email lúc này.')
    } finally {
      setIsResending(false)
    }
  }

  if (status === 'success') {
    return (
      <AuthShell title="Đã xác thực" description="Tài khoản đã hoàn tất bước đăng ký.">
        <div className="auth-success" role="status" aria-live="polite">
          <span className="auth-success__mark" aria-hidden="true">✓</span>
          <h3>{message}</h3>
          <p>Đăng nhập để bắt đầu lưu lại những nơi bạn muốn quay lại.</p>
        </div>
        <Link className="button button--primary button--link" to="/login">
          Đăng nhập
        </Link>
      </AuthShell>
    )
  }

  return (
    <AuthShell
      title="Xác thực email"
      description="Dán token từ email hoặc yêu cầu gửi lại liên kết."
    >
      <form className="auth-form" onSubmit={handleManualVerify} noValidate>
        <FormField
          id="verification-token"
          name="token"
          label="Token xác thực"
          value={token}
          onChange={(event) => setToken(event.target.value)}
          error={status === 'error' ? message : undefined}
          helper="Liên kết trong email sẽ tự điền token này."
          required
        />
        <button className="button button--primary" type="submit" disabled={isVerifying || !token}>
          {isVerifying ? 'Đang xác thực…' : 'Xác thực email'}
        </button>
      </form>

      <div className="auth-divider" />

      <form className="auth-form" onSubmit={handleResend} noValidate>
        <FormField
          id="resend-email"
          name="email"
          label="Email đã đăng ký"
          type="email"
          autoComplete="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
        <button className="button button--secondary" type="submit" disabled={isResending || !email}>
          {isResending ? 'Đang gửi…' : 'Gửi lại liên kết'}
        </button>
        {resendMessage ? (
          <p className="auth-inline-feedback" role="status" aria-live="polite">
            {resendMessage}
          </p>
        ) : null}
      </form>

      <p className="auth-switch">
        Đã xác thực rồi? <Link to="/login">Đăng nhập</Link>
      </p>
    </AuthShell>
  )
}
