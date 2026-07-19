import { useEffect, useId, useState } from 'react'
import type { CSSProperties, FormEvent } from 'react'
import './App.css'

type RecommendationRequest = {
  location: { lat: number; lng: number }
  district_id: string
  radius_km: number
  category_slug: string
  price_min: number
  price_max: number | null
  tags?: string[]
  limit?: 1 | 3
}

type Place = {
  id: string
  name: string
  slug: string
  distance_km: number
  price_display: string
  address: string
  rating: number
  matched_tags: string[]
  cover_image: string | null
  category: { name: string; slug: string } | null
  district: { name: string } | null
  tags: Tag[]
}

type RecommendationData = {
  places: Place[]
  meta: { message_key: string; fallback_applied: boolean; query_radius_km: number }
}

type Category = { id: string; name: string; slug: string; sort_order: number }
type District = { id: string; name: string; slug: string; center: { lat: number; lng: number } | null }
type Tag = { id: string; name: string; slug: string; group: string; icon: string | null; sort_order: number; is_related?: boolean }

type AuthUser = { id: string; name: string; email: string; roles: string[] }
type ImportRow = { row_number: number; external_id: string | null; status: string; normalized_data: Record<string, unknown>; errors: string[] }
type ImportBatch = { id: string; filename: string; status: string; total_rows: number; valid_rows: number; duplicate_rows: number; invalid_rows: number; error_message?: string | null; rows: ImportRow[] }

const API_BASE = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000').replace(/\/(?:api)?\/?$/, '')

async function recommend(request: RecommendationRequest): Promise<RecommendationData> {
  const response = await fetch(`${API_BASE}/api/v1/recommendations`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(request),
  })
  const payload = await response.json()
  if (!response.ok || !payload.success) {
    throw new Error(payload.error?.message_key || 'recommendation.request_failed')
  }
  return payload.data
}

async function publicRequest<T>(path: string): Promise<T> {
  const response = await fetch(`${API_BASE}/api/v1${path}`, { headers: { Accept: 'application/json' } })
  const payload = await response.json()
  if (!response.ok || !payload.success) throw new Error(payload.error?.message_key || 'discovery.request_failed')
  return payload.data
}

async function authRequest(path: string, options: RequestInit = {}) {
  const xsrf = decodeURIComponent(document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
  const isMultipart = options.body instanceof FormData
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (!isMultipart) headers.set('Content-Type', 'application/json')
  if (xsrf) headers.set('X-XSRF-TOKEN', xsrf)
  const response = await fetch(`${API_BASE}/api/v1${path}`, {
    ...options,
    credentials: 'include',
    headers,
  })
  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new Error(payload?.message || payload?.errors?.email?.[0] || 'auth.request_failed')
  }
  return response.status === 204 ? null : response.json()
}

async function uploadImport(file: File): Promise<ImportBatch> {
  await authRequest('/auth/csrf-cookie', { method: 'GET' })
  const form = new FormData()
  form.append('file', file)
  const payload = await authRequest('/admin/imports/places/preview', { method: 'POST', body: form })
  return payload.data
}

async function updateImport(batchId: string, confirm = false): Promise<ImportBatch> {
  const payload = await authRequest(`/admin/imports/places/${batchId}${confirm ? '/confirm' : ''}`, { method: confirm ? 'POST' : 'GET' })
  return payload.data
}

async function getCurrentUser(): Promise<AuthUser | null> {
  try {
    const payload = await authRequest('/auth/me')
    return payload.data
  } catch {
    return null
  }
}

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
  'an-uong': { hue: '#e4572e', soft: '#ffe3d6', deep: '#9c2f12', emoji: '🍜' },
  'cafe-do-uong': { hue: '#c47b2b', soft: '#f8e6c8', deep: '#7a4a16', emoji: '☕' },
  'bar-nightlife': { hue: '#7b3fe4', soft: '#ebe0ff', deep: '#4a1f99', emoji: '🍸' },
  'ngoai-troi': { hue: '#1f8a5b', soft: '#d8f3e5', deep: '#0f4f34', emoji: '🌿' },
  'gaming-giai-tri': { hue: '#2f6fed', soft: '#dce8ff', deep: '#1a3f99', emoji: '🎮' },
  'van-hoa-nghe-thuat': { hue: '#d63d7a', soft: '#ffd9e8', deep: '#8a1848', emoji: '🎭' },
  'suc-khoe-thu-gian': { hue: '#0f9d8a', soft: '#d4f5ef', deep: '#0a5c51', emoji: '🧘' },
  'mua-sam': { hue: '#e09f1f', soft: '#fff0c8', deep: '#8a5d0a', emoji: '🛍️' },
}

const BUDGET_OPTIONS = [
  { value: 'under-100', label: 'Dưới 100k', hint: 'Nhẹ ví' },
  { value: '100-300', label: '100–300k', hint: 'Vừa túi' },
  { value: '300-500', label: '300–500k', hint: 'Thoải mái' },
  { value: 'over-500', label: 'Trên 500k', hint: 'Chơi lớn' },
] as const

const RADIUS_OPTIONS = [1, 3, 5, 10, 20] as const

