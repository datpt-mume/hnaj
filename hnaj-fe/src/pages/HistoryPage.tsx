import { useCallback, useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { RiGridFill, RiListCheck } from 'react-icons/ri'
import { AuthNav } from '../components/AuthNav'
import { EmptyState } from '../components/EmptyState'
import { PlaceCard } from '../components/PlaceCard'
import { PlaceListCard } from '../components/PlaceListCard'
import { Skeleton } from '../components/Skeleton'
import { useAuth } from '../hooks/useAuth'
import { useGoThere } from '../hooks/useGoThere'
import { createBookmark, deleteBookmark } from '../services/bookmarkService'
import { listVisitHistory } from '../services/visitService'
import type { VisitHistoryPlace } from '../services/visitService'
import { getApiErrorMessage } from '../services/httpClient'

type ViewMode = 'grid' | 'list'

const VIEW_STORAGE_KEY = 'hnaj.history.view'

function readStoredViewMode(): ViewMode {
  try {
    const value = window.localStorage.getItem(VIEW_STORAGE_KEY)
    if (value === 'grid' || value === 'list') return value
  } catch {
    // localStorage có thể bị chặn; fallback mặc định.
  }
  return 'grid'
}

export function HistoryPage() {
  useAuth()
  const navigate = useNavigate()
  const goThere = useGoThere()

  const [places, setPlaces] = useState<VisitHistoryPlace[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [viewMode, setViewMode] = useState<ViewMode>(() => readStoredViewMode())
  const [isBookmarkLoading, setIsBookmarkLoading] = useState<number | null>(null)

  const loadHistory = useCallback(async (targetPage: number, signal?: AbortSignal) => {
    setIsLoading(true)
    setError('')
    try {
      const result = await listVisitHistory({ page: targetPage, per_page: 10, signal })
      setPlaces(result.places)
      setLastPage(result.meta.last_page)
      setTotal(result.meta.total)
    } catch (requestError) {
      if (signal?.aborted) return
      setError(getApiErrorMessage(requestError, 'Không thể tải lịch sử đi tới. Hãy thử lại.'))
    } finally {
      if (!signal?.aborted) setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    const controller = new AbortController()
    void loadHistory(page, controller.signal)
    return () => controller.abort()
  }, [page, loadHistory])

  function handleViewModeChange(mode: ViewMode) {
    setViewMode(mode)
    try {
      window.localStorage.setItem(VIEW_STORAGE_KEY, mode)
    } catch {
      // Bỏ qua nếu localStorage không khả dụng.
    }
  }

  function handleNavigate(place: VisitHistoryPlace) {
    goThere(place, 'history')
  }

  function handleDetails(place: VisitHistoryPlace) {
    navigate(`/places/${place.id}`, { state: { place } })
  }

  async function handleBookmark(place: VisitHistoryPlace) {
    setIsBookmarkLoading(place.id)
    const previous = places
    const isBookmarked = Boolean(place.is_bookmarked)

    setPlaces((current) =>
      current.map((p) => (p.id === place.id ? { ...p, is_bookmarked: !isBookmarked } : p)),
    )

    try {
      if (isBookmarked) {
        await deleteBookmark(place.id)
      } else {
        await createBookmark(place.id)
      }
    } catch {
      setPlaces(previous)
    } finally {
      setIsBookmarkLoading(null)
    }
  }

  return (
    <main className="bookmarks-shell" aria-label="Lịch sử đi tới">
      <nav className="home-nav" aria-label="Điều hướng chính">
        <Link className="wordmark" to="/" aria-label="Hôm nay ăn gì? - Trang chủ">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <div className="home-nav__links">
          <Link className="home-nav__link" to="/">Khám phá</Link>
          <Link className="home-nav__link" to="/bookmarks">Điểm đến yêu thích</Link>
          <Link className="home-nav__link home-nav__link--active" to="/history">Lịch sử</Link>
        </div>
        <AuthNav />
      </nav>

      <section className="bookmarks-content" aria-labelledby="history-title">
        <header className="bookmarks-content__header">
          <div className="bookmarks-content__titles">
            <h1 id="history-title">Lịch sử đi tới</h1>
            {total > 0 ? (
              <p className="bookmarks-content__count">{total} địa điểm đã ghé</p>
            ) : null}
          </div>

          {!isLoading && !error && places.length > 0 ? (
            <div className="bookmarks-view-toggle" role="group" aria-label="Kiểu hiển thị">
              <button
                type="button"
                className={`bookmarks-view-toggle__button${viewMode === 'grid' ? ' bookmarks-view-toggle__button--active' : ''}`}
                aria-label="Xem dạng lưới"
                aria-pressed={viewMode === 'grid'}
                onClick={() => handleViewModeChange('grid')}
              >
                <RiGridFill aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`bookmarks-view-toggle__button${viewMode === 'list' ? ' bookmarks-view-toggle__button--active' : ''}`}
                aria-label="Xem dạng danh sách"
                aria-pressed={viewMode === 'list'}
                onClick={() => handleViewModeChange('list')}
              >
                <RiListCheck aria-hidden="true" />
              </button>
            </div>
          ) : null}
        </header>

        {error ? (
          <div className="bookmarks-content__error" role="alert">
            <p>{error}</p>
            <button className="button button--secondary" type="button" onClick={() => void loadHistory(page)}>
              Thử lại
            </button>
          </div>
        ) : null}

        {isLoading ? (
          viewMode === 'grid' ? (
            <div className="bookmarks-grid" aria-label="Đang tải lịch sử" aria-busy="true">
              {Array.from({ length: 6 }, (_, i) => (
                <div key={i} className="place-card place-card--loading">
                  <Skeleton className="skeleton--media" />
                  <div className="place-card__body">
                    <Skeleton className="skeleton--line" />
                    <Skeleton className="skeleton--title" />
                    <Skeleton className="skeleton--line" />
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="bookmarks-list" aria-label="Đang tải lịch sử" aria-busy="true">
              {Array.from({ length: 4 }, (_, i) => (
                <div key={i} className="place-list-card place-list-card--loading">
                  <Skeleton className="skeleton--media place-list-card__media-skeleton" />
                  <div className="place-list-card__body">
                    <Skeleton className="skeleton--line" />
                    <Skeleton className="skeleton--title" />
                    <Skeleton className="skeleton--line" />
                  </div>
                </div>
              ))}
            </div>
          )
        ) : !error && places.length === 0 ? (
          <EmptyState
            title="Chưa có lịch sử đi tới"
            description="Khi bạn bấm “Đi tới đó”, địa điểm sẽ xuất hiện ở đây."
            action={
              <Link className="button button--flame" to="/">
                Bắt đầu khám phá
              </Link>
            }
          />
        ) : (
          <>
            {viewMode === 'grid' ? (
              <div className="bookmarks-grid">
                {places.map((place) => (
                  <PlaceCard
                    key={place.id}
                    place={place}
                    isBookmarked={Boolean(place.is_bookmarked)}
                    isBookmarkLoading={isBookmarkLoading === place.id}
                    onNavigate={() => handleNavigate(place)}
                    onDetails={() => handleDetails(place)}
                    onBookmark={() => void handleBookmark(place)}
                  />
                ))}
              </div>
            ) : (
              <div className="bookmarks-list">
                {places.map((place) => (
                  <PlaceListCard
                    key={place.id}
                    place={place}
                    isBookmarked={Boolean(place.is_bookmarked)}
                    isBookmarkLoading={isBookmarkLoading === place.id}
                    onNavigate={() => handleNavigate(place)}
                    onDetails={() => handleDetails(place)}
                    onBookmark={() => void handleBookmark(place)}
                  />
                ))}
              </div>
            )}

            {lastPage > 1 ? (
              <nav className="bookmarks-pagination" aria-label="Phân trang lịch sử">
                <button
                  className="button button--secondary"
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => p - 1)}
                >
                  Trang trước
                </button>
                <span className="bookmarks-pagination__info">Trang {page} / {lastPage}</span>
                <button
                  className="button button--secondary"
                  type="button"
                  disabled={page >= lastPage}
                  onClick={() => setPage((p) => p + 1)}
                >
                  Trang sau
                </button>
              </nav>
            ) : null}
          </>
        )}
      </section>
    </main>
  )
}