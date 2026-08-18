import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  Link,
  useLocation,
  useNavigate,
  useParams,
} from 'react-router-dom'
import {
  RiArrowLeftLine,
  RiBookmarkFill,
  RiBookmarkLine,
  RiCheckboxCircleFill,
  RiExternalLinkLine,
  RiGlobalLine,
  RiMapPin2Line,
  RiNavigationFill,
  RiPhoneLine,
  RiShareForwardLine,
  RiTimeLine,
} from 'react-icons/ri'
import { ManagerApplicationForm } from '../components/ManagerApplicationForm'
import { Skeleton } from '../components/Skeleton'
import { useAuth } from '../hooks/useAuth'
import { createBookmark, deleteBookmark } from '../services/bookmarkService'
import type { DiscoveryOpeningHour, DiscoveryPlace } from '../services/discoveryService'
import { ApiRequestError, getApiErrorMessage } from '../services/httpClient'
import { getPlaceDetail, type PlaceDetail, type PlaceImage } from '../services/placeService'
import { OPENING_DAYS } from '../utils/adminPlaceForm'
import { formatVnd } from '../utils/format'

const DAY_LABELS: Record<number, string> = Object.fromEntries(
  OPENING_DAYS.map((day) => [day.dayOfWeek, day.label === 'CN' ? 'Chủ nhật' : `Thứ ${day.label.slice(1)}`]),
)

function priceLabel(min: number | null, max: number | null): string | null {
  const minLabel = formatVnd(min)
  const maxLabel = formatVnd(max)
  if (minLabel && maxLabel) return `${minLabel} – ${maxLabel}`
  return minLabel ?? maxLabel
}

function toPlaceDetailSeed(place: DiscoveryPlace): PlaceDetail {
  return {
    ...place,
    description: null,
    phone: null,
    website_url: null,
    is_verified: true,
    images: place.thumbnail
      ? [{ image_url: place.thumbnail.image_url, alt_text: place.thumbnail.alt_text }]
      : [],
    opening_hours: place.opening_hours ?? [],
  }
}

function galleryImages(place: PlaceDetail): PlaceImage[] {
  if (place.images.length > 0) return place.images
  if (place.thumbnail) {
    return [{ image_url: place.thumbnail.image_url, alt_text: place.thumbnail.alt_text }]
  }
  return []
}

/** Quy ước backend: 2=T2 … 7=T7, 8=CN. JS getDay(): 0=CN … 6=T7. */
function todayDayOfWeek(date = new Date()): number {
  const jsDay = date.getDay()
  return jsDay === 0 ? 8 : jsDay + 1
}

function minutesNow(date = new Date()): number {
  return date.getHours() * 60 + date.getMinutes()
}

function parseHm(value: string | null): number | null {
  if (!value) return null
  const [h, m] = value.split(':').map(Number)
  if (Number.isNaN(h) || Number.isNaN(m)) return null
  return h * 60 + m
}

function isOpenNow(hours: DiscoveryOpeningHour[], date = new Date()): boolean | null {
  if (hours.length === 0) return null
  const today = todayDayOfWeek(date)
  const todayHours = hours.filter((hour) => hour.day_of_week === today)
  if (todayHours.length === 0) return null

  const now = minutesNow(date)
  let open = false
  for (const hour of todayHours) {
    if (hour.schedule_type === 'closed') continue
    if (hour.schedule_type === 'all_day') return true
    const opens = parseHm(hour.opens_at)
    const closes = parseHm(hour.closes_at)
    if (opens === null || closes === null) continue
    if (now >= opens && now < closes) open = true
  }
  // Nếu hôm nay chỉ có closed → đóng; nếu có regular nhưng ngoài giờ → đóng.
  if (todayHours.every((hour) => hour.schedule_type === 'closed')) return false
  return open
}

function hourText(hour: DiscoveryOpeningHour | undefined): string {
  if (!hour) return 'Chưa cập nhật'
  if (hour.schedule_type === 'closed') return 'Đóng cửa'
  if (hour.schedule_type === 'all_day') return 'Cả ngày'
  if (hour.opens_at && hour.closes_at) return `${hour.opens_at} – ${hour.closes_at}`
  return 'Chưa cập nhật'
}

