import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { FormField } from '../components/FormField'
import { useAuth } from '../hooks/useAuth'
import { getApiErrorMessage } from '../services/httpClient'

export function AccountPage() {
  const { user, signOut, updateProfile } = useAuth()

  const [fullName, setFullName] = useState(user?.full_name ?? '')
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<boolean>(false)

  useEffect(() => {
    setFullName(user?.full_name ?? '')
  }, [user?.full_name])

  if (!user) return null

  async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    const trimmed = fullName.trim()

    if (!trimmed) {
      setError('Vui lòng nhập tên của bạn.')
      setSuccess(false)
      return
    }

    setIsSaving(true)
    setError(null)
    setSuccess(false)

    try {
      await updateProfile(trimmed)
      setSuccess(true)
    } catch (cause) {
      setError(getApiErrorMessage(cause, 'Không thể cập nhật tên. Vui lòng thử lại.'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <main className="account-shell">
      <nav className="home-nav" aria-label="Điều hướng tài khoản">
        <Link className="wordmark" to="/" aria-label="HNAJ - Trang chủ">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <button className="text-button" type="button" onClick={() => void signOut()}>
          Đăng xuất
        </button>
      </nav>
      <section className="account-card" aria-labelledby="account-title">
        <p className="home-hero__kicker">Tài khoản của bạn</p>
        <h1 id="account-title">Xin chào, {user.full_name}.</h1>
        <p className="account-card__lead">
          Tài khoản đã xác thực và sẵn sàng cho các tính năng cá nhân của HNAJ.
        </p>
        <dl className="account-details">
          <div><dt>Tên tài khoản</dt><dd>{user.username}</dd></div>
          <div><dt>Email</dt><dd>{user.email}</dd></div>
          <div><dt>Role</dt><dd>{user.roles.join(', ')}</dd></div>
        </dl>

        <div className="account-form">
          <h2 className="account-form__title">Đổi tên hiển thị</h2>
          <form onSubmit={(event) => void handleSubmit(event)} noValidate>
            <FormField
              id="full-name"
              label="Họ và tên"
              type="text"
              autoComplete="name"
              value={fullName}
              required
              disabled={isSaving}
              onChange={(event) => {
                setFullName(event.target.value)
                setSuccess(false)
              }}
              error={error ?? undefined}
            />
            <div className="account-form__actions">
              <button className="button button--primary" type="submit" disabled={isSaving}>
                {isSaving ? 'Đang lưu…' : 'Lưu thay đổi'}
              </button>
            </div>
            {success && (
              <p className="form-success" role="status">
                Đã lưu tên hiển thị mới.
              </p>
            )}
          </form>
        </div>

        <Link className="button button--secondary button--link" to="/">
          Về trang khám phá
        </Link>
      </section>
    </main>
  )
}
