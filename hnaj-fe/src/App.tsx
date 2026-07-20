import { useCallback, useEffect, useId, useState } from 'react'
import type { CSSProperties, FormEvent } from 'react'
import './App.css'
import { authRequest, getCurrentUser, listImports, publicRequest, recommend, transitionImport, updateImport, uploadImport } from './api'
import type { AuthUser, Category, District, ImportBatchDetail, ImportHistory, Place, RecommendationData, Tag, UserLocation } from './types'

const HERO_OPTIONS = [
  {
    id: 'an-uong',
    label: 'Ăn uống',
    title: 'Ăn uống',
    description: 'Khám phá quán ăn theo khu vực, ngân sách và khẩu vị bạn chọn.',
    image:
      'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80',
    chip: 'Món ngon gần bạn',
  },
  {
    id: 'vui-choi',
    label: 'Vui chơi',
    title: 'Vui chơi',
    description: 'Tìm board game và điểm tụ họp phù hợp cho nhóm bạn hôm nay.',
    image: '/hero/board-game.jpg',
    chip: 'Board game · tụ họp',
  },
  {
    id: 'the-thao',
    label: 'Thể thao',
    title: 'Thể thao',
    description: 'Chọn nơi bắn cung và vận động theo vị trí, khoảng cách bạn muốn đi.',
    image: '/hero/archery.jpg',
    chip: 'Bắn cung · vận động',
  },
] as const

const HERO_ROTATE_MS = 4200

const CATEGORY_ACCENTS: Record<string, { hue: string; soft: string; deep: string; emoji: string }> = {
  'an-uong': { hue: '#c45c2a', soft: '#f6e7dc', deep: '#7a3514', emoji: '🍜' },
  'cafe-do-uong': { hue: '#a56a2d', soft: '#f3e8d8', deep: '#6b4216', emoji: '☕' },
  'bar-nightlife': { hue: '#5b4d6a', soft: '#ebe7ef', deep: '#342a40', emoji: '🍸' },
  'ngoai-troi': { hue: '#2f6b4f', soft: '#e2efe8', deep: '#1a4030', emoji: '🌿' },
  'gaming-giai-tri': { hue: '#3f5f7a', soft: '#e4ebf1', deep: '#243848', emoji: '🎮' },
  'van-hoa-nghe-thuat': { hue: '#8a4d5c', soft: '#f1e4e8', deep: '#532832', emoji: '🎭' },
  'suc-khoe-thu-gian': { hue: '#3d6b63', soft: '#e3efed', deep: '#24423d', emoji: '🧘' },
  'mua-sam': { hue: '#9a6b2f', soft: '#f2e9da', deep: '#5f4118', emoji: '🛍️' },
}

const BUDGET_OPTIONS = [
  { value: 'under-100', label: 'Dưới 100k' },
  { value: '100-300', label: '100–300k' },
  { value: '300-500', label: '300–500k' },
  { value: 'over-500', label: 'Trên 500k' },
] as const

const RADIUS_OPTIONS = [1, 3, 5, 10, 20] as const

function categoryAccent(slug?: string | null) {
  if (!slug) return { hue: '#2f6b4f', soft: '#e2efe8', deep: '#1a4030', emoji: '•' }
  return CATEGORY_ACCENTS[slug] ?? { hue: '#2f6b4f', soft: '#e2efe8', deep: '#1a4030', emoji: '•' }
}

function placeBackdrop(place: Place): { accent: ReturnType<typeof categoryAccent>; style: CSSProperties } {
  const accent = categoryAccent(place.category?.slug)
  return {
    accent,
    style: {
      ['--result-hue' as string]: accent.hue,
      ['--result-soft' as string]: accent.soft,
      ['--result-deep' as string]: accent.deep,
      ['--result-image' as string]: place.cover_image ? `url("${place.cover_image}")` : 'none',
    },
  }
}

