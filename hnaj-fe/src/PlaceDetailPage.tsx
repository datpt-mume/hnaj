import { useEffect, useState } from 'react'
import { getPlaceDetail } from './api'
import type { Place } from './types'

const CATEGORY_EMOJI: Record<string, string> = {
  'an-uong': '🍜',
  'cafe-do-uong': '☕',
  'bar-nightlife': '🍸',
  'ngoai-troi': '🌿',
  'gaming-giai-tri': '🎮',
  'van-hoa-nghe-thuat': '🎭',
  'suc-khoe-thu-gian': '🧘',
  'mua-sam': '🛍️',
}

type PlaceDetailPageProps = {
  slug: string
}

export function PlaceDetailPage({ slug }: PlaceDetailPageProps) {
  const [place, setPlace] = useState<Place | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false
    setPlace(null)
    setError(null)

    void getPlaceDetail(decodeURIComponent(slug), null)
      .then((detail) => {
        if (!ignore) setPlace(detail)
      })
      .catch((requestError) => {
        if (!ignore) setError(requestError instanceof Error ? requestError.message : 'place.detail_failed')
      })

    return () => { ignore = true }
  }, [slug])

  return (
    <main className="place-page">
      <nav className="place-page-nav">
        <a className="brand" href="/">Hôm nay ăn gì</a>
        <button className="ghost-button" type="button" onClick={() => window.history.back()}>← Quay lại</button>
      </nav>
      {!place && !error && <div className="place-page-state"><strong>Đang tải địa điểm...</strong></div>}
      {error && <div className="place-page-state"><strong>Không thể tải địa điểm</strong><p>{error}</p><a className="primary-button" href="/">Về trang khám phá</a></div>}
      {place && (
        <article className="place-detail">
          <div className="place-detail-hero">
            {place.cover_image
              ? <img src={place.cover_image} alt="" />
              : <div className="place-fallback">{CATEGORY_EMOJI[place.category?.slug ?? ''] ?? '•'}</div>}
          </div>
          <div className="place-detail-copy">
            <p className="result-kicker">{place.category?.name || 'Địa điểm'} · {place.district?.name || 'Hà Nội'}</p>
            <h1>{place.name}</h1>
            <p className="place-detail-address">{place.address}</p>
            <div className="result-stats"><span>{place.price_display}</span><span>★ {place.rating.toFixed(1)}</span>{place.distance_km !== undefined && <span>{place.distance_km.toFixed(1)} km</span>}</div>
            {place.description && <p className="place-detail-description">{place.description}</p>}
            {place.tags.length > 0 && <div className="result-tags">{place.tags.map((tag) => <span key={tag.slug}>{tag.name}</span>)}</div>}
          </div>
        </article>
      )}
    </main>
  )
}