export function PlaceDetailsPage() {
  const { placeId: placeIdParam } = useParams()
  const location = useLocation()
  const navigate = useNavigate()
  const { user, isLoading: isAuthLoading } = useAuth()

  const placeId = Number(placeIdParam)
  const seedFromState = (location.state as { place?: DiscoveryPlace } | null)?.place
  const initialSeed =
    seedFromState && seedFromState.id === placeId ? toPlaceDetailSeed(seedFromState) : null

  const [place, setPlace] = useState<PlaceDetail | null>(initialSeed)
  const [isLoading, setIsLoading] = useState(initialSeed === null)
  const [error, setError] = useState('')
  const [isNotFound, setIsNotFound] = useState(false)
  const [activeImageIndex, setActiveImageIndex] = useState(0)
  const [mediaFailed, setMediaFailed] = useState(false)
  const [isBookmarked, setIsBookmarked] = useState(initialSeed?.is_bookmarked ?? false)
  const [isBookmarkLoading, setIsBookmarkLoading] = useState(false)
  const [bookmarkError, setBookmarkError] = useState('')
  const [shareFeedback, setShareFeedback] = useState('')

  const loadPlace = useCallback(async (signal?: AbortSignal) => {
    if (!Number.isFinite(placeId) || placeId <= 0) {
      setIsNotFound(true)
      setIsLoading(false)
      setPlace(null)
      return
    }

    setIsLoading(true)
    setError('')
    setIsNotFound(false)

    try {
      const data = await getPlaceDetail(placeId)
      if (signal?.aborted) return
      setPlace(data)
      setIsBookmarked(data.is_bookmarked ?? false)
      setActiveImageIndex(0)
      setMediaFailed(false)
    } catch (requestError) {
      if (signal?.aborted) return
      if (requestError instanceof ApiRequestError && requestError.status === 404) {
        setIsNotFound(true)
        setPlace(null)
        setError('')
      } else {
        setError(getApiErrorMessage(requestError, 'Không thể tải chi tiết địa điểm. Hãy thử lại.'))
      }
    } finally {
      if (!signal?.aborted) setIsLoading(false)
    }
  }, [placeId])

  useEffect(() => {
    const controller = new AbortController()
    void loadPlace(controller.signal)
    return () => controller.abort()
  }, [loadPlace])

  const images = useMemo(() => (place ? galleryImages(place) : []), [place])
  const openStatus = useMemo(
    () => (place ? isOpenNow(place.opening_hours) : null),
    [place],
  )
  const today = todayDayOfWeek()
  const hoursByDay = useMemo(() => {
    const map = new Map<number, DiscoveryOpeningHour>()
    for (const hour of place?.opening_hours ?? []) {
      if (!map.has(hour.day_of_week)) map.set(hour.day_of_week, hour)
    }
    return map
  }, [place])

  async function handleBookmark() {
    if (!place) return
    if (!user) {
      navigate('/login', { state: { from: `/places/${place.id}` } })
      return
    }

    setIsBookmarkLoading(true)
    setBookmarkError('')
    const previous = isBookmarked
    setIsBookmarked(!previous)

    try {
      if (previous) {
        await deleteBookmark(place.id)
      } else {
        await createBookmark(place.id)
      }
    } catch (requestError) {
      setIsBookmarked(previous)
      setBookmarkError(getApiErrorMessage(requestError, 'Không thể cập nhật bookmark. Hãy thử lại.'))
    } finally {
      setIsBookmarkLoading(false)
    }
  }

  async function handleShare() {
    if (!place) return
    const url = window.location.href
    setShareFeedback('')

    try {
      if (navigator.share) {
        await navigator.share({ title: place.name, url })
        return
      }
      await navigator.clipboard.writeText(url)
      setShareFeedback('Đã sao chép liên kết.')
    } catch {
      setShareFeedback('Không thể chia sẻ. Hãy sao chép URL thủ công.')
    }
  }

  if (!Number.isFinite(placeId) || placeId <= 0 || isNotFound) {
    return (
      <main className="place-details-shell">
        <div className="place-details">
          <Link className="place-details__back" to="/">
            <RiArrowLeftLine aria-hidden="true" /> Về trang khám phá
          </Link>
          <section className="place-details__state" aria-labelledby="place-not-found-title">
            <h1 id="place-not-found-title">Địa điểm không tồn tại hoặc đã bị ẩn</h1>
            <p>Có thể địa điểm đã bị gỡ, chưa được xác minh, hoặc đường dẫn không đúng.</p>
            <Link className="button button--flame" to="/">
              Quay lại khám phá
            </Link>
          </section>
        </div>
      </main>
    )
  }

  if (isLoading && !place) {
    return (
      <main className="place-details-shell" aria-busy="true">
        <div className="place-details">
          <Link className="place-details__back" to="/">
            <RiArrowLeftLine aria-hidden="true" /> Về trang khám phá
          </Link>
          <div className="place-details__card place-details__card--loading" aria-label="Đang tải chi tiết địa điểm">
            <Skeleton className="place-details__skeleton-media" />
            <div className="place-details__body">
              <Skeleton className="place-details__skeleton-line place-details__skeleton-line--sm" />
              <Skeleton className="place-details__skeleton-line place-details__skeleton-line--lg" />
              <Skeleton className="place-details__skeleton-line" />
              <Skeleton className="place-details__skeleton-line" />
              <Skeleton className="place-details__skeleton-line place-details__skeleton-line--md" />
            </div>
          </div>
        </div>
      </main>
    )
  }

  if (error && !place) {
    return (
      <main className="place-details-shell">
        <div className="place-details">
          <Link className="place-details__back" to="/">
            <RiArrowLeftLine aria-hidden="true" /> Về trang khám phá
          </Link>
          <section className="place-details__state" role="alert" aria-labelledby="place-error-title">
            <h1 id="place-error-title">Không tải được địa điểm</h1>
            <p>{error}</p>
            <button className="button button--flame" type="button" onClick={() => void loadPlace()}>
              Thử lại
            </button>
          </section>
        </div>
      </main>
    )
  }

  if (!place) return null

  const price = priceLabel(place.min_price, place.max_price)
  const activeImage = images[activeImageIndex] ?? null
  // Ẩn rating khi chưa có review thật: default DB 5.0 không đủ để hiện.
  // Hiện tại API chưa trả review_count; tạm ẩn rating mặc định 5.0.
  const showRating = place.rating !== null && place.rating !== 5

  return (
    <main className="place-details-shell">
      <div className="place-details">
        <Link className="place-details__back" to="/">
          <RiArrowLeftLine aria-hidden="true" /> Về trang khám phá
        </Link>

        <article className="place-details__card">
          <div className="place-details__gallery">
            <div className="place-details__media" aria-live="polite">
              {activeImage && !mediaFailed ? (
                <img
                  src={activeImage.image_url}
                  alt={activeImage.alt_text || place.name}
                  loading="eager"
                  onError={() => setMediaFailed(true)}
                />
              ) : (
                <span aria-hidden="true">{place.name.charAt(0)}</span>
              )}
            </div>
            {images.length > 1 ? (
              <ul className="place-details__thumbs" aria-label="Thư viện ảnh">
                {images.map((image, index) => (
                  <li key={`${image.image_url}-${index}`}>
                    <button
                      type="button"
                      className={`place-details__thumb${index === activeImageIndex ? ' place-details__thumb--active' : ''}`}
                      aria-label={`Xem ảnh ${index + 1}`}
                      aria-current={index === activeImageIndex ? 'true' : undefined}
                      onClick={() => {
                        setActiveImageIndex(index)
                        setMediaFailed(false)
                      }}
                    >
                      <img src={image.image_url} alt="" loading="lazy" />
                    </button>
                  </li>
                ))}
              </ul>
            ) : null}
          </div>

          <div className="place-details__body">
            <div className="place-details__heading">
              <div className="place-details__kicker">
                <p className="place-card__category">{place.category?.name ?? 'Địa điểm'}</p>
                {place.is_verified ? (
                  <span className="place-details__verified">
                    <RiCheckboxCircleFill aria-hidden="true" />
                    Đã xác minh
                  </span>
                ) : null}
              </div>
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

            <div className="place-details__meta">
              {showRating ? (
                <p className="place-details__rating" aria-label={`Đánh giá ${place.rating} trên 5`}>
                  <span aria-hidden="true">★</span> {place.rating?.toFixed(1)}
                  <span className="place-details__rating-note">Đánh giá HNAJ</span>
                </p>
              ) : null}
              {price ? <p className="place-details__price">{price}</p> : null}
              {place.district ? (
                <p className="place-details__district">{place.district.name}</p>
              ) : null}
              {openStatus !== null ? (
                <p
                  className={`place-details__open-badge${openStatus ? ' place-details__open-badge--open' : ' place-details__open-badge--closed'}`}
                >
                  <RiTimeLine aria-hidden="true" />
                  {openStatus ? 'Đang mở' : 'Đã đóng'}
                </p>
              ) : null}
            </div>

            <p className="place-details__address">
              <RiMapPin2Line aria-hidden="true" /> {place.address_text}
            </p>

            {place.tags.length > 0 ? (
              <ul className="place-card__tags" aria-label="Tags">
                {place.tags.map((tag) => (
                  <li key={tag.id}>
                    <Link className="tag" to={`/search?tag_id=${tag.id}`}>
                      #{tag.name}
                    </Link>
                  </li>
                ))}
              </ul>
            ) : null}

            {bookmarkError ? (
              <p className="place-details__error" role="alert">{bookmarkError}</p>
            ) : null}
            {shareFeedback ? (
              <p className="place-details__feedback" role="status">{shareFeedback}</p>
            ) : null}

            <div className="place-details__actions">
              <a
                className="button button--flame"
                href={place.google_maps_url}
                target="_blank"
                rel="noreferrer"
              >
                <RiNavigationFill aria-hidden="true" /> Đi tới đó
              </a>
              <button className="button button--ghost" type="button" onClick={() => void handleShare()}>
                <RiShareForwardLine aria-hidden="true" /> Chia sẻ
              </button>
            </div>
          </div>
        </article>

        {place.description ? (
          <section className="place-details__section" aria-labelledby="place-description-title">
            <h2 id="place-description-title">Mô tả</h2>
            <p className="place-details__description">{place.description}</p>
          </section>
        ) : null}

        <section className="place-details__section" aria-labelledby="place-hours-title">
          <div className="place-details__section-head">
            <h2 id="place-hours-title">Giờ mở cửa</h2>
            {openStatus !== null ? (
              <span
                className={`place-details__open-badge${openStatus ? ' place-details__open-badge--open' : ' place-details__open-badge--closed'}`}
              >
                <RiTimeLine aria-hidden="true" />
                {openStatus ? 'Đang mở' : 'Đã đóng'}
              </span>
            ) : null}
          </div>
          {place.opening_hours.length === 0 ? (
            <p className="place-details__empty">Chưa cập nhật giờ mở cửa.</p>
          ) : (
            <ul className="place-details__hours">
              {OPENING_DAYS.map((day) => {
                const hour = hoursByDay.get(day.dayOfWeek)
                const isToday = day.dayOfWeek === today
                return (
                  <li
                    key={day.dayOfWeek}
                    className={`place-details__hour-row${isToday ? ' place-details__hour-row--today' : ''}`}
                  >
                    <span className="place-details__hour-day">
                      {DAY_LABELS[day.dayOfWeek]}
                      {isToday ? <span className="place-details__today-pill">Hôm nay</span> : null}
                    </span>
                    <span className="place-details__hour-time">{hourText(hour)}</span>
                  </li>
                )
              })}
            </ul>
          )}
        </section>

        <section className="place-details__section" aria-labelledby="place-contact-title">
          <h2 id="place-contact-title">Liên hệ & vị trí</h2>
          <ul className="place-details__contact">
            {place.phone ? (
              <li>
                <RiPhoneLine aria-hidden="true" />
                <a href={`tel:${place.phone}`}>{place.phone}</a>
              </li>
            ) : null}
            {place.website_url ? (
              <li>
                <RiGlobalLine aria-hidden="true" />
                <a href={place.website_url} target="_blank" rel="noreferrer">
                  Website <RiExternalLinkLine aria-hidden="true" />
                </a>
              </li>
            ) : null}
            <li>
              <RiMapPin2Line aria-hidden="true" />
              <span>{place.address_text}</span>
            </li>
            <li>
              <RiNavigationFill aria-hidden="true" />
              <a href={place.google_maps_url} target="_blank" rel="noreferrer">
                Mở Google Maps <RiExternalLinkLine aria-hidden="true" />
              </a>
            </li>
          </ul>
          {!place.phone && !place.website_url ? (
            <p className="place-details__empty">Chưa có số điện thoại hoặc website.</p>
          ) : null}
        </section>

        <section className="place-details__section place-details__section--muted" aria-labelledby="place-reviews-title">
          <h2 id="place-reviews-title">Đánh giá & bình luận</h2>
          <p className="place-details__empty">
            Tính năng đánh giá và bình luận sẽ sớm ra mắt. Bạn vẫn có thể lưu địa điểm yêu thích và mở chỉ đường.
          </p>
        </section>

        {!isAuthLoading ? (
          <ManagerApplicationForm
            placeId={place.id}
            placeName={place.name}
            isAuthenticated={Boolean(user)}
          />
        ) : null}

        <div className="place-details__sticky-cta" aria-hidden={false}>
          <a
            className="button button--flame place-details__sticky-button"
            href={place.google_maps_url}
            target="_blank"
            rel="noreferrer"
          >
            <RiNavigationFill aria-hidden="true" /> Đi tới đó
          </a>
        </div>
      </div>
    </main>
  )
}
