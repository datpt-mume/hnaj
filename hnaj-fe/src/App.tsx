import { useState } from 'react'
import type { FormEvent } from 'react'
import './App.css'

type RecommendationRequest = {
  location: { lat: number; lng: number }
  radius_km: number
  price_max: number
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
}

type RecommendationData = {
  places: Place[]
  meta: { message_key: string; fallback_applied: boolean }
}

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

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

function App() {
  const [radius, setRadius] = useState(3)
  const [budget, setBudget] = useState(150000)
  const [tags, setTags] = useState('')
  const [result, setResult] = useState<RecommendationData | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsLoading(true)
    setError(null)
    try {
      setResult(await recommend({
        location: { lat: 10.762622, lng: 106.660172 },
        radius_km: radius,
        price_max: budget,
        tags: tags.split(',').map((tag) => tag.trim()).filter(Boolean),
        limit: 3,
      }))
    } catch (requestError) {
      setResult(null)
      setError(requestError instanceof Error ? requestError.message : 'recommendation.request_failed')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <main>
      <a className="skip-link" href="#recommendation-form">Chuyển đến bộ lọc</a>
      <nav className="nav" aria-label="Điều hướng chính">
        <a className="brand" href="/" aria-label="Hôm nay ăn gì - Trang chủ"><span className="brand-mark">H?</span><span>Hôm nay ăn gì</span></a>
        <span className="nav-note">Gợi ý món ngon quanh bạn</span>
      </nav>
      <header className="hero">
        <div className="hero-copy">
          <p className="kicker">Đói bụng rồi hả?</p>
          <h1>Chốt món.<br /><em>Đi ăn.</em></h1>
          <p className="intro">Không cần nghĩ lâu. Chọn túi tiền và khoảng cách, tụi mình tìm quán hợp gu ngay.</p>
          <a className="hero-link" href="#recommendation-form">Tìm món ngay <span aria-hidden="true">↓</span></a>
        </div>
        <div className="hero-visual" aria-label="Một tô phở Việt Nam hấp dẫn" role="img">
          <div className="food-stamp"><strong>03</strong><span>gợi ý<br />mỗi lượt</span></div><p>Ngon gần đây</p>
        </div>
      </header>
      <section className="workspace" id="recommendation-form">
        <form className="controls" onSubmit={handleSubmit}>
          <div className="form-heading"><span>01</span><div><h2>Hôm nay mình ăn gì?</h2><p>Cho tụi mình vài manh mối.</p></div></div>
          <label>Vị trí hiện tại<input value="10.762622, 106.660172" readOnly /></label>
          <label className="range-label"><span>Đi xa tối đa <output>{radius} km</output></span><input type="range" min="0.5" max="20" step="0.5" value={radius} onChange={(event) => setRadius(Number(event.target.value))} /></label>
          <label className="range-label"><span>Ngân sách <output>{budget.toLocaleString('vi-VN')}đ</output></span><input type="range" min="50000" max="500000" step="25000" value={budget} onChange={(event) => setBudget(Number(event.target.value))} /></label>
          <label>Đang thèm gì?<input placeholder="Ví dụ: món nước, cay, ngồi ngoài trời" value={tags} onChange={(event) => setTags(event.target.value)} /></label>
          <button type="submit" disabled={isLoading}><span>{isLoading ? 'Đang lục tìm...' : 'Chốt kèo ăn uống'}</span><span aria-hidden="true">→</span></button>
        </form>
        <section className="results" aria-live="polite">
          <div className="results-heading"><span>02</span><h2>Quán dành cho bạn</h2></div>
          {error && <p className="notice">Không thể tải gợi ý: {error}</p>}
          {isLoading && <div className="loading-list" aria-label="Đang tìm quán"><i /><i /><i /></div>}
          {!result && !error && !isLoading && <div className="empty"><strong>Chưa biết ăn gì cũng không sao.</strong><p>Chỉnh vài lựa chọn bên cạnh, rồi để chiếc bụng quyết định.</p><span aria-hidden="true">↗</span></div>}
          {result && result.places.length === 0 && <p className="empty">Chưa tìm thấy nơi phù hợp trong phạm vi này.</p>}
          <div className="place-list">
            {result?.places.map((place, index) => (
              <article className="place" key={place.id}>
                <span className="place-index">0{index + 1}</span>
                <div><h3>{place.name}</h3><p>{place.address}</p><p className="metadata">{place.distance_km.toFixed(1)} km <span>•</span> {place.price_display} <span>•</span> ★ {place.rating.toFixed(1)}</p></div>
              </article>
            ))}
          </div>
        </section>
      </section>
      <footer><strong>Hôm nay ăn gì?</strong><span>Ăn ngon, chọn nhanh, khỏi nghĩ nhiều.</span></footer>
    </main>
  )
}

export default App
