import { useEffect, useId } from 'react'
import type { CSSProperties } from 'react'
import { RiCloseLine } from 'react-icons/ri'
import type { DiscoveryPlace } from '../services/discoveryService'
import { PlaceCard } from './PlaceCard'

type RecommendationModalProps = {
  open: boolean
  place: DiscoveryPlace | null
  isLoading: boolean
  error: string
  isBookmarkLoading?: boolean
  onClose: () => void
  onRetry: () => void
  onRoll: () => void
  onNavigate: () => void
  onDetails: () => void
  onBookmark: () => void
}

const CONFETTI_COLORS = ['#EA580C', '#FBBF24', '#2563EB', '#16A34A', '#F97316']

export function RecommendationModal({
  open,
  place,
  isLoading,
  error,
  isBookmarkLoading = false,
  onClose,
  onRetry,
  onRoll,
  onNavigate,
  onDetails,
  onBookmark,
}: RecommendationModalProps) {
  const titleId = useId()

  useEffect(() => {
    if (!open) return

    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.body.style.overflow = previousOverflow
    }
  }, [open])

  useEffect(() => {
    if (!open) return

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') onClose()
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="recommendation-modal" role="presentation">
      <button className="recommendation-modal__backdrop" type="button" aria-label="Đóng kết quả" onClick={onClose} />
      <div className="recommendation-modal__confetti" aria-hidden="true">
        {Array.from({ length: 22 }, (_, index) => (
          <span
            key={index}
            style={{
              '--confetti-color': CONFETTI_COLORS[index % CONFETTI_COLORS.length],
              '--confetti-delay': `${(index % 7) * 80}ms`,
              '--confetti-x': `${((index * 37) % 100) - 50}%`,
            } as CSSProperties}
          />
        ))}
      </div>
      <section className="recommendation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby={titleId}>
        <header className="recommendation-modal__header">
          <div>
            <p className="recommendation-modal__eyebrow">Gợi ý dành cho bạn</p>
            <h2 id={titleId}>{place ? 'Đây là điểm đến hôm nay' : 'Đang tìm một nơi thật hợp gu'}</h2>
          </div>
          <button className="icon-button" type="button" aria-label="Đóng kết quả" onClick={onClose}>
            <RiCloseLine aria-hidden="true" />
          </button>
        </header>

        {error ? (
          <div className="recommendation-modal__message" role="alert">
            <strong>Chưa tìm được địa điểm.</strong>
            <p>{error}</p>
            <button className="button button--secondary" type="button" onClick={onRetry}>Thử lại</button>
          </div>
        ) : null}

        {isLoading ? (
          <div className="place-card place-card--loading" aria-label="Đang tải địa điểm" aria-busy="true">
            <div className="skeleton skeleton--media" />
            <div className="place-card__body">
              <div className="skeleton skeleton--line" />
              <div className="skeleton skeleton--title" />
              <div className="skeleton skeleton--line" />
            </div>
          </div>
        ) : null}

        {!isLoading && !error && place ? (
          <PlaceCard
            place={place}
            onRoll={onRoll}
            onNavigate={onNavigate}
            onDetails={onDetails}
            onBookmark={onBookmark}
            isRolling={false}
            isBookmarked={place.is_bookmarked ?? false}
            isBookmarkLoading={isBookmarkLoading}
          />
        ) : null}
      </section>
    </div>
  )
}
