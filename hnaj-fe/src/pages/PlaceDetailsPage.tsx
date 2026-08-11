import { Link, Navigate, useLocation } from 'react-router-dom'
import { RiArrowLeftLine, RiMapPin2Line, RiNavigationFill } from 'react-icons/ri'
import type { DiscoveryPlace } from '../services/discoveryService'
import { formatVnd } from '../utils/format'

function priceLabel(value: number | null): string | null {
  return formatVnd(value)
}

export function PlaceDetailsPage() {
  const location = useLocation()
  const place = (location.state as { place?: DiscoveryPlace } | null)?.place

  if (!place) return <Navigate to="/" replace />

  const price = [priceLabel(place.min_price), priceLabel(place.max_price)].filter(Boolean).join(' – ')

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
            <p className="place-card__category">{place.category?.name ?? 'Địa điểm'}</p>
            <h1>{place.name}</h1>
            <p className="place-details__address"><RiMapPin2Line aria-hidden="true" /> {place.address_text}</p>
            {price ? <p className="place-details__price">{price}</p> : null}
            {place.tags.length > 0 ? <ul className="place-card__tags" aria-label="Tags">{place.tags.map((tag) => <li key={tag.id}><span className="tag">#{tag.name}</span></li>)}</ul> : null}
            <div className="place-details__actions">
              <a className="button button--flame" href={place.google_maps_url} target="_blank" rel="noreferrer">
                <RiNavigationFill aria-hidden="true" /> Đi tới đó
              </a>
            </div>
          </div>
        </article>
      </div>
    </main>
  )
}