function categoryAccent(slug?: string | null) {
  if (!slug) return { hue: '#e4572e', soft: '#ffe3d6', deep: '#9c2f12', emoji: '✨' }
  return CATEGORY_ACCENTS[slug] ?? { hue: '#e4572e', soft: '#ffe3d6', deep: '#9c2f12', emoji: '✨' }
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
  const [gpsLocation, setGpsLocation] = useState<{ lat: number; lng: number } | null>(null)
  const [locationStatus, setLocationStatus] = useState('Đang dùng tâm quận')
  const [optionsError, setOptionsError] = useState<string | null>(null)
  const [result, setResult] = useState<RecommendationData | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loginError, setLoginError] = useState<string | null>(null)
  const [isLoggingIn, setIsLoggingIn] = useState(false)
  const [importFile, setImportFile] = useState<File | null>(null)
  const [importBatch, setImportBatch] = useState<ImportBatch | null>(null)
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

  useEffect(() => {
    if (isAdminPage) void getCurrentUser().then(setUser)
  }, [isAdminPage])

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
      ({ coords }) => { setGpsLocation({ lat: coords.latitude, lng: coords.longitude }); setLocationStatus('Đang tính từ vị trí hiện tại') },
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
    } catch (requestError) {
      setLoginError(requestError instanceof Error ? requestError.message : 'auth.login_failed')
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
    try { setImportBatch(await uploadImport(importFile)) } catch (requestError) { setImportError(requestError instanceof Error ? requestError.message : 'import.preview_failed') } finally { setIsImporting(false) }
  }

  async function handleImportConfirm() {
    if (!importBatch) return
    setIsImporting(true)
    setImportError(null)
    try {
      const batch = await updateImport(importBatch.id, true)
      setImportBatch(batch)
      if (batch.status === 'classifying') {
        const refreshed = await updateImport(batch.id)
        setImportBatch(refreshed)
      }
    } catch (requestError) { setImportError(requestError instanceof Error ? requestError.message : 'import.confirm_failed') } finally { setIsImporting(false) }
  }

  async function requestRecommendation() {
    const district = districts.find((item) => item.id === districtId)
    const location = gpsLocation || district?.center
    if (!district || !location || !categorySlug) {
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
        district_id: districtId,
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
                    <span>{importBatch.duplicate_rows} trùng</span>
                    <span>{importBatch.invalid_rows} lỗi</span>
                  </div>
                  <button
                    className="primary-button"
                    type="button"
                    onClick={handleImportConfirm}
                    disabled={isImporting || importBatch.status !== 'previewed'}
                  >
                    <span>{isImporting ? 'Đang xử lý...' : 'Nhập dữ liệu'}</span>
                  </button>
                </div>
              )}
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
          <a className="nav-cta" href="#recommendation-form">Chọn ngay</a>
        </div>
      </nav>

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
              <p className="form-kicker">Bộ lọc đi chơi</p>
              <h2>Bạn muốn đi đâu?</h2>
              <p>Chọn khu vực, mood, sở thích và ngân sách — một gợi ý sẽ bật lên ngay.</p>
            </div>
            <div className="form-summary" aria-live="polite">
              <span>{selectedDistrict?.name || 'Chưa chọn quận'}</span>
              <span>{selectedCategory?.name || 'Chưa chọn mood'}</span>
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
                      className={`district-tile${active ? ' is-active' : ''}${district.center ? '' : ' is-disabled'}`}
                      disabled={!district.center}
                      onClick={() => {
                        setDistrictId(district.id)
                        setGpsLocation(null)
                        setLocationStatus('Đang dùng tâm quận')
                      }}
                    >
                      <span className="district-tile-name">{district.name}</span>
                      <span className="district-tile-meta">{district.center ? 'Sẵn sàng' : 'Chưa có tâm'}</span>
                    </button>
                  )
                })}
              </div>

              <div className="area-tools">
                <button
                  className="ghost-button gps-button"
                  type="button"
                  onClick={requestCurrentLocation}
                  disabled={!districtId}
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
                <strong>Hôm nay muốn gì</strong>
                <span>Chạm một mood</span>
              </div>
              <div className="mood-grid" role="group" aria-label="Hôm nay bạn muốn">
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
                      <span className="mood-check" aria-hidden="true">{active ? '✓' : ''}</span>
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
                      <span>{option.hint}</span>
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
              disabled={isLoading || !districtId || !categorySlug}
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
                <strong>Đang tìm kiếm một chỗ hay...</strong>
                <p>Giữ mood, nới bán kính nếu cần, rồi bung kết quả.</p>
              </div>
            )}

            {!isLoading && !error && result && result.places.length === 0 && (
              <div className="result-empty">
                <strong>Chưa thấy chỗ khớp</strong>
                <p>Thử nới bán kính, đổi mood hoặc bỏ bớt sở thích rồi chọn lại.</p>
                <button className="secondary-button" type="button" onClick={() => void requestRecommendation()}>
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
                    <span>{featuredPlace.distance_km.toFixed(1)} km</span>
                    <span>{featuredPlace.price_display}</span>
                    <span>★ {featuredPlace.rating.toFixed(1)}</span>
                  </div>

                  {featuredPlace.matched_tags.length > 0 && (
                    <div className="result-tags">
                      {featuredPlace.tags
                        .filter((tag) => featuredPlace.matched_tags.includes(tag.slug))
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
