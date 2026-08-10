import { RiMapPin2Line } from 'react-icons/ri'
import type { DiscoveryPlace } from '../services/discoveryService'

export function SearchResultCard({ place }: { place: DiscoveryPlace }) {
  return (
    <article className="search-result-card">
      <div className="search-result-card__media">
        {place.thumbnail ? (
          <img
            src={place.thumbnail.image_url}
            alt={place.thumbnail.alt_text || place.name}
            loading="lazy"
            width="320"
            height="180"
          />
        ) : (
          <div className="search-result-card__placeholder" aria-hidden="true">
            {place.name.charAt(0)}
          </div>
        )}
      </div>
      <div className="search-result-card__body">
        <p className="search-result-card__category">{place.category?.name ?? 'Địa điểm'}</p>
        <h3 className="search-result-card__name">{place.name}</h3>
        <p className="search-result-card__address">
          <RiMapPin2Line aria-hidden="true" />
          {/* address_text already contains district, city and country. */}
          <span>{place.address_text}</span>
        </p>
        {place.tags.length > 0 ? (
          <ul className="search-result-card__tags" aria-label="Tags">
            {place.tags.map((tag) => (
              <li key={tag.id}>
                <span className="tag">#{tag.name}</span>
              </li>
            ))}
          </ul>
        ) : null}
        <a
          className="button button--flame search-result-card__navigate"
          href={place.google_maps_url}
          target="_blank"
          rel="noopener noreferrer"
        >
          Đi tới đó
        </a>
      </div>
    </article>
  )
}
