import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { RiAccountCircleFill, RiLogoutBoxLine } from 'react-icons/ri'
import { useAuth } from '../hooks/useAuth'
import { setAuthReturnPath } from '../services/authReturnPath'

function initialsOf(fullName: string): string {
  const parts = fullName.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

export function AuthNav() {
  const { user, isLoading, signOut } = useAuth()
  const navigate = useNavigate()
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const [isSigningOut, setIsSigningOut] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!isMenuOpen) return

    function onPointerDown(event: PointerEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setIsMenuOpen(false)
      }
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') setIsMenuOpen(false)
    }

    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [isMenuOpen])

  const handleSignOut = useCallback(async () => {
    setIsSigningOut(true)
    try {
      await signOut()
    } finally {
      setIsSigningOut(false)
      setIsMenuOpen(false)
      navigate('/')
    }
  }, [navigate, signOut])

  if (isLoading) {
    return (
      <div className="auth-nav" aria-hidden="true">
        <span className="auth-nav__skeleton" />
      </div>
    )
  }

  if (!user) {
    return (
      <div className="auth-nav">
        <Link
          className="button button--secondary auth-nav__login"
          to="/login"
          onClick={() => setAuthReturnPath(window.location.pathname + window.location.search)}
        >
          Đăng nhập
        </Link>
        <Link className="button button--primary auth-nav__register" to="/register">
          Đăng ký
        </Link>
      </div>
    )
  }

  return (
    <div className="auth-nav" ref={menuRef}>
      <button
        className="auth-nav__member"
        type="button"
        aria-haspopup="menu"
        aria-expanded={isMenuOpen}
        onClick={() => setIsMenuOpen((open) => !open)}
      >
        {user.avatar_url ? (
          <img className="auth-nav__avatar" src={user.avatar_url} alt="" />
        ) : (
          <span className="auth-nav__avatar auth-nav__avatar--fallback" aria-hidden="true">
            {initialsOf(user.full_name)}
          </span>
        )}
        <span className="auth-nav__name">{user.full_name.split(' ').slice(-1)[0]}</span>
      </button>

      {isMenuOpen ? (
        <div className="auth-nav__menu" role="menu" aria-label="Menu tài khoản">
          <Link
            className="auth-nav__menu-item"
            role="menuitem"
            to="/account"
            onClick={() => setIsMenuOpen(false)}
          >
            <RiAccountCircleFill aria-hidden="true" />
            Tài khoản
          </Link>
          <button
            className="auth-nav__menu-item"
            role="menuitem"
            type="button"
            disabled={isSigningOut}
            onClick={() => void handleSignOut()}
          >
            <RiLogoutBoxLine aria-hidden="true" />
            {isSigningOut ? 'Đang đăng xuất…' : 'Đăng xuất'}
          </button>
        </div>
      ) : null}
    </div>
  )
}