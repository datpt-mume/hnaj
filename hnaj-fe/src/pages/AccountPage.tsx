import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function AccountPage() {
  const { user, signOut } = useAuth()

  if (!user) return null

  return (
    <main className="account-shell">
      <nav className="home-nav" aria-label="Điều hướng tài khoản">
        <Link className="wordmark" to="/">HNAJ</Link>
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
        <Link className="button button--secondary button--link" to="/">
          Về trang khám phá
        </Link>
      </section>
    </main>
  )
}
