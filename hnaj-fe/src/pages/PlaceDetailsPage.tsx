import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { RiArrowLeftLine, RiBookmarkFill, RiBookmarkLine, RiMapPin2Line, RiNavigationFill } from 'react-icons/ri'
import { ManagerApplicationForm } from '../components/ManagerApplicationForm'
import { useAuth } from '../hooks/useAuth'
import type { DiscoveryPlace } from '../services/discoveryService'
import { createBookmark, deleteBookmark } from '../services/bookmarkService'
import { getApiErrorMessage } from '../services/httpClient'
import { formatVnd } from '../utils/format'

function priceLabel(value: number | null): string | null {
  return formatVnd(value)
}

export function PlaceDetailsPage() {
  const location = useLocation()
  const navigate = useNavigate()
  const { user, isLoading } = useAuth()
  const place = (location.state as { place?: DiscoveryPlace } | null)?.place

  const [isBookmarked, setIsBookmarked] = useState(place?.is_bookmarked ?? false)
  const [isBookmarkLoading, setIsBookmarkLoading] = useState(false)
  const [bookmarkError, setBookmarkError] = useState('')

  if (!place) return <Navigate to="/" replace />

  const price = [priceLabel(place.min_price), priceLabel(place.max_price)].filter(Boolean).join(' – ')
  const placeId = place.id

  async function handleBookmark() {
    if (!user) {
      navigate('/login', { state: { from: `/places/${placeId}` } })
      return
    }

    setIsBookmarkLoading(true)
    setBookmarkError('')

    // Optimistic toggle; rollback nếu API thất bại.
    const previous = isBookmarked
    setIsBookmarked(!previous)

    try {
      if (previous) {
        await deleteBookmark(placeId)
      } else {
        await createBookmark(placeId)
      }
    } catch (error) {
      setIsBookmarked(previous)
      setBookmarkError(getApiErrorMessage(error, 'Không thể cập nhật bookmark. Hãy thử lại.'))
    } finally {
      setIsBookmarkLoading(false)
    }
  }

  return (
    <main className="place-details-shell">
      <div className="place-details">
        <Link className="place-details__back" to="/">
          <RiArrowLeftLine aria-hidden="true" /> Về trang khám phá
        </Link>
        <article className="place-details__card">
          <div className="place-details__media">
            {place.thumbnail ? <img src={place.thumbnail.image_url} alt={place.thumbnail.alt_text || place.name} /> : <span>{place.name.charAt(0)}</span>}
          </div>
          <div className="place-details__body">
            <div className="place-details__heading">
              <p className="place-card__category">{place.category?.name ?? 'Địa điểm'}</p>
              <button
                className={`icon-button${isBookmarked ? ' icon-button--active' : ''}`}
                type="button"
                aria-label={isBookmarked ? 'Bỏ lưu địa điểm yêu thích' : 'Lưu địa điểm yêu thích'}
                aria-pressed={isBookmarked}
                aria-busy={isBookmarkLoading}
                disabled={isBookmarkLoading}
                onClick={() => void handleBookmark()}
              >
                {isBookmarked ? <RiBookmarkFill aria-hidden="true" /> : <RiBookmarkLine aria-hidden="true" />}
              </button>
            </div>
            <h1>{place.name}</h1>
            <p className="place-details__address"><RiMapPin2Line aria-hidden="true" /> {place.address_text}</p>
            {price ? <p className="place-details__price">{price}</p> : null}
            {place.tags.length > 0 ? <ul className="place-card__tags" aria-label="Tags">{place.tags.map((tag) => <li key={tag.id}><span className="tag">#{tag.name}</span></li>)}</ul> : null}
            {bookmarkError ? (
              <p className="place-details__error" role="alert">{bookmarkError}</p>
            ) : null}
            <div className="place-details__actions">
              <a className="button button--flame" href={place.google_maps_url} target="_blank" rel="noreferrer">
                <RiNavigationFill aria-hidden="true" /> Đi tới đó
              </a>
            </div>
          </div>
        </article>
        {!isLoading ? (
          <ManagerApplicationForm
            placeId={place.id}
            placeName={place.name}
            isAuthenticated={Boolean(user)}
          />
        ) : null}
      </div>
    </main>
  )
}
