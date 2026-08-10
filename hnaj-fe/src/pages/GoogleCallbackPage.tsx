import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { clearAuthReturnPath, getAuthReturnPath } from '../services/authReturnPath'
import { useAuth } from '../hooks/useAuth'
import { ApiRequestError } from '../services/httpClient'
import { exchangeGoogleCodeOnce } from '../services/inFlightAuthRequests'

export function GoogleCallbackPage() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { acceptUserAuth } = useAuth()
  const [state, setState] = useState<'pending' | 'success' | 'error'>('pending')
  const [message, setMessage] = useState('Đang hoàn tất đăng nhập với Google…')

  useEffect(() => {
    const code = searchParams.get('code')
    const error = searchParams.get('error')

    if (error || !code) {
      setState('error')
      setMessage('Google chưa hoàn tất phiên đăng nhập. Hãy quay lại và thử lại.')
      return
    }

    const googleCode = code

    let isActive = true

    async function exchange() {
      try {
        const response = await exchangeGoogleCodeOnce(googleCode)
        if (!isActive) return
        setState('success')
        acceptUserAuth(response.data)
        const destination = getAuthReturnPath() ?? '/account'
        clearAuthReturnPath()
        navigate(destination, { replace: true })
      } catch (requestError) {
        if (!isActive) return
        setState('error')
        setMessage(
          requestError instanceof ApiRequestError
            ? requestError.message
            : 'Không thể hoàn tất đăng nhập với Google.',
        )
      }
    }

    void exchange()

    return () => {
      isActive = false
    }
  }, [acceptUserAuth, navigate, searchParams])

  return (
    <AuthShell title="Đang đăng nhập" description="Đang kiểm tra phiên Google của bạn.">
      <div className="auth-status" role="status" aria-live="polite">
        {state === 'pending' ? <span className="inline-loader" aria-hidden="true" /> : null}
        <p>{message}</p>
      </div>
      <Link className="button button--secondary button--link" to="/login">
        Về trang đăng nhập
      </Link>
    </AuthShell>
  )
}
