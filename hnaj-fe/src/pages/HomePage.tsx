import { useCallback, useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { RiAccountCircleLine, RiDiceLine, RiMapPin2Line } from 'react-icons/ri'
import { EmptyState } from '../components/EmptyState'
import { FilterPanel } from '../components/FilterPanel'
import type { FilterState } from '../components/FilterPanel'
import { PlaceCard } from '../components/PlaceCard'
import { Skeleton } from '../components/Skeleton'
import { useAuth } from '../hooks/useAuth'
import { randomPlace } from '../services/discoveryService'
import type { DiscoveryFilters, DiscoveryPlace } from '../services/discoveryService'
import { DEFAULT_MIN_PRICE, DEFAULT_MAX_PRICE } from '../components/FilterPanel'
import { ApiRequestError } from '../services/httpClient'

const DEFAULT_FILTERS: FilterState = {
  categoryId: null,
  districtId: null,
  minPrice: DEFAULT_MIN_PRICE,
  maxPrice: DEFAULT_MAX_PRICE,
  tagIds: [],
  openNow: true,
  useLocation: false,
  locationDenied: false,
}

type LocationRef = { lat: number; lng: number } | null

export function HomePage() {
  const { user } = useAuth()
  const navigate = useNavigate()

  const [filters, setFilters] = useState<FilterState>(DEFAULT_FILTERS)
  const [place, setPlace] = useState<DiscoveryPlace | null>(null)
  const [excluded, setExcluded] = useState<number[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [hasRolled, setHasRolled] = useState(false)

  const locationRef = useRef<LocationRef>(null)

  useEffect(() => {
    function onLocation(event: Event) {
      const detail = (event as CustomEvent<{ lat: number; lng: number }>).detail
      locationRef.current = { lat: detail.lat, lng: detail.lng }
    }
    window.addEventListener('hnaj:location', onLocation)
    return () => window.removeEventListener('hnaj:location', onLocation)
  }, [])

  const buildRequest = useCallback(
    (): DiscoveryFilters => {
      const request: DiscoveryFilters = {
        open_now: filters.openNow,
      }
      if (filters.categoryId !== null) request.category_id = filters.categoryId
      if (filters.districtId !== null) request.district_id = filters.districtId
      if (filters.minPrice > 0) request.min_price = filters.minPrice
      if (filters.maxPrice < DEFAULT_MAX_PRICE) request.max_price = filters.maxPrice
      if (filters.tagIds.length > 0) request.tag_ids = [...filters.tagIds]
      if (filters.useLocation && locationRef.current) {
        request.lat = locationRef.current.lat
        request.lng = locationRef.current.lng
      }
      return request
    },
    [filters],
  )

  const runRandom = useCallback(
    async (exclude: number[]) => {
      setIsLoading(true)
      setError('')
      try {
        const next = await randomPlace(buildRequest(), exclude)
        setPlace(next)
        setExcluded(next ? [...exclude, next.id] : exclude)
        setHasRolled(true)
      } catch (requestError) {
        setPlace(null)
        setError(
          requestError instanceof ApiRequestError
            ? requestError.message
            : 'Không thể kết nối máy chủ. Hãy thử lại.',
        )
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

  function handleBookmark() {
    if (!user) {
      navigate('/login', { state: { from: '/' } })
      return
    }
    // TODO(bookmark): gọi bookmark API khi backend có API.
  }

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
          <Link className="home-nav__link" to="/bookmarks">Điểm đến yêu thích</Link>
          <Link className="home-nav__link" to="/history">Lịch sử</Link>
          <Link className="home-nav__link" to="/suggest">Đề xuất địa điểm</Link>
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
        <Link className="home-account" to={user ? '/account' : '/login'} aria-label="Mở tài khoản">
          <RiAccountCircleLine aria-hidden="true" />
          <span>{user ? user.full_name.split(' ').slice(-1)[0] : 'Tài khoản'}</span>
        </Link>
      </nav>

      <section className="home-discover" aria-labelledby="discover-title">
        <header className="home-discover__header">
          <p className="home-discover__kicker">Hôm nay ăn gì?</p>
          <h1 id="discover-title">Bớt phân vân. Đi thôi.</h1>
          <p className="home-discover__lead">
            Chọn vài tiêu chí — hoặc không chọn gì — rồi để chúng tôi đề xuất một nơi
            hợp ý cho bạn.
          </p>
        </header>

        <form className="home-discover__form" onSubmit={handleRandom}>
          <FilterPanel filters={filters} onChange={setFilters} />

          <div className="home-discover__submit">
            <button className="button button--flame button--random" type="submit">
              <RiDiceLine aria-hidden="true" />
              {hasRolled ? 'Đề xuất địa điểm khác' : 'Đề xuất cho tôi một nơi'}
            </button>
          </div>
        </form>

        <div className="home-discover__result" aria-live="polite" aria-busy={isLoading}>
          {error ? (
            <EmptyState
              title="Không thể tìm được địa điểm."
              description={error}
              action={
                <button className="button button--secondary" type="button" onClick={() => void runRandom(excluded)}>
                  Thử lại
                </button>
              }
            />
          ) : null}

          {isLoading ? (
            <div className="place-card place-card--loading" aria-hidden="true">
              <Skeleton className="skeleton--media" />
              <div className="place-card__body">
                <Skeleton className="skeleton--line" />
                <Skeleton className="skeleton--title" />
                <Skeleton className="skeleton--line" />
                <Skeleton className="skeleton--title" />
              </div>
            </div>
          ) : null}

          {!isLoading && !error && place ? (
            <PlaceCard
              place={place}
              onRoll={() => void handleRoll()}
              onNavigate={handleNavigate}
              onBookmark={handleBookmark}
              isRolling={false}
            />
          ) : null}

          {!isLoading && !error && hasRolled && !place ? (
            <EmptyState
              title="Không tìm thấy địa điểm phù hợp."
              description="Hãy nới lỏng bộ lọc (bớt tag, mở rộng khoảng giá hoặc đổi khu vực) rồi thử lại."
              action={
                <button
                  className="button button--secondary"
                  type="button"
                  onClick={() => void runRandom([])}
                >
                  Thử lại với bộ lọc hiện tại
                </button>
              }
            />
          ) : null}

          {!isLoading && !error && !hasRolled ? (
            <EmptyState
              title="Còn trống ở đây."
              description="Bấm “Random cho tôi một nơi” để nhận đề xuất đầu tiên."
            />
          ) : null}
        </div>
      </section>
    </main>
  )
}
