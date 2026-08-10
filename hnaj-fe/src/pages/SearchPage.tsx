import { useCallback, useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { RiSearchLine } from 'react-icons/ri'
import { AuthNav } from '../components/AuthNav'
import { EmptyState } from '../components/EmptyState'
import { SearchResultCard } from '../components/SearchResultCard'
import { Skeleton } from '../components/Skeleton'
import { searchPlaces } from '../services/placeSearchService'
import type { DiscoveryPlace } from '../services/discoveryService'
import { getApiErrorMessage } from '../services/httpClient'

const PER_PAGE = 10

function buildPageHref(query: string, page: number): string {
  const params = new URLSearchParams({ q: query })
  if (page > 1) params.set('page', String(page))
  return `/search?${params.toString()}`
}

export function SearchPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const query = searchParams.get('q')?.trim() ?? ''
  const page = Math.max(1, Number(searchParams.get('page') ?? 1) || 1)

  const [inputValue, setInputValue] = useState(query)
  const [places, setPlaces] = useState<DiscoveryPlace[]>([])
  const [total, setTotal] = useState(0)
  const [lastPage, setLastPage] = useState(1)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [hasSearched, setHasSearched] = useState(false)

  useEffect(() => {
    setInputValue(query)
  }, [query])

  const abortRef = useRef<AbortController | null>(null)

  const runSearch = useCallback(async (q: string, p: number) => {
    // Hủy request trước để response cũ không ghi đè response mới khi query
    // hoặc page đổi nhanh.
    abortRef.current?.abort()
    const controller = new AbortController()
    abortRef.current = controller

    setIsLoading(true)
    setError('')
    try {
      const result = await searchPlaces(q, p, PER_PAGE, { signal: controller.signal })
      setPlaces(result.places)
      setTotal(result.meta.total)
      setLastPage(result.meta.last_page)
      setHasSearched(true)
    } catch (requestError) {
      if (controller.signal.aborted) return
      setPlaces([])
      setTotal(0)
      setLastPage(1)
      setHasSearched(true)
      setError(getApiErrorMessage(requestError, 'Không thể kết nối máy chủ. Hãy thử lại.'))
    } finally {
      if (!controller.signal.aborted) {
        setIsLoading(false)
      }
    }
  }, [])

  useEffect(() => {
    if (!query) return
    void runSearch(query, page)
  }, [query, page, runSearch])

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    const q = inputValue.trim()
    setSearchParams(q ? { q } : {})
  }

  const noQuery = query === ''

  return (
    <main className="search-shell" aria-label="Tìm kiếm địa điểm HNAJ">
      <nav className="search-nav" aria-label="Điều hướng chính">
        <Link className="wordmark" to="/" aria-label="Hôm nay ăn gì? - Trang chủ">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <form className="search-form" role="search" onSubmit={handleSubmit}>
          <label className="sr-only" htmlFor="search-input">Tìm kiếm địa điểm</label>
          <RiSearchLine className="search-form__icon" aria-hidden="true" />
          <input
            id="search-input"
            type="search"
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            placeholder="Tìm kiếm địa điểm, món ăn..."
          />
          <button className="button button--secondary search-form__submit" type="submit">
            Tìm
          </button>
        </form>
        <Link className="search-nav__back" to="/">← Về khám phá</Link>
        <AuthNav />
      </nav>

      <section className="search-results" aria-label="Kết quả tìm kiếm">
        {noQuery ? (
          <EmptyState
            title="Bạn muốn tìm gì?"
            description="Nhập từ khóa tên địa điểm, món ăn, địa chỉ hoặc tag để tìm kiếm."
          />
        ) : null}

        {isLoading ? (
          <div className="search-results__list" aria-hidden="true">
            {Array.from({ length: 3 }, (_, i) => (
              <div className="search-result-card" key={i}>
                <Skeleton className="skeleton--media" />
                <div className="search-result-card__body">
                  <Skeleton className="skeleton--line" />
                  <Skeleton className="skeleton--title" />
                  <Skeleton className="skeleton--line" />
                </div>
              </div>
            ))}
          </div>
        ) : null}

        {!isLoading && error ? (
          <EmptyState
            title="Không thể tải kết quả."
            description={error}
            action={
              <button className="button button--secondary" type="button" onClick={() => void runSearch(query, page)}>
                Thử lại
              </button>
            }
          />
        ) : null}

        {!isLoading && !error && hasSearched && query !== '' && places.length === 0 ? (
          <EmptyState
            title={`Không tìm thấy địa điểm phù hợp với "${query}".`}
            description="Hãy thử từ khóa khác, ví dụ tên quán, món ăn hoặc khu vực."
          />
        ) : null}

        {!isLoading && !error && query !== '' && places.length > 0 ? (
          <>
            <p className="search-results__summary" role="status">
              {total} địa điểm phù hợp với "{query}"
            </p>
            <ul className="search-results__list">
              {places.map((place) => (
                <li key={place.id}>
                  <SearchResultCard place={place} />
                </li>
              ))}
            </ul>
            {lastPage > 1 ? (
              <nav className="pagination" aria-label="Phân trang kết quả">
                {page > 1 ? (
                  <Link className="pagination__link" to={buildPageHref(query, page - 1)}>
                    ← Trang trước
                  </Link>
                ) : (
                  <span className="pagination__link pagination__link--disabled">← Trang trước</span>
                )}
                <span className="pagination__current">
                  Trang {page} / {lastPage}
                </span>
                {page < lastPage ? (
                  <Link className="pagination__link" to={buildPageHref(query, page + 1)}>
                    Trang sau →
                  </Link>
                ) : (
                  <span className="pagination__link pagination__link--disabled">Trang sau →</span>
                )}
              </nav>
            ) : null}
          </>
        ) : null}
      </section>
    </main>
  )
}
