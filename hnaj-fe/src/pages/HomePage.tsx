import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function HomePage() {
  const { user, signOut } = useAuth()

  return (
    <main className="home-shell">
      <nav className="home-nav" aria-label="Điều hướng chính">
        <Link className="wordmark" to="/">HNAJ</Link>
        <div className="home-nav__links">
          {user ? (
            <>
              <Link to="/account">Tài khoản</Link>
              <button type="button" className="text-button" onClick={() => void signOut()}>
                Đăng xuất
              </button>
            </>
          ) : (
            <>
              <Link to="/login">Đăng nhập</Link>
              <Link className="nav-cta" to="/register">Tạo tài khoản</Link>
            </>
          )}
        </div>
      </nav>

      <section className="home-hero">
        <p className="home-hero__kicker">Hôm nay ăn gì?</p>
        <h1>Chọn một nơi hợp mood hôm nay.</h1>
        <p>
          HNAJ giúp bạn tìm một địa điểm cụ thể để đi ngay, thay vì mở thêm một tab tìm kiếm.
        </p>
        <Link className="button button--primary button--link" to={user ? '/account' : '/register'}>
          {user ? 'Mở tài khoản' : 'Bắt đầu'}
        </Link>
      </section>

      <section className="home-note" aria-label="Trạng thái tài khoản">
        <span className="home-note__line" />
        <p>
          {user
            ? `Bạn đang đăng nhập với tên tài khoản ${user.username}.`
            : 'Đăng nhập để lưu bookmark và giữ lại lịch sử những nơi bạn đã chọn.'}
        </p>
      </section>
    </main>
  )
}
