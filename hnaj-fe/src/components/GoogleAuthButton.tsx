import { useState } from 'react'
import { useLocation } from 'react-router-dom'
import { getGoogleAuthorizationUrl } from '../services/authService'
import { ApiRequestError } from '../services/httpClient'
import { setAuthReturnPath } from '../services/authReturnPath'

type GoogleAuthButtonProps = {
  onError: (message: string) => void
}

export function GoogleAuthButton({ onError }: GoogleAuthButtonProps) {
  const location = useLocation()
  const [isLoading, setIsLoading] = useState(false)

  async function handleGoogleLogin() {
    onError('')
    setIsLoading(true)

    try {
      setAuthReturnPath(location.pathname + location.search)
      const response = await getGoogleAuthorizationUrl()
      window.location.assign(response.data.authorization_url)
    } catch (requestError) {
      onError(
        requestError instanceof ApiRequestError
          ? requestError.message
          : 'Không thể kết nối tới máy chủ. Hãy thử lại.',
      )
      setIsLoading(false)
    }
  }

  return (
    <button
      className="button button--google"
      type="button"
      disabled={isLoading}
      onClick={() => void handleGoogleLogin()}
    >
      <span className="google-mark" aria-hidden="true">G</span>
      {isLoading ? 'Đang chuyển tới Google…' : 'Tiếp tục với Google'}
    </button>
  )
}