function App() {
  const isAdminPage = window.location.pathname === '/admin' || window.location.pathname.startsWith('/admin/')
  const [radius, setRadius] = useState(3)
  const [budget, setBudget] = useState('100-300')
  const [categories, setCategories] = useState<Category[]>([])
  const [districts, setDistricts] = useState<District[]>([])
  const [availableTags, setAvailableTags] = useState<Tag[]>([])
  const [relatedTags, setRelatedTags] = useState<string[]>([])
  const [districtId, setDistrictId] = useState('')
  const [categorySlug, setCategorySlug] = useState('')
  const [selectedTags, setSelectedTags] = useState<string[]>([])
  const [gpsLocation, setGpsLocation] = useState<UserLocation | null>(null)
  const [locationStatus, setLocationStatus] = useState('Đang dùng tâm quận')
  const [optionsError, setOptionsError] = useState<string | null>(null)
  const [result, setResult] = useState<RecommendationData | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loginError, setLoginError] = useState<string | null>(null)
  const [isLoggingIn, setIsLoggingIn] = useState(false)
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login')
  const [isAccountOpen, setIsAccountOpen] = useState(false)
  const [importFile, setImportFile] = useState<File | null>(null)
  const [importBatch, setImportBatch] = useState<ImportBatchDetail | null>(null)
  const [importHistory, setImportHistory] = useState<ImportHistory | null>(null)
  const [importHistoryLoading, setImportHistoryLoading] = useState(false)
  const [importHistoryError, setImportHistoryError] = useState<string | null>(null)
  const [importError, setImportError] = useState<string | null>(null)
  const [isImporting, setIsImporting] = useState(false)
  const [heroOptionIndex, setHeroOptionIndex] = useState(0)
  const [isResultOpen, setIsResultOpen] = useState(false)
  const resultTitleId = useId()
  const selectedGroups = selectedTags
    .map((slug) => availableTags.find((tag) => tag.slug === slug)?.group)
    .filter((group): group is string => Boolean(group))
  const relatedTagOrder = relatedTags.length > 0
    ? relatedTags
    : availableTags.filter((tag) => selectedGroups.includes(tag.group) && !selectedTags.includes(tag.slug)).map((tag) => tag.slug)
  const activeHeroOption = HERO_OPTIONS[heroOptionIndex] ?? HERO_OPTIONS[0]
  const featuredPlace = result?.places[0] ?? null
  const selectedDistrict = districts.find((item) => item.id === districtId) ?? null
  const selectedCategory = categories.find((item) => item.slug === categorySlug) ?? null
  const selectedBudget = BUDGET_OPTIONS.find((item) => item.value === budget) ?? BUDGET_OPTIONS[1]
  const moodAccent = categoryAccent(selectedCategory?.slug ?? categorySlug)
  const featuredBackdrop = featuredPlace
    ? placeBackdrop(featuredPlace)
    : {
        accent: moodAccent,
        style: {
          ['--result-hue' as string]: moodAccent.hue,
          ['--result-soft' as string]: moodAccent.soft,
          ['--result-deep' as string]: moodAccent.deep,
          ['--result-image' as string]: 'none',
        } satisfies CSSProperties,
      }

  const refreshImportHistory = useCallback(async (page = 1) => {
    setImportHistoryLoading(true)
    setImportHistoryError(null)
    try { setImportHistory(await listImports(page)) } catch (requestError) { setImportHistoryError(requestError instanceof Error ? requestError.message : 'import.history_failed') } finally { setImportHistoryLoading(false) }
  }, [])

  useEffect(() => {
    void getCurrentUser().then((currentUser) => {
      setUser(currentUser)
      if (isAdminPage && currentUser?.roles.some((role) => role === 'admin' || role === 'editor')) void refreshImportHistory()
    })
  }, [isAdminPage, refreshImportHistory])

  useEffect(() => {
    if (!isAdminPage || !importBatch || !['queued', 'processing', 'pause_requested'].includes(importBatch.status)) return
    const timer = window.setInterval(() => {
      void updateImport(importBatch.id).then((batch) => { setImportBatch(batch); if (!['queued', 'processing', 'pause_requested'].includes(batch.status)) void refreshImportHistory() }).catch(() => setImportError('import.status_failed'))
    }, 2500)
    return () => window.clearInterval(timer)
  }, [isAdminPage, importBatch, refreshImportHistory])

  useEffect(() => {
    if (isAdminPage) return
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

    const timer = window.setInterval(() => {
      setHeroOptionIndex((current) => (current + 1) % HERO_OPTIONS.length)
    }, HERO_ROTATE_MS)

    return () => window.clearInterval(timer)
  }, [isAdminPage])

  useEffect(() => {
    if (isAdminPage) return
    Promise.all([publicRequest<Category[]>('/categories'), publicRequest<District[]>('/districts')])
      .then(([categoryData, districtData]) => {
        setCategories(categoryData)
        setDistricts(districtData)
      })
      .catch(() => setOptionsError('Chưa tải được danh sách lựa chọn. Vui lòng thử lại.'))
  }, [isAdminPage])

  useEffect(() => {
    if (isAdminPage || !categorySlug) {
      setAvailableTags([])
      setRelatedTags([])
      return
    }
    const query = new URLSearchParams({ category: categorySlug })
    selectedTags.forEach((tag) => query.append('selected[]', tag))
    publicRequest<{ tags: Tag[]; related: string[] }>(`/tags?${query}`)
      .then((data) => { setAvailableTags(data.tags); setRelatedTags(data.related) })
      .catch(() => setOptionsError('Chưa tải được tag cho category này.'))
  }, [categorySlug, isAdminPage, selectedTags])

  useEffect(() => {
    if (!isResultOpen) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setIsResultOpen(false)
    }
    window.addEventListener('keydown', onKeyDown)
    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [isResultOpen])

  function requestCurrentLocation() {
    if (!navigator.geolocation) {
      setLocationStatus('Trình duyệt không hỗ trợ GPS, đang dùng tâm quận')
      return
    }
    setLocationStatus('Đang xác định vị trí...')
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        setGpsLocation({ lat: coords.latitude, lng: coords.longitude })
        setDistrictId('')
        setLocationStatus('Đang tính từ vị trí hiện tại')
      },
      () => { setGpsLocation(null); setLocationStatus('Không có quyền GPS, đang dùng tâm quận') },
      { enableHighAccuracy: true, timeout: 8000 },
    )
  }

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsLoggingIn(true)
    setLoginError(null)
    const form = new FormData(event.currentTarget)
    try {
      await authRequest('/auth/csrf-cookie', { method: 'GET' })
      const payload = await authRequest('/auth/login', { method: 'POST', body: JSON.stringify({ email: form.get('email'), password: form.get('password') }) })
      setUser(payload.data)
      setIsAuthOpen(false)
    } catch (requestError) {
      setLoginError(requestError instanceof Error ? requestError.message : 'auth.login_failed')
    } finally {
      setIsLoggingIn(false)
    }
  }

  async function handleRegister(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsLoggingIn(true)
    setLoginError(null)
    const form = new FormData(event.currentTarget)
    try {
      await authRequest('/auth/csrf-cookie', { method: 'GET' })
      const payload = await authRequest('/auth/register', {
        method: 'POST',
        body: JSON.stringify({
          name: form.get('name'),
          email: form.get('email'),
          password: form.get('password'),
          password_confirmation: form.get('password_confirmation'),
        }),
      })
      setUser(payload.data)
      setIsAuthOpen(false)
    } catch (requestError) {
      setLoginError(requestError instanceof Error ? requestError.message : 'auth.register_failed')
    } finally {
      setIsLoggingIn(false)
    }
  }

  async function handleLogout() {
    await authRequest('/auth/logout', { method: 'POST' })
    setUser(null)
  }

  async function handleImportPreview(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!importFile) return
    setIsImporting(true)
    setImportError(null)
    try { const batch = await uploadImport(importFile); setImportBatch(batch); await refreshImportHistory() } catch (requestError) { setImportError(requestError instanceof Error ? requestError.message : 'import.preview_failed') } finally { setIsImporting(false) }
  }

  async function handleImportConfirm() {
    if (!importBatch) return
    setIsImporting(true)
    setImportError(null)
    try {
      setImportBatch(await updateImport(importBatch.id, true))
      await refreshImportHistory()
    } catch (requestError) { setImportError(requestError instanceof Error ? requestError.message : 'import.confirm_failed') } finally { setIsImporting(false) }
  }

  async function handleImportTransition(action: 'pause' | 'resume') {
    if (!importBatch) return
    setIsImporting(true)
    setImportError(null)
    try {
      setImportBatch(await transitionImport(importBatch.id, action))
      await refreshImportHistory()
    } catch (requestError) {
      setImportError(requestError instanceof Error ? requestError.message : `import.${action}_failed`)
    } finally {
      setIsImporting(false)
    }
  }

  async function requestRecommendation() {
    const district = districts.find((item) => item.id === districtId)
    const location = gpsLocation || district?.center
    if (!location || !categorySlug || (!gpsLocation && !district)) {
      setError('Chọn khu vực và một kiểu đi chơi để tiếp tục.')
      setIsResultOpen(true)
      return
    }
    const budgets: Record<string, [number, number | null]> = { 'under-100': [0, 99999], '100-300': [100000, 300000], '300-500': [300000, 500000], 'over-500': [500001, null] }
    const [priceMin, priceMax] = budgets[budget]
    setIsLoading(true)
    setError(null)
    setResult(null)
    setIsResultOpen(true)
    try {
      setResult(await recommend({
        location,
        district_id: gpsLocation ? '' : districtId,
        radius_km: radius,
        category_slug: categorySlug,
        price_min: priceMin,
        price_max: priceMax,
        tags: selectedTags,
        limit: 1,
      }))
    } catch (requestError) {
      setResult(null)
      setError(requestError instanceof Error ? requestError.message : 'recommendation.request_failed')
    } finally {
      setIsLoading(false)
    }
  }

  function closeResultModal() {
    setIsResultOpen(false)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    await requestRecommendation()
  }

  if (isAdminPage) {
    const canImport = user?.roles.some((role) => role === 'admin' || role === 'editor') ?? false

    return (
      <main className="admin-shell">
        <div className="grain" aria-hidden="true" />
        <nav className="admin-nav">
          <a className="brand" href="/">
            <span className="brand-mark" aria-hidden="true">H?</span>
            <span>Quản trị Hôm nay ăn gì</span>
          </a>
          <a className="admin-nav-link" href="/">Mở trang khách</a>
        </nav>
        <section className="admin-page">
          <header className="admin-header">
            <div>
              <p className="admin-eyebrow">Dữ liệu</p>
              <h1>Quản lý địa điểm</h1>
            </div>
            <p>Kiểm tra và nhập dữ liệu địa điểm từ tệp CSV.</p>
          </header>

          {!user && (
            <form className="admin-login panel" onSubmit={handleLogin}>
              <div>
                <h2>Đăng nhập</h2>
                <p>Dành cho quản trị viên và biên tập viên.</p>
              </div>
              <label>
                Email
                <input name="email" type="email" autoComplete="email" required />
              </label>
              <label>
                Mật khẩu
                <input name="password" type="password" autoComplete="current-password" required />
              </label>
              {loginError && <p className="notice">{loginError}</p>}
              <button className="primary-button" type="submit" disabled={isLoggingIn}>
                <span>{isLoggingIn ? 'Đang đăng nhập...' : 'Đăng nhập'}</span>
              </button>
            </form>
          )}

          {user && !canImport && (
            <section className="admin-denied panel">
              <h2>Không đủ quyền truy cập</h2>
              <p>Tài khoản {user.email} không có vai trò admin hoặc editor.</p>
              <button className="secondary-button" type="button" onClick={handleLogout}>Đăng xuất</button>
            </section>
          )}

          {user && canImport && (
            <section className="admin-workspace panel">
              <div className="admin-toolbar">
                <div>
                  <h2>Nhập dữ liệu CSV</h2>
                  <p>Hệ thống sẽ kiểm tra dữ liệu trước khi ghi nhận.</p>
                </div>
                <button className="admin-logout" type="button" onClick={handleLogout}>
                  {user.name} · Đăng xuất
                </button>
              </div>
              <form className="import-form" onSubmit={handleImportPreview} aria-busy={isImporting}>
                <input
                  type="file"
                  accept=".csv,.txt"
                  disabled={isImporting}
                  onChange={(event) => setImportFile(event.target.files?.[0] || null)}
                />
                <button className="primary-button" type="submit" disabled={!importFile || isImporting}>
                  {isImporting && <span className="admin-spinner" aria-hidden="true" />}
                  <span>{isImporting ? 'Đang tải lên và kiểm tra...' : 'Kiểm tra tệp'}</span>
                </button>
                {isImporting && (
                  <p className="import-progress" role="status">
                    Tệp lớn có thể cần vài phút. Vui lòng giữ trang này mở.
                  </p>
                )}
              </form>
              {importError && <p className="notice">{importError}</p>}
              {importBatch && (
                <div className="import-summary">
                  <p>
                    <strong>{importBatch.filename}</strong> · {importBatch.status}
                  </p>
                  <div className="import-stats">
                    <span>{importBatch.total_rows} dòng</span>
                    <span>{importBatch.valid_rows} hợp lệ</span>
                    <span>{importBatch.duplicate_rows} bỏ qua</span>
                    <span>{importBatch.invalid_rows} lỗi</span>
                  </div>
                  <progress className="import-progress-bar" max="100" value={importBatch.progress_percent}>{importBatch.progress_percent}%</progress>
                  <p className="import-progress" role="status">{importBatch.progress_percent}% · {importBatch.processed_rows}/{importBatch.total_rows} dòng đã xử lý · {importBatch.failed_rows} lỗi</p>
                  <button
                    className="primary-button"
                    type="button"
                    onClick={handleImportConfirm}
                    disabled={isImporting || importBatch.status !== 'previewed'}
                  >
                    <span>{isImporting ? 'Đang xử lý...' : 'Nhập dữ liệu'}</span>
                  </button>
                  {['queued', 'processing', 'pause_requested'].includes(importBatch.status) && (
                    <button className="secondary-button" type="button" onClick={() => void handleImportTransition('pause')} disabled={isImporting || importBatch.status === 'pause_requested'}>
                      {importBatch.status === 'pause_requested' ? 'Đang tạm dừng...' : 'Tạm dừng'}
                    </button>
                  )}
                  {importBatch.status === 'paused' && (
                    <button className="secondary-button" type="button" onClick={() => void handleImportTransition('resume')} disabled={isImporting}>Tiếp tục</button>
                  )}
                  {importBatch.status === 'completed_with_errors' && <p className="notice">Đã hoàn tất nhưng có dòng lỗi cần kiểm tra.</p>}
                </div>
              )}
              <section className="import-history" aria-labelledby="import-history-title">
                <div className="import-history-head">
                  <div>
                    <p className="admin-eyebrow">Theo dõi</p>
                    <h3 id="import-history-title">Lịch sử nhập dữ liệu</h3>
                  </div>
                  <button className="secondary-button" type="button" onClick={() => void refreshImportHistory()} disabled={importHistoryLoading}>
                    {importHistoryLoading ? 'Đang tải...' : 'Làm mới'}
                  </button>
                </div>
                {importHistoryError && <p className="notice">{importHistoryError}</p>}
                {!importHistoryLoading && importHistory && importHistory.data.length === 0 && <p className="import-history-empty">Chưa có lần nhập dữ liệu nào.</p>}
                {importHistory && importHistory.data.length > 0 && (
                  <div className="import-history-list">
                    {importHistory.data.map((batch) => (
                      <button
                        className={`import-history-item${importBatch?.id === batch.id ? ' is-active' : ''}`}
                        type="button"
                        key={batch.id}
                        onClick={() => void updateImport(batch.id).then(setImportBatch).catch(() => setImportError('import.status_failed'))}
                      >
                        <span className="import-history-main">
                          <strong>{batch.filename}</strong>
                          <small>{batch.created_at ? new Date(batch.created_at).toLocaleString('vi-VN') : batch.status}</small>
                        </span>
                        <span className={`import-status import-status-${batch.status}`}>{batch.status}</span>
                        <span className="import-history-counts">{batch.imported_rows}/{batch.total_rows} nhập · {batch.failed_rows} lỗi</span>
                        <span className="import-history-progress"><i style={{ width: `${batch.progress_percent}%` }} /></span>
                      </button>
                    ))}
                  </div>
                )}
                {importHistory && importHistory.last_page > 1 && (
                  <div className="import-history-pagination">
                    <button className="secondary-button" type="button" onClick={() => void refreshImportHistory(importHistory.current_page - 1)} disabled={importHistory.current_page <= 1 || importHistoryLoading}>Trước</button>
                    <span>Trang {importHistory.current_page}/{importHistory.last_page}</span>
                    <button className="secondary-button" type="button" onClick={() => void refreshImportHistory(importHistory.current_page + 1)} disabled={importHistory.current_page >= importHistory.last_page || importHistoryLoading}>Sau</button>
                  </div>
                )}
              </section>
            </section>
          )}
        </section>
      </main>
    )
  }

  return (
    <main className="public-shell">
      <div className="grain" aria-hidden="true" />
      <a className="skip-link" href="#recommendation-form">Chuyển đến bộ lọc</a>

      <nav className="nav" aria-label="Điều hướng chính">
        <a className="brand" href="/" aria-label="Hôm nay ăn gì - Trang chủ">
          <span className="brand-mark" aria-hidden="true">H?</span>
          <span>Hôm nay ăn gì</span>
        </a>
        <div className="nav-actions">
          <span className="nav-note">Ăn uống · Cà phê · Vui chơi</span>
          {user ? (
            <button className="nav-account" type="button" onClick={() => setIsAccountOpen(true)}>
              {user.name}
            </button>
          ) : (
            <button
              className="nav-account"
              type="button"
              onClick={() => {
                setAuthMode('login')
                setLoginError(null)
                setIsAuthOpen(true)
              }}
            >
              Đăng nhập
            </button>
          )}
          <a className="nav-cta" href="#recommendation-form">Chọn ngay</a>
        </div>
      </nav>

      {isAuthOpen && (
        <div className="auth-modal" role="presentation">
          <button className="auth-backdrop" type="button" aria-label="Đóng đăng nhập" onClick={() => setIsAuthOpen(false)} />
          <section className="auth-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-title">
            <div className="auth-dialog-head">
              <div>
                <p className="form-kicker">Tài khoản</p>
                <h2 id="auth-title">{authMode === 'login' ? 'Chào mừng bạn quay lại' : 'Tạo tài khoản'}</h2>
              </div>
              <button className="result-close" type="button" onClick={() => setIsAuthOpen(false)} aria-label="Đóng">×</button>
            </div>
            <form className="auth-form" onSubmit={authMode === 'login' ? handleLogin : handleRegister}>
              {authMode === 'register' && (
                <label>
                  Tên hiển thị
                  <input name="name" autoComplete="name" required />
                </label>
              )}
              <label>
                Email
                <input name="email" type="email" autoComplete="email" required />
              </label>
              <label>
                Mật khẩu
                <input name="password" type="password" autoComplete={authMode === 'login' ? 'current-password' : 'new-password'} minLength={8} required />
              </label>
              {authMode === 'register' && (
                <label>
                  Xác nhận mật khẩu
                  <input name="password_confirmation" type="password" autoComplete="new-password" minLength={8} required />
                </label>
              )}
              {loginError && <p className="notice">{loginError}</p>}
              <button className="primary-button" type="submit" disabled={isLoggingIn}>
                <span>{isLoggingIn ? 'Đang xử lý...' : authMode === 'login' ? 'Đăng nhập' : 'Đăng ký'}</span>
              </button>
            </form>
            <button
              className="auth-switch"
              type="button"
              onClick={() => {
                setAuthMode((mode) => mode === 'login' ? 'register' : 'login')
                setLoginError(null)
              }}
            >
              {authMode === 'login' ? 'Chưa có tài khoản? Đăng ký' : 'Đã có tài khoản? Đăng nhập'}
            </button>
          </section>
        </div>
      )}

      {isAccountOpen && user && (
        <div className="auth-modal" role="presentation">
          <button className="auth-backdrop" type="button" aria-label="Đóng tài khoản" onClick={() => setIsAccountOpen(false)} />
          <section className="auth-dialog account-dialog" role="dialog" aria-modal="true" aria-labelledby="account-title">
            <div className="auth-dialog-head">
              <div>
                <p className="form-kicker">Tài khoản của bạn</p>
                <h2 id="account-title">{user.name}</h2>
              </div>
              <button className="result-close" type="button" onClick={() => setIsAccountOpen(false)} aria-label="Đóng">×</button>
            </div>
            <dl className="account-summary">
              <div><dt>Email</dt><dd>{user.email}</dd></div>
              <div><dt>Vai trò</dt><dd>{user.roles.length > 0 ? user.roles.join(', ') : 'Người dùng'}</dd></div>
            </dl>
            <div className="account-actions">
              {user.roles.some((role) => role === 'admin' || role === 'editor') && <a className="secondary-button" href="/admin">Mở quản trị</a>}
              <button className="primary-button" type="button" onClick={() => { setIsAccountOpen(false); void handleLogout() }}>Đăng xuất</button>
            </div>
          </section>
        </div>
      )}

      <header className="hero">
        <div className="hero-copy">
          <p className="kicker">Gợi ý địa điểm tại Hà Nội</p>
          <h1>
            Bạn không biết đi đâu
            <br />
            <em>hay ăn gì?</em>
          </h1>
          <p className="intro">
            Chọn khu vực, ngân sách và điều bạn đang muốn. Phần còn lại để Hôm nay ăn gì lo.
          </p>
          <a className="hero-link" href="#recommendation-form">
            <span>Bắt đầu chọn</span>
            <span className="hero-link-icon" aria-hidden="true">↓</span>
          </a>
        </div>

        <div className="hero-stage">
          <div
            className="hero-visual"
            aria-label={`${activeHeroOption.title}: ${activeHeroOption.description}`}
            role="img"
          >
            {HERO_OPTIONS.map((option, index) => (
              <div
                key={option.id}
                className={`hero-visual-slide${index === heroOptionIndex ? ' is-active' : ''}`}
                style={{ backgroundImage: `url("${option.image}")` }}
                aria-hidden={index !== heroOptionIndex}
              />
            ))}
            <p className="hero-chip" key={activeHeroOption.id}>{activeHeroOption.chip}</p>
          </div>
          <div className="hero-aside" aria-label="Ba hướng khám phá">
            {HERO_OPTIONS.map((option, index) => (
              <button
                key={option.id}
                type="button"
                className={`hero-card${index === heroOptionIndex ? ' is-active' : ''}`}
                onClick={() => setHeroOptionIndex(index)}
                aria-pressed={index === heroOptionIndex}
              >
                <span className="hero-card-media" aria-hidden="true">
                  <img src={option.image} alt="" loading="lazy" />
                </span>
                <span className="hero-card-copy">
                  <span className="hero-card-index">{String(index + 1).padStart(2, '0')}</span>
                  <strong>{option.title}</strong>
                  <span className="hero-card-desc">{option.description}</span>
                </span>
                {index === heroOptionIndex && (
                  <span className="hero-card-progress" aria-hidden="true" />
                )}
              </button>
            ))}
          </div>
        </div>
      </header>

      <section className="workspace" id="recommendation-form">
        <form className="controls panel discovery-form" onSubmit={handleSubmit}>
          <div className="form-heading">
            <div className="form-heading-copy">
              <p className="form-kicker">Bộ lọc</p>
              <h2>Bạn muốn đi đâu?</h2>
              <p>Chọn khu vực, loại hình, sở thích và ngân sách để nhận một gợi ý phù hợp.</p>
            </div>
            <div className="form-summary" aria-live="polite">
              <span>{selectedDistrict?.name || 'Chưa chọn quận'}</span>
              <span>{selectedCategory?.name || 'Chưa chọn loại'}</span>
              <span>{selectedBudget.label}</span>
              <span>≤ {radius} km</span>
            </div>
          </div>

          {optionsError && <p className="notice">{optionsError}</p>}

          <div className="form-board">
            <section className="picker-block area-block">
              <div className="picker-head">
                <strong>Khu vực</strong>
                <span>{locationStatus}</span>
              </div>

              <div className="district-menu" role="listbox" aria-label="Quận muốn đi">
                {districts.map((district) => {
                  const active = districtId === district.id
                  return (
                    <button
                      key={district.id}
                      type="button"
                      role="option"
                      aria-selected={active}
                      aria-label={district.center ? district.name : `${district.name}, chưa có tâm quận`}
                      className={`district-tile${active ? ' is-active' : ''}${district.center ? '' : ' is-disabled'}`}
                      disabled={!district.center}
                      onClick={() => {
                        setDistrictId(district.id)
                        setGpsLocation(null)
                        setLocationStatus('Đang dùng tâm quận')
                      }}
                    >
                      <span className="district-tile-name">{district.name}</span>
                    </button>
                  )
                })}
              </div>

              <div className="area-tools">
                <button
                  className="ghost-button gps-button"
                  type="button"
                  onClick={requestCurrentLocation}
                  disabled={false}
                >
                  {gpsLocation ? 'Đang dùng GPS' : 'Dùng GPS hiện tại'}
                </button>

                <div className="radius-track" role="group" aria-label="Đi xa tối đa">
                  {RADIUS_OPTIONS.map((value) => (
                    <button
                      key={value}
                      type="button"
                      className={`radius-stop${radius === value ? ' is-active' : ''}`}
                      aria-pressed={radius === value}
                      onClick={() => setRadius(value)}
                    >
                      <span className="radius-dot" aria-hidden="true" />
                      <span>{value} km</span>
                    </button>
                  ))}
                </div>
              </div>
            </section>

            <section className="picker-block mood-block">
              <div className="picker-head">
                <strong>Loại hình</strong>
                <span>Chọn một mục</span>
              </div>
              <div className="mood-grid" role="group" aria-label="Loại hình">
                {categories.map((category) => {
                  const accent = categoryAccent(category.slug)
                  const active = categorySlug === category.slug
                  return (
                    <button
                      key={category.id}
                      type="button"
                      className={`mood-card${active ? ' is-active' : ''}`}
                      aria-pressed={active}
                      style={{
                        ['--mood-hue' as string]: accent.hue,
                        ['--mood-soft' as string]: accent.soft,
                        ['--mood-deep' as string]: accent.deep,
                      }}
                      onClick={() => {
                        setCategorySlug(category.slug)
                        setSelectedTags([])
                      }}
                    >
                      <span className="mood-emoji" aria-hidden="true">{accent.emoji}</span>
                      <span className="mood-name">{category.name}</span>
                    </button>
                  )
                })}
              </div>
            </section>

            {categorySlug && (
              <section className="picker-block taste-block">
                <div className="picker-head">
                  <strong>Sở thích</strong>
                  <span>{selectedTags.length > 0 ? `Đã chọn ${selectedTags.length}` : 'Tuỳ chọn · chạm để gắn'}</span>
                </div>
                {selectedTags.length > 0 && (
                  <div className="taste-selected" aria-label="Sở thích đã chọn">
                    {selectedTags.map((slug) => {
                      const tag = availableTags.find((item) => item.slug === slug)
                      return tag ? (
                        <button
                          key={tag.id}
                          type="button"
                          className="taste-pill is-on"
                          aria-pressed="true"
                          onClick={() => setSelectedTags((current) => current.filter((item) => item !== slug))}
                        >
                          {tag.name}
                          <span aria-hidden="true">×</span>
                        </button>
                      ) : null
                    })}
                  </div>
                )}
                <div className="taste-board" aria-label="Sở thích chưa chọn">
                  {[...availableTags]
                    .filter((tag) => !selectedTags.includes(tag.slug))
                    .sort((left, right) => {
                      const leftRelated = relatedTagOrder.indexOf(left.slug)
                      const rightRelated = relatedTagOrder.indexOf(right.slug)
                      if (leftRelated !== -1 || rightRelated !== -1) {
                        return leftRelated === -1 ? 1 : rightRelated === -1 ? -1 : leftRelated - rightRelated
                      }
                      return left.sort_order - right.sort_order
                    })
                    .map((tag) => {
                      const related = relatedTagOrder.includes(tag.slug)
                      return (
                        <button
                          key={tag.id}
                          type="button"
                          className={`taste-pill${related ? ' is-related' : ''}`}
                          aria-pressed="false"
                          onClick={() => setSelectedTags((current) => [...current, tag.slug])}
                        >
                          {tag.name}
                        </button>
                      )
                    })}
                </div>
              </section>
            )}

            <section className="picker-block budget-block">
              <div className="picker-head">
                <strong>Ngân sách</strong>
                <span>Mỗi người</span>
              </div>
              <div className="budget-strip" role="group" aria-label="Ngân sách mỗi người">
                {BUDGET_OPTIONS.map((option) => {
                  const active = budget === option.value
                  return (
                    <button
                      key={option.value}
                      type="button"
                      className={`budget-card${active ? ' is-active' : ''}`}
                      aria-pressed={active}
                      onClick={() => setBudget(option.value)}
                    >
                      <strong>{option.label}</strong>
                    </button>
                  )
                })}
              </div>
            </section>
          </div>

          <div className="form-actions">
            <button
              className="primary-button submit-button"
              type="submit"
              disabled={isLoading || (!districtId && !gpsLocation) || !categorySlug}
            >
              <span>{isLoading ? 'Đang tìm kiếm địa điểm...' : 'Xem gợi ý'}</span>
              <span className="button-icon" aria-hidden="true">→</span>
            </button>
          </div>
        </form>
      </section>

      {isResultOpen && (
        <div
          className={`result-modal${featuredPlace ? ' has-place' : ''}${isLoading ? ' is-loading' : ''}`}
          style={featuredBackdrop.style}
          role="presentation"
        >
          <button className="result-backdrop" type="button" aria-label="Đóng gợi ý" onClick={closeResultModal} />
          <div
            className="result-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby={resultTitleId}
            aria-busy={isLoading}
          >
            <div className="result-dialog-top">
              <p className="result-kicker">Gợi ý cho bạn</p>
              <button className="result-close" type="button" onClick={closeResultModal} aria-label="Đóng">
                ×
              </button>
            </div>

            {error && <p className="notice">Không thể tải gợi ý: {error}</p>}

            {isLoading && (
              <div className="result-loading" aria-label="Đang tìm quán">
                <div className="result-loading-orb" aria-hidden="true" />
                <strong>Đang tìm địa điểm phù hợp…</strong>
                <p>Giữ lựa chọn hiện tại hoặc nới bán kính nếu cần.</p>
              </div>
            )}

            {!isLoading && !error && result && result.places.length === 0 && (
              <div className="result-empty">
                <strong>Chưa thấy chỗ khớp</strong>
                <p>Thử nới bán kính, đổi loại hình hoặc bớt tag.</p>
                <button className="ghost-button" type="button" onClick={() => void requestRecommendation()}>
                  Thử lại
                </button>
              </div>
            )}

            {!isLoading && featuredPlace && (
              <article className="result-card">
                <div className="result-media">
                  {featuredPlace.cover_image ? (
                    <img src={featuredPlace.cover_image} alt="" />
                  ) : (
                    <div className="result-fallback" aria-hidden="true">
                      <span>{featuredBackdrop.accent.emoji}</span>
                      <strong>{featuredPlace.name.slice(0, 1)}</strong>
                    </div>
                  )}
                  <div className="result-media-badges">
                    <span>{featuredPlace.category?.name || 'Địa điểm'}</span>
                    <span>{featuredPlace.district?.name || selectedDistrict?.name || 'Hà Nội'}</span>
                  </div>
                </div>

                <div className="result-body">
                  <h3 id={resultTitleId}>{featuredPlace.name}</h3>
                  <p className="result-address">{featuredPlace.address}</p>

                  <div className="result-stats">
                    {featuredPlace.distance_km !== undefined && <span>{featuredPlace.distance_km.toFixed(1)} km</span>}
                    <span>{featuredPlace.price_display}</span>
                    <span>★ {featuredPlace.rating.toFixed(1)}</span>
                  </div>

                  {featuredPlace.matched_tags && featuredPlace.matched_tags.length > 0 && (
                    <div className="result-tags">
                      {featuredPlace.tags
                        .filter((tag) => featuredPlace.matched_tags?.includes(tag.slug))
                        .map((tag) => (
                          <span key={tag.slug}>{tag.name}</span>
                        ))}
                    </div>
                  )}

                  {result?.meta.fallback_applied && (
                    <p className="result-note">
                      Đã mở rộng phạm vi tới {result.meta.query_radius_km} km để đủ lựa chọn.
                    </p>
                  )}

                  {featuredPlace.description && <p className="result-description">{featuredPlace.description}</p>}

                  <div className="result-actions">
                    <button
                      className="primary-button"
                      type="button"
                      onClick={() => void requestRecommendation()}
                      disabled={isLoading}
                    >
                      <span>{isLoading ? 'Đang chọn...' : 'Chọn lại'}</span>
                      <span className="button-icon" aria-hidden="true">↻</span>
                    </button>
                    <a className="ghost-button result-detail-link" href={`/places/${encodeURIComponent(featuredPlace.slug)}`}>
                      Xem chi tiết
                    </a>
                    <button className="ghost-button" type="button" onClick={closeResultModal}>
                      Giữ form này
                    </button>
                  </div>
                </div>
              </article>
            )}
          </div>
        </div>
      )}

      <footer className="site-footer">
        <div>
          <strong>Hôm nay ăn gì</strong>
          <p>Ăn ngon, uống vui, chọn nhanh, khỏi nghĩ nhiều.</p>
        </div>
        <div className="footer-meta">
          <span>Hà Nội</span>
          <a href="/admin">Quản trị</a>
        </div>
      </footer>
    </main>
  )
}

export default App
