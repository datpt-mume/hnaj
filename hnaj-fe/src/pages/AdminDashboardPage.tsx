import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function AdminDashboardPage() {
  const { adminUser, signOutAdmin } = useAuth()

  if (!adminUser) return null

  return (
    <main className="admin-shell">
      <nav className="home-nav" aria-label="Điều hướng quản trị">
        <Link className="wordmark" to="/admin" aria-label="HNAJ - Quản trị">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <button className="text-button" type="button" onClick={() => void signOutAdmin()}>
          Đăng xuất admin
        </button>
      </nav>
      <section className="admin-card" aria-labelledby="admin-title">
        <p className="home-hero__kicker">Khu vực quản trị</p>
        <h1 id="admin-title">Chào {adminUser.full_name}.</h1>
        <p>
          Phiên admin đang hoạt động. Các màn hình quản lý place, user và moderation sẽ nối vào đây.
        </p>
        <div className="admin-role-list">
          {adminUser.roles.map((role) => <span key={role}>{role}</span>)}
        </div>
        <Link className="button button--secondary button--link" to="/">
          Về trang chính
        </Link>
      </section>
    </main>
  )
}
