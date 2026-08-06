import { RiBookmarkLine, RiMapPin2Line, RiNavigationFill, RiRefreshLine } from 'react-icons/ri'
import type { DiscoveryPlace } from '../services/discoveryService'

function formatVnd(value: number | null): string | null {
  if (value === null) return null
  return new Intl.NumberFormat('vi-VN').format(value)
}

function priceLabel(place: DiscoveryPlace): string | null {
  const min = formatVnd(place.min_price)
  const max = formatVnd(place.max_price)
  if (min && max) return `${min}đ – ${max}đ`
  if (min) return `từ ${min}đ`
  if (max) return `đến ${max}đ`
  return null
}

type PlaceCardProps = {
  place: DiscoveryPlace
  onRoll: () => void
  onNavigate: () => void
  onBookmark: () => void
  isRolling: boolean
}

export function PlaceCard({
  place,
  onRoll,
  onNavigate,
  onBookmark,
  isRolling,
}: PlaceCardProps) {
  const price = priceLabel(place)

  return (
    <article className="place-card" aria-live="polite">
      <div className="place-card__media">
        {place.thumbnail ? (
          <img
            src={place.thumbnail.image_url}
            alt={place.thumbnail.alt_text || place.name}
            loading="lazy"
            width="640"
            height="360"
          />
        ) : (
          <div className="place-card__placeholder" aria-hidden="true">
            {place.name.charAt(0)}
          </div>
        )}
      </div>

      <div className="place-card__body">
        <div className="place-card__heading">
          <p className="place-card__category">
            {place.category?.name ?? 'Địa điểm'}
          </p>
          <button
            className="icon-button"
            type="button"
            aria-label="Lưu bookmark"
            onClick={onBookmark}
          >
            <RiBookmarkLine aria-hidden="true" />
          </button>
        </div>

        <h3 className="place-card__name">{place.name}</h3>

        <p className="place-card__address">
          <RiMapPin2Line aria-hidden="true" />
          <span>
            {place.address_text}
            {place.district ? `, ${place.district.name}` : ''}
          </span>
        </p>

        {price ? <p className="place-card__price">{price}</p> : null}

        {place.tags.length > 0 ? (
          <ul className="place-card__tags" aria-label="Tags">
            {place.tags.map((tag) => (
              <li key={tag.id}>
                <span className="tag">#{tag.name}</span>
              </li>
            ))}
          </ul>
        ) : null}

        <div className="place-card__actions">
          <button
            className="button button--flame button--navigate"
            type="button"
            onClick={onNavigate}
          >
            <RiNavigationFill aria-hidden="true" />
            Đi tới đó
          </button>
          <button
            className="button button--secondary button--roll"
            type="button"
            onClick={onRoll}
            disabled={isRolling}
          >
            <RiRefreshLine aria-hidden="true" />
            {isRolling ? 'Đang chọn…' : 'Roll lại'}
          </button>
        </div>
      </div>
    </article>
  )
}