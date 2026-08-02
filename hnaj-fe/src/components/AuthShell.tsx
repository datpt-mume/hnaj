import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import heroImage from '../assets/hero.png'

type AuthShellProps = {
  title: string
  description: string
  children: ReactNode
  admin?: boolean
}

export function AuthShell({
  title,
  description,
  children,
  admin = false,
}: AuthShellProps) {
  return (
    <main className={`auth-shell${admin ? ' auth-shell--admin' : ''}`}>
      <section className="auth-brand" aria-label="Giới thiệu HNAJ">
        <Link className="wordmark" to="/" aria-label="HNAJ - Trang chủ">
          HNAJ
        </Link>

        <div className="auth-brand__content">
          <p className="auth-brand__kicker">
            {admin ? 'Khu vực quản trị' : 'Hôm nay ăn gì'}
          </p>
          <h1>{admin ? 'Quản trị rõ quyền, đúng phạm vi.' : 'Bớt phân vân. Đi ăn thôi.'}</h1>
          <p>
            {admin
              ? 'Đăng nhập bằng tài khoản admin được tạo nội bộ. Mọi quyền được kiểm tra lại ở backend.'
              : 'Đăng nhập để lưu địa điểm, xem lịch sử và chia sẻ trải nghiệm của bạn.'}
          </p>
        </div>

        <div className="auth-brand__art" aria-hidden="true">
          <span className="auth-brand__orbit" />
          <img src={heroImage} alt="" width="343" height="361" />
        </div>
      </section>

      <section className="auth-panel" aria-labelledby="auth-title">
        <div className="auth-panel__inner">
          <header className="auth-panel__header">
            <h2 id="auth-title">{title}</h2>
            <p>{description}</p>
          </header>
          {children}
        </div>
      </section>
    </main>
  )
}
