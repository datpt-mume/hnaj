import { useCallback, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { RiDiceLine, RiMapPin2Line } from 'react-icons/ri'
import { AuthNav } from '../components/AuthNav'
import FoodPosterSlideshow from '../components/FoodPosterSlideshow'
import { FilterPanel } from '../components/FilterPanel'
import type { FilterState } from '../components/FilterPanel'
import { RecommendationModal } from '../components/RecommendationModal'
import { Toggle } from '../components/Toggle'
import { useAuth } from '../hooks/useAuth'
import { randomPlace } from '../services/discoveryService'
import type { DiscoveryFilters, DiscoveryPlace } from '../services/discoveryService'
import { DEFAULT_MIN_PRICE, DEFAULT_MAX_PRICE } from '../components/FilterPanel'
import { getApiErrorMessage } from '../services/httpClient'

const DEFAULT_FILTERS: FilterState = {
  categoryId: null,
  districtId: null,
  minPrice: DEFAULT_MIN_PRICE,
  maxPrice: DEFAULT_MAX_PRICE,
  tagIds: [],
  openNow: true,
  useLocation: false,
  location: null,
  locationDenied: false,
}

export function HomePage() {
  const { user } = useAuth()
  const navigate = useNavigate()

  const [filters, setFilters] = useState<FilterState>(DEFAULT_FILTERS)
  const [place, setPlace] = useState<DiscoveryPlace | null>(null)
  const [excluded, setExcluded] = useState<number[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [hasRolled, setHasRolled] = useState(false)
  const [isResultOpen, setIsResultOpen] = useState(false)

  const buildRequest = useCallback(
    (): DiscoveryFilters => {
      const request: DiscoveryFilters = {
        open_now: filters.openNow,
      }
      if (filters.categoryId !== null) request.category_id = filters.categoryId
      if (filters.districtId !== null && !filters.useLocation) {
        request.district_id = filters.districtId
      }
      if (filters.minPrice > 0) request.min_price = filters.minPrice
      if (filters.maxPrice < DEFAULT_MAX_PRICE) request.max_price = filters.maxPrice
      if (filters.tagIds.length > 0) request.tag_ids = [...filters.tagIds]
      if (filters.useLocation && filters.location) {
        request.lat = filters.location.lat
        request.lng = filters.location.lng
      }
      return request
    },
    [filters],
  )

  const runRandom = useCallback(
    async (exclude: number[]) => {
      setIsLoading(true)
      setIsResultOpen(true)
      setError('')
      try {
        const next = await randomPlace(buildRequest(), exclude)
        setPlace(next)
        setExcluded(next ? [...exclude, next.id] : exclude)
        setHasRolled(true)
        if (!next) setError('Hãy nới lỏng bộ lọc (bớt tag, mở rộng khoảng giá hoặc đổi khu vực) rồi thử lại.')
      } catch (requestError) {
        setPlace(null)
        setError(getApiErrorMessage(requestError, 'Không thể kết nối máy chủ. Hãy thử lại.'))
      } finally {
        setIsLoading(false)
      }
    },
    [buildRequest],
  )

  function handleRandom(event: FormEvent) {
    event.preventDefault()
    void runRandom([])
  }

  function handleRoll() {
    void runRandom(excluded)
  }

  function handleNavigate() {
    if (!place) return
    window.open(place.google_maps_url, '_blank', 'noopener,noreferrer')
    // TODO(visit): ghi visit event khi backend có API; hiện chỉ mở Maps.
  }

  function handleDetails() {
    if (!place) return
    navigate(`/places/${place.id}`, { state: { place } })
  }

  function handleCloseResult() {
    setIsResultOpen(false)
  }

  function handleBookmark() {
    if (!user) {
      navigate('/login', { state: { from: '/' } })
      return
    }
    // TODO(bookmark): gọi bookmark API khi backend có API.
  }

  return (
    <main className="home-shell" aria-label="Trang chủ Hôm nay ăn gì">
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
          {user ? (
            <>
              <Link className="home-nav__link" to="/bookmarks">Điểm đến yêu thích</Link>
              <Link className="home-nav__link" to="/history">Lịch sử</Link>
            </>
          ) : null}
        </div>
        <form
          className="home-search"
          role="search"
          onSubmit={(event) => {
            event.preventDefault()
            const form = event.currentTarget
            const value = new FormData(form).get('search')?.toString().trim() ?? ''
            if (value) {
              navigate(`/search?q=${encodeURIComponent(value)}`)
            }
          }}
        >
          <label className="sr-only" htmlFor="home-search-input">Tìm kiếm địa điểm</label>
          <input
            id="home-search-input"
            type="search"
            name="search"
            placeholder="Tìm kiếm địa điểm, món ăn..."
          />
        </form>
        <AuthNav />
      </nav>

      <section className="home-discover" aria-labelledby="discover-title">
        <div className="home-discover__layout">
          <header className="home-discover__header">
            <p className="home-discover__kicker">Hôm nay ăn gì?</p>
            <h1 id="discover-title">Không biết đi đâu hay ăn gì? Đã có <span className="brand-primary-text">Hôm nay ăn gì</span></h1>
            <p className="home-discover__lead">
              Dành cho những ngày không muốn suy nghĩ nhiều. Bỏ qua mọi lo toan và hãy để Hôm nay ăn gì quyết định giúp bạn nha
            </p>
          </header>

          <form className="home-discover__form" onSubmit={handleRandom}>
            <FilterPanel filters={filters} onChange={setFilters} />

            <div className="home-discover__footer">
              <Toggle
                id="open-now"
                label="Đang mở cửa"
                hint="Bỏ chọn để xem cả nơi chưa rõ giờ"
                checked={filters.openNow}
                onChange={(openNow) => setFilters((current) => ({ ...current, openNow }))}
              />
              <div className="home-discover__submit">
                <button className="button button--flame button--random" type="submit">
                  <RiDiceLine aria-hidden="true" />
                  {hasRolled ? 'Đề xuất địa điểm khác' : 'Đề xuất cho tôi một nơi'}
                </button>
              </div>
            </div>
          </form>

          <FoodPosterSlideshow />
        </div>

        <RecommendationModal
          open={isResultOpen}
          place={place}
          isLoading={isLoading}
          error={error}
          onClose={handleCloseResult}
          onRetry={() => void runRandom(excluded)}
          onRoll={() => void handleRoll()}
          onNavigate={handleNavigate}
          onDetails={handleDetails}
          onBookmark={handleBookmark}
        />
      </section>
    </main>
  )
}
