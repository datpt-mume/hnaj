import { RiAccountCircleLine, RiMapPin2Line } from 'react-icons/ri'
import { Link } from 'react-router-dom'

export function HomePage() {
  return (
    <main className="home-shell" aria-label="Trang chủ HNAJ">
      <nav className="home-nav" aria-label="Điều hướng chính">
        <Link className="wordmark" to="/" aria-label="Hôm nay ăn gì? - Trang chủ">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <Link className="home-location" to="/">
          <RiMapPin2Line aria-hidden="true" />
          <span>Hà Nội</span>
          <span className="home-location__chevron" aria-hidden="true">⌄</span>
        </Link>
        <div className="home-nav__links">
          <Link className="home-nav__link home-nav__link--active" to="/">
            Khám phá
          </Link>
          <Link className="home-nav__link" to="/">Điểm đến yêu thích</Link>
          <Link className="home-nav__link" to="/">Lịch sử</Link>
          <Link className="home-nav__link" to="/">Đề xuất địa điểm</Link>
        </div>
        <label className="home-search">
          <span className="sr-only">Tìm kiếm địa điểm</span>
          <input type="search" placeholder="Tìm kiếm địa điểm, món ăn..." />
        </label>
        <Link className="home-account" to="/login" aria-label="Mở tài khoản">
          <RiAccountCircleLine aria-hidden="true" />
          <span>Tài khoản</span>
        </Link>
      </nav>

      <section className="home-hero" aria-label="Khám phá Việt Nam">
        <img
          src="/hero_image.png"
          alt="Ẩm thực, cảnh quan và nét văn hóa Việt Nam"
          width="3090"
          height="1376"
        />
      </section>
    </main>
  )
}
