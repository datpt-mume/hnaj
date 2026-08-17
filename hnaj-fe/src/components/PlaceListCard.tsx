import { useState } from 'react'
import { RiBookmarkFill, RiBookmarkLine, RiEyeLine, RiMapPin2Line, RiNavigationFill } from 'react-icons/ri'
import type { DiscoveryPlace } from '../services/discoveryService'
import { formatVnd } from '../utils/format'

function priceLabel(place: DiscoveryPlace): string | null {
  const min = formatVnd(place.min_price)
  const max = formatVnd(place.max_price)
  if (min && max) return `${min} – ${max}`
  if (min) return `từ ${min}`
  if (max) return `đến ${max}`
  return null
}

type PlaceListCardProps = {
  place: DiscoveryPlace
  onNavigate: () => void
  onBookmark: () => void
  onDetails?: () => void
  isBookmarked?: boolean
  isBookmarkLoading?: boolean
}

export function PlaceListCard({
  place,
  onNavigate,
  onBookmark,
  onDetails,
  isBookmarked = false,
  isBookmarkLoading = false,
}: PlaceListCardProps) {
  const price = priceLabel(place)
  const [imageFailed, setImageFailed] = useState(false)
  const showImage = Boolean(place.thumbnail) && !imageFailed

  return (
    <article className="place-list-card">
      <div className="place-list-card__media">
        {showImage && place.thumbnail ? (
          <img
            src={place.thumbnail.image_url}
            alt={place.thumbnail.alt_text || place.name}
            loading="lazy"
            width="320"
            height="180"
            onError={() => setImageFailed(true)}
          />
        ) : (
          <div className="place-list-card__placeholder" aria-hidden="true">
            {place.name.charAt(0)}
          </div>
        )}
      </div>

      <div className="place-list-card__body">
        <div className="place-list-card__heading">
          <p className="place-list-card__category">
            {place.category?.name ?? 'Địa điểm'}
          </p>
          <button
            className={`icon-button${isBookmarked ? ' icon-button--active' : ''}`}
            type="button"
            aria-label={isBookmarked ? 'Bỏ lưu địa điểm yêu thích' : 'Lưu địa điểm yêu thích'}
            aria-pressed={isBookmarked}
            aria-busy={isBookmarkLoading}
            disabled={isBookmarkLoading}
            onClick={onBookmark}
          >
            {isBookmarked ? (
              <RiBookmarkFill aria-hidden="true" />
            ) : (
              <RiBookmarkLine aria-hidden="true" />
            )}
          </button>
        </div>

        <h3 className="place-list-card__name">{place.name}</h3>

        <p className="place-list-card__address">
          <RiMapPin2Line aria-hidden="true" />
          <span>
            {place.address_text}
            {place.district ? `, ${place.district.name}` : ''}
          </span>
        </p>

        {price ? <p className="place-list-card__price">{price}</p> : null}

        {place.tags.length > 0 ? (
          <ul className="place-list-card__tags" aria-label="Tags">
            {place.tags.map((tag) => (
              <li key={tag.id}>
                <span className="tag">#{tag.name}</span>
              </li>
            ))}
          </ul>
        ) : null}

        <div className="place-list-card__actions">
          {onDetails ? (
            <button className="button button--secondary" type="button" onClick={onDetails}>
              <RiEyeLine aria-hidden="true" />
              Xem chi tiết
            </button>
          ) : null}
          <button
            className="button button--flame"
            type="button"
            onClick={onNavigate}
          >
            <RiNavigationFill aria-hidden="true" />
            Đi tới đó
          </button>
        </div>
      </div>
    </article>
  )
}
