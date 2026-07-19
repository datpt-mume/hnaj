import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
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
  const selectedGroups = selectedTags
    .map((slug) => availableTags.find((tag) => tag.slug === slug)?.group)
    .filter((group): group is string => Boolean(group))
  const relatedTagOrder = relatedTags.length > 0
    ? relatedTags
    : availableTags.filter((tag) => selectedGroups.includes(tag.group) && !selectedTags.includes(tag.slug)).map((tag) => tag.slug)

  useEffect(() => {
    if (isAdminPage) void getCurrentUser().then(setUser)
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

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const district = districts.find((item) => item.id === districtId)
    const location = gpsLocation || district?.center
    if (!district || !location || !categorySlug) {
      setError('Chọn khu vực và một kiểu đi chơi để tiếp tục.')
      return
    }
    const budgets: Record<string, [number, number | null]> = { 'under-100': [0, 99999], '100-300': [100000, 300000], '300-500': [300000, 500000], 'over-500': [500001, null] }
    const [priceMin, priceMax] = budgets[budget]
    setIsLoading(true)
    setError(null)
    setResult(null)
    try {
      setResult(await recommend({
        location,
        district_id: districtId,
        radius_km: radius,
        category_slug: categorySlug,
        price_min: priceMin,
        price_max: priceMax,
        tags: selectedTags,
        limit: 3,
      }))
    } catch (requestError) {
      setResult(null)
      setError(requestError instanceof Error ? requestError.message : 'recommendation.request_failed')
    } finally {
      setIsLoading(false)
    }
  }

  if (isAdminPage) {
    const canImport = user?.roles.some((role) => role === 'admin' || role === 'editor') ?? false

    return (
      <main className="admin-shell">
        <nav className="admin-nav"><a className="brand" href="/"><span className="brand-mark">H?</span><span>Quản trị Hôm nay ăn gì</span></a><a href="/">Mở trang khách</a></nav>
        <section className="admin-page">
          <header className="admin-header"><div><p className="admin-eyebrow">Dữ liệu</p><h1>Quản lý địa điểm</h1></div><p>Kiểm tra và nhập dữ liệu địa điểm từ tệp CSV.</p></header>
          {!user && <form className="admin-login" onSubmit={handleLogin}><div><h2>Đăng nhập</h2><p>Dành cho quản trị viên và biên tập viên.</p></div><label>Email<input name="email" type="email" autoComplete="email" required /></label><label>Mật khẩu<input name="password" type="password" autoComplete="current-password" required /></label>{loginError && <p className="notice">{loginError}</p>}<button type="submit" disabled={isLoggingIn}>{isLoggingIn ? 'Đang đăng nhập...' : 'Đăng nhập'}</button></form>}
          {user && !canImport && <section className="admin-denied"><h2>Không đủ quyền truy cập</h2><p>Tài khoản {user.email} không có vai trò admin hoặc editor.</p><button type="button" onClick={handleLogout}>Đăng xuất</button></section>}
          {user && canImport && <section className="admin-workspace"><div className="admin-toolbar"><div><h2>Nhập dữ liệu CSV</h2><p>Hệ thống sẽ kiểm tra dữ liệu trước khi ghi nhận.</p></div><button className="admin-logout" type="button" onClick={handleLogout}>{user.name} · Đăng xuất</button></div><form className="import-form" onSubmit={handleImportPreview} aria-busy={isImporting}><input type="file" accept=".csv,.txt" disabled={isImporting} onChange={(event) => setImportFile(event.target.files?.[0] || null)} /><button type="submit" disabled={!importFile || isImporting}>{isImporting && <span className="admin-spinner" aria-hidden="true" />}{isImporting ? 'Đang tải lên và kiểm tra...' : 'Kiểm tra tệp'}</button>{isImporting && <p className="import-progress" role="status">Tệp lớn có thể cần vài phút. Vui lòng giữ trang này mở.</p>}</form>{importError && <p className="notice">{importError}</p>}{importBatch && <div className="import-summary"><p><strong>{importBatch.filename}</strong> · {importBatch.status}</p><div><span>{importBatch.total_rows} dòng</span><span>{importBatch.valid_rows} hợp lệ</span><span>{importBatch.duplicate_rows} trùng</span><span>{importBatch.invalid_rows} lỗi</span></div><button type="button" onClick={handleImportConfirm} disabled={isImporting || importBatch.status !== 'previewed'}>{isImporting ? 'Đang xử lý...' : 'Nhập dữ liệu'}</button></div>}</section>}
        </section>
      </main>
    )
  }

  return (
    <main>
      <a className="skip-link" href="#recommendation-form">Chuyển đến bộ lọc</a>
      <nav className="nav" aria-label="Điều hướng chính">
        <a className="brand" href="/" aria-label="Hôm nay ăn gì - Trang chủ"><span className="brand-mark">H?</span><span>Hôm nay ăn gì</span></a>
        <div className="nav-actions"><span className="nav-note">Ăn uống · Cà phê · Vui chơi</span></div>
      </nav>
      <header className="hero">
        <div className="hero-copy">
          <p className="kicker">Gợi ý địa điểm tại Hà Nội</p>
          <h1>Bạn không biết đi đâu<br /><em>hay ăn gì?</em></h1>
          <p className="intro">Hãy để Hôm nay ăn gì giúp bạn. Chọn khu vực, ngân sách và điều bạn đang muốn, còn lại để Hôm nay ăn gì lo nhé!</p>
          <a className="hero-link" href="#recommendation-form">Bắt đầu chọn <span aria-hidden="true">↓</span></a>
        </div>
        <div className="hero-visual" aria-label="Một tô phở Việt Nam hấp dẫn" role="img">
          <div className="food-stamp"><strong>03</strong><span>gợi ý<br />mỗi lượt</span></div><p>Ăn · Uống · Chơi</p>
        </div>
      </header>
      <section className="workspace" id="recommendation-form">
        <form className="controls" onSubmit={handleSubmit}>
          <div className="form-heading"><span>01</span><div><h2>Bạn muốn đi đâu?</h2><p>Chọn vài điều quan trọng với bạn.</p></div></div>
          {optionsError && <p className="notice">{optionsError}</p>}
          <label>Quận muốn đi<select value={districtId} onChange={(event) => { setDistrictId(event.target.value); setGpsLocation(null); setLocationStatus('Đang dùng tâm quận') }} required><option value="">Chọn một quận</option>{districts.map((district) => <option key={district.id} value={district.id} disabled={!district.center}>{district.name}{district.center ? '' : ' · chưa có dữ liệu'}</option>)}</select></label>
          <div className="location-row"><div><strong>Mốc tính khoảng cách</strong><span>{locationStatus}</span></div><button className="secondary-button" type="button" onClick={requestCurrentLocation} disabled={!districtId}>Dùng GPS</button></div>
          <fieldset><legend>Đi xa tối đa</legend><div className="choice-row">{[1, 3, 5, 10, 20].map((value) => <button key={value} type="button" className={radius === value ? 'choice active' : 'choice'} aria-pressed={radius === value} onClick={() => setRadius(value)}>{value} km</button>)}</div></fieldset>
          <fieldset><legend>Hôm nay bạn muốn</legend><div className="category-grid">{categories.map((category) => <button key={category.id} type="button" className={categorySlug === category.slug ? 'category-choice active' : 'category-choice'} aria-pressed={categorySlug === category.slug} onClick={() => { setCategorySlug(category.slug); setSelectedTags([]) }}>{category.name}</button>)}</div></fieldset>
          {categorySlug && <fieldset className="tag-fieldset"><legend>Bạn đang muốn gì?</legend><p className="field-hint">Chọn một hoặc vài điều bạn thích.</p>{selectedTags.length > 0 && <div className="selected-preferences"><p className="selected-label">Đã chọn <span>{selectedTags.length}</span></p><div className="selected-rail" aria-label="Sở thích đã chọn">{selectedTags.map((slug) => { const tag = availableTags.find((item) => item.slug === slug); return tag ? <button key={tag.id} type="button" className="preference-chip selected" aria-pressed="true" onClick={() => setSelectedTags((current) => current.filter((item) => item !== slug))}>{tag.name}<span aria-hidden="true">×</span></button> : null })}</div></div>}<p className="browse-label">{selectedTags.length > 0 ? 'Lướt để thêm lựa chọn' : 'Lướt để chọn'}</p><div className="tag-rail" aria-label="Sở thích chưa chọn">{[...availableTags].filter((tag) => !selectedTags.includes(tag.slug)).sort((left, right) => { const leftRelated = relatedTagOrder.indexOf(left.slug); const rightRelated = relatedTagOrder.indexOf(right.slug); if (leftRelated !== -1 || rightRelated !== -1) return leftRelated === -1 ? 1 : rightRelated === -1 ? -1 : leftRelated - rightRelated; return left.sort_order - right.sort_order }).map((tag) => { const related = relatedTagOrder.includes(tag.slug); return <button key={tag.id} type="button" className={`preference-chip${related ? ' related' : ''}`} aria-pressed="false" onClick={() => setSelectedTags((current) => [...current, tag.slug])}>{tag.name}</button>})}</div></fieldset>}
          <fieldset><legend>Ngân sách mỗi người</legend><div className="budget-grid">{[['under-100', 'Dưới 100k'], ['100-300', '100–300k'], ['300-500', '300–500k'], ['over-500', 'Trên 500k']].map(([value, label]) => <button key={value} type="button" className={budget === value ? 'choice active' : 'choice'} aria-pressed={budget === value} onClick={() => setBudget(value)}>{label}</button>)}</div></fieldset>
          <button type="submit" disabled={isLoading || !districtId || !categorySlug}><span>{isLoading ? 'Đang tìm địa điểm...' : 'Xem gợi ý'}</span><span aria-hidden="true">→</span></button>
        </form>
        <section className="results" aria-live="polite">
          <div className="results-heading"><span>02</span><h2>Địa điểm dành cho bạn</h2></div>
          {error && <p className="notice">Không thể tải gợi ý: {error}</p>}
          {isLoading && <div className="loading-list" aria-label="Đang tìm quán"><i /><i /><i /></div>}
          {!result && !error && !isLoading && <div className="empty"><strong>Chưa biết đi đâu cũng không sao.</strong><p>Chọn khoảng cách, ngân sách và trải nghiệm đang muốn, rồi để tụi mình gợi ý.</p></div>}
          {result && result.places.length === 0 && <p className="empty">Chưa tìm thấy nơi phù hợp trong phạm vi này.</p>}
          {result?.meta.fallback_applied && <p className="result-note">Đã mở rộng phạm vi tới {result.meta.query_radius_km} km để đủ lựa chọn.</p>}
          <div className="place-list">
            {result?.places.map((place, index) => (
              <article className="place" key={place.id}>
                <span className="place-index">0{index + 1}</span>
                {place.cover_image && <img src={place.cover_image} alt="" />}
                <div><p className="place-category">{place.category?.name} · {place.district?.name}</p><h3>{place.name}</h3><p>{place.address}</p><p className="metadata">{place.distance_km.toFixed(1)} km <span>•</span> {place.price_display} <span>•</span> ★ {place.rating.toFixed(1)}</p>{place.matched_tags.length > 0 && <div className="matched-tags">{place.tags.filter((tag) => place.matched_tags.includes(tag.slug)).map((tag) => <span key={tag.slug}>{tag.name}</span>)}</div>}</div>
              </article>
            ))}
          </div>
        </section>
      </section>
      <footer><strong>Hôm nay ăn gì</strong><span>Ăn ngon, uống vui, chọn nhanh, khỏi nghĩ nhiều.</span></footer>
    </main>
  )
}

export default App
