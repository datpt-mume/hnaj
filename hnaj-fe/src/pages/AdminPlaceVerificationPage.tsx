import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useDiscoveryMetadata } from '../hooks/useDiscoveryMetadata'
import {
  createAdminTag,
  deleteAdminPlace,
  getAdminPlace,
  getVerificationQueue,
  updateAdminPlace,
  type AdminPlace,
  type AdminPlaceOpeningHour,
} from '../services/adminPlaceService'
import type { FilterTag } from '../services/metaService'
import { ApiRequestError, getApiErrorMessage } from '../services/httpClient'

const OPENING_DAYS = [
  { label: 'T2', dayOfWeek: 2 },
  { label: 'T3', dayOfWeek: 3 },
  { label: 'T4', dayOfWeek: 4 },
  { label: 'T5', dayOfWeek: 5 },
  { label: 'T6', dayOfWeek: 6 },
  { label: 'T7', dayOfWeek: 7 },
  { label: 'CN', dayOfWeek: 8 },
] as const

function getExternalHttpUrl(value: string): string | null {
  try {
    const url = new URL(value.trim())
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null
  } catch {
    return null
  }
}

function toOpeningHoursState(hours: AdminPlaceOpeningHour[]): AdminPlaceOpeningHour[] {
  const hoursByDay = new Map(hours.map((hour) => [hour.day_of_week, hour]))

  return OPENING_DAYS.map(({ dayOfWeek }) =>
    hoursByDay.get(dayOfWeek) ?? {
      day_of_week: dayOfWeek,
      schedule_type: 'regular',
      opens_at: '08:00',
      closes_at: '22:00',
    },
  )
}

export function AdminPlaceVerificationPage() {
  const { adminUser, signOutAdmin } = useAuth()
  const { metadata: meta } = useDiscoveryMetadata()

  const [queueIds, setQueueIds] = useState<number[]>([])
  const [currentId, setCurrentId] = useState<number | null>(null)
  const [currentIndex, setCurrentIndex] = useState(0)
  const [totalUnverified, setTotalUnverified] = useState(0)
  const [place, setPlace] = useState<AdminPlace | null>(null)
  const [loadingQueue, setLoadingQueue] = useState(true)
  const [loadingPlace, setLoadingPlace] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [adminTags, setAdminTags] = useState<FilterTag[]>([])
  const [newTagName, setNewTagName] = useState('')
  const [creatingTag, setCreatingTag] = useState(false)
  const [tagFeedback, setTagFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null)
  const [showDelete, setShowDelete] = useState(false)

  const [form, setForm] = useState<{
    name: string
    address_text: string
    district_id: string
    category_id: string
    tag_ids: number[]
    phone: string
    website_url: string
    google_maps_url: string
    google_place_id: string
    latitude: string
    longitude: string
    min_price: string
    max_price: string
    description: string
    status: string
    opening_hours: AdminPlaceOpeningHour[]
    images: { id: number | null; image_url: string; alt_text: string }[]
    thumbnail_image_id: string
    deleted_image_ids: number[]
  } | null>(null)

  const loadQueue = useCallback(async () => {
    setLoadingQueue(true)
    setError(null)
    try {
      const result = await getVerificationQueue({ page: 1, per_page: 50 })
      const ids = result.places.map((p) => p.id)
      setQueueIds(ids)
      setTotalUnverified(result.meta.total_unverified)
      if (ids.length > 0) {
        setCurrentId(ids[0])
        setCurrentIndex(0)
      } else {
        setCurrentId(null)
        setPlace(null)
      }
    } catch (err) {
      setError(getApiErrorMessage(err, 'Không tải được hàng đợi kiểm duyệt.'))
    } finally {
      setLoadingQueue(false)
    }
  }, [])

  useEffect(() => {
    void loadQueue()
  }, [loadQueue])

  useEffect(() => {
    if (meta?.tags) setAdminTags(meta.tags)
  }, [meta?.tags])

  const loadPlace = useCallback(async (id: number) => {
    setLoadingPlace(true)
    setError(null)
    setSuccess(null)
    try {
      const data = await getAdminPlace(id)
      setPlace(data)
      setForm({
        name: data.name,
        address_text: data.address_text,
        district_id: String(data.district_id),
        category_id: String(data.category_id),
        tag_ids: data.tags.map((t) => t.id),
        phone: data.phone ?? '',
        website_url: data.website_url ?? '',
        google_maps_url: data.google_maps_url,
        google_place_id: data.google_place_id ?? '',
        latitude: String(data.latitude),
        longitude: String(data.longitude),
        min_price: data.min_price !== null ? String(data.min_price) : '',
        max_price: data.max_price !== null ? String(data.max_price) : '',
        description: data.description ?? '',
        status: data.status,
        opening_hours: toOpeningHoursState(data.opening_hours),
        images: data.images.map((img) => ({ id: img.id, image_url: img.image_url, alt_text: img.alt_text ?? '' })),
        thumbnail_image_id: data.thumbnail_image_id ? String(data.thumbnail_image_id) : data.images[0]?.id ? String(data.images[0].id) : '',
        deleted_image_ids: [],
      })
    } catch (err) {
      setError(getApiErrorMessage(err, 'Không tải được chi tiết địa điểm.'))
    } finally {
      setLoadingPlace(false)
    }
  }, [])

  useEffect(() => {
    if (currentId !== null) void loadPlace(currentId)
  }, [currentId, loadPlace])

  const goToIndex = useCallback(
    (idx: number) => {
      if (idx < 0 || idx >= queueIds.length) return
      setCurrentIndex(idx)
      setCurrentId(queueIds[idx])
      setShowDelete(false)
    },
    [queueIds],
  )

  const handlePrev = useCallback(() => goToIndex(currentIndex - 1), [currentIndex, goToIndex])
  const handleNext = useCallback(() => goToIndex(currentIndex + 1), [currentIndex, goToIndex])

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (showDelete) return
      if (e.key === 'ArrowLeft') handlePrev()
      if (e.key === 'ArrowRight') handleNext()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [handlePrev, handleNext, showDelete])

  const fieldErrors = useMemo(() => new Map<string, string[]>(), [])

  const handleSubmit = useCallback(
    async (e: React.FormEvent) => {
      e.preventDefault()
      if (!place || !form) return
      setSaving(true)
      setError(null)
      setSuccess(null)
      try {
        const payload = {
          name: form.name.trim(),
          address_text: form.address_text.trim(),
          district_id: Number(form.district_id),
          category_id: Number(form.category_id),
          tag_ids: form.tag_ids,
          phone: form.phone.trim() || null,
          website_url: form.website_url.trim() || null,
          google_maps_url: form.google_maps_url.trim(),
          google_place_id: form.google_place_id.trim() || null,
          latitude: Number(form.latitude),
          longitude: Number(form.longitude),
          min_price: form.min_price ? Number(form.min_price) : null,
          max_price: form.max_price ? Number(form.max_price) : null,
          description: form.description.trim() || null,
          status: form.status,
          opening_hours: form.opening_hours,
          images: form.images.map((img) => ({
            id: img.id,
            image_url: img.image_url.trim(),
            alt_text: img.alt_text.trim() || null,
          })),
          thumbnail_image_id: form.thumbnail_image_id ? Number(form.thumbnail_image_id) : null,
          deleted_image_ids: form.deleted_image_ids,
        }

        const result = await updateAdminPlace(place.id, payload as never)
        setSuccess('Đã cập nhật và xác minh địa điểm. Đang chuyển tiếp...')
        // Remove current from queue and go next
        const nextIds = queueIds.filter((id) => id !== place.id)
        setQueueIds(nextIds)
        setTotalUnverified((v) => Math.max(0, v - 1))
        if (result.next_unverified_id && nextIds.includes(result.next_unverified_id)) {
          const idx = nextIds.indexOf(result.next_unverified_id)
          setCurrentIndex(idx)
          setCurrentId(result.next_unverified_id)
        } else if (nextIds.length > 0) {
          const idx = Math.min(currentIndex, nextIds.length - 1)
          setCurrentIndex(idx)
          setCurrentId(nextIds[idx])
        } else {
          setCurrentId(null)
          setPlace(null)
          setForm(null)
        }
      } catch (err) {
        if (err instanceof ApiRequestError && err.errors) {
          const msg = Object.entries(err.errors)
            .map(([k, v]) => `${k}: ${v.join(', ')}`)
            .join(' | ')
          setError(msg || err.message)
        } else {
          setError(getApiErrorMessage(err, 'Cập nhật thất bại.'))
        }
      } finally {
        setSaving(false)
      }
    },
    [place, form, queueIds, currentIndex],
  )

  const handleCreateTag = useCallback(async () => {
    const name = newTagName.trim().replace(/\s+/g, ' ')
    if (!form || name === '') return

    setCreatingTag(true)
    setTagFeedback(null)
    try {
      const tag = await createAdminTag(name)
      setAdminTags((current) => {
        const exists = current.some((item) => item.id === tag.id)
        return exists ? current : [...current, tag].sort((a, b) => a.name.localeCompare(b.name, 'vi'))
      })
      setForm({
        ...form,
        tag_ids: form.tag_ids.includes(tag.id) ? form.tag_ids : [...form.tag_ids, tag.id],
      })
      setNewTagName('')
      setTagFeedback({ type: 'success', message: `Đã thêm và chọn tag “${tag.name}”.` })
    } catch (err) {
      if (err instanceof ApiRequestError && err.errors) {
        const message = Object.values(err.errors).flat().join(' ')
        setTagFeedback({ type: 'error', message: message || err.message })
      } else {
        setTagFeedback({ type: 'error', message: getApiErrorMessage(err, 'Không thêm được tag. Hãy thử lại.') })
      }
    } finally {
      setCreatingTag(false)
    }
  }, [form, newTagName])

  const handleDelete = useCallback(async () => {
    if (!place) return
    setDeleting(true)
    setError(null)
    try {
      const result = await deleteAdminPlace(place.id)
      setSuccess('Đã xóa địa điểm.')
      setShowDelete(false)
      const nextIds = queueIds.filter((id) => id !== place.id)
      setQueueIds(nextIds)
      setTotalUnverified((v) => Math.max(0, v - 1))
      if (result.next_unverified_id && nextIds.includes(result.next_unverified_id)) {
        const idx = nextIds.indexOf(result.next_unverified_id)
        setCurrentIndex(idx)
        setCurrentId(result.next_unverified_id)
      } else if (nextIds.length > 0) {
        const idx = Math.min(currentIndex, nextIds.length - 1)
        setCurrentIndex(idx)
        setCurrentId(nextIds[idx])
      } else {
        setCurrentId(null)
        setPlace(null)
        setForm(null)
      }
    } catch (err) {
      setError(getApiErrorMessage(err, 'Xóa thất bại.'))
    } finally {
      setDeleting(false)
    }
  }, [place, queueIds, currentIndex])

  if (!adminUser) return null

  return (
    <main className="admin-shell admin-verification">
      <nav className="home-nav" aria-label="Điều hướng quản trị">
        <Link className="wordmark" to="/admin" aria-label="HNAJ - Quản trị">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <Link className="text-button" to="/admin">
          Dashboard
        </Link>
        <button className="text-button" type="button" onClick={() => void signOutAdmin()}>
          Đăng xuất admin
        </button>
      </nav>

      <section className="admin-verification__header" aria-labelledby="verification-title">
        <div>
          <p className="home-hero__kicker">Kiểm duyệt thủ công</p>
          <h1 id="verification-title">Làm sạch Places</h1>
          <p className="admin-verification__lead">
            Duyệt lần lượt các địa điểm chưa xác minh. Mỗi lần cập nhật sẽ tự đánh dấu đã xác minh và chuyển tiếp.
          </p>
        </div>
        <div className="admin-verification__progress" aria-live="polite">
          <span className="admin-verification__count">
            {queueIds.length === 0 ? '0 / 0' : `${currentIndex + 1} / ${queueIds.length}`}
          </span>
          <span className="admin-verification__total">Tổng chưa xác minh: {totalUnverified}</span>
          <div className="admin-verification__nav">
            <button type="button" className="button button--secondary" onClick={handlePrev} disabled={currentIndex <= 0} aria-label="Địa điểm trước">
              ← Trước
            </button>
            <button
              type="button"
              className="button button--secondary"
              onClick={handleNext}
              disabled={currentIndex >= queueIds.length - 1}
              aria-label="Địa điểm tiếp theo"
            >
              Tiếp →
            </button>
            <button type="button" className="button button--secondary" onClick={() => void loadQueue()} disabled={loadingQueue}>
              Tải lại
            </button>
          </div>
        </div>
      </section>

      {error && (
        <div className="admin-verification__alert admin-verification__alert--error" role="alert">
          {error}
        </div>
      )}
      {success && (
        <div className="admin-verification__alert admin-verification__alert--success" role="status" aria-live="polite">
          {success}
        </div>
      )}

      {loadingQueue ? (
        <div className="admin-verification__skeleton" aria-busy="true" aria-label="Đang tải hàng đợi">
          <div className="skeleton skeleton--card" />
          <div className="skeleton skeleton--card" />
        </div>
      ) : queueIds.length === 0 ? (
        <div className="admin-verification__empty" role="status">
          <h2>Đã duyệt hết</h2>
          <p>Không còn địa điểm chưa xác minh.</p>
          <button type="button" className="button button--primary" onClick={() => void loadQueue()}>
            Tải lại hàng đợi
          </button>
        </div>
      ) : loadingPlace || !place || !form ? (
        <div className="admin-verification__skeleton" aria-busy="true">
          <div className="skeleton skeleton--card" />
        </div>
      ) : (
        <form className="admin-verification__form" onSubmit={handleSubmit} noValidate>
          <div className="admin-verification__grid">
            <section className="admin-card admin-card--form" aria-labelledby="basic-info">
              <h2 id="basic-info">Thông tin chung</h2>
              <label className="form-field">
                <span>Tên địa điểm *</span>
                <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required maxLength={255} />
              </label>
              <label className="form-field">
                <span>Địa chỉ *</span>
                <textarea value={form.address_text} onChange={(e) => setForm({ ...form, address_text: e.target.value })} required rows={2} />
              </label>
              <div className="form-grid">
                <label className="form-field">
                  <span>Quận *</span>
                  <select value={form.district_id} onChange={(e) => setForm({ ...form, district_id: e.target.value })} required>
                    <option value="">Chọn quận</option>
                    {meta?.districts.map((d) => (
                      <option key={d.id} value={String(d.id)}>
                        {d.name}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="form-field">
                  <span>Danh mục *</span>
                  <select value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} required>
                    <option value="">Chọn danh mục</option>
                    {meta?.categories.map((c) => (
                      <option key={c.id} value={String(c.id)}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                </label>
              </div>
              <div className="form-field admin-verification__tag-field">
                <span>Tags</span>
                <p className="admin-verification__hint">Chọn nhiều tag hoặc tạo tag mới nếu địa điểm có ngữ cảnh chưa có sẵn.</p>
                <div className="admin-verification__tag-create" role="group" aria-label="Thêm tag mới">
                  <input
                    value={newTagName}
                    onChange={(e) => {
                      setNewTagName(e.target.value)
                      setTagFeedback(null)
                    }}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault()
                        void handleCreateTag()
                      }
                    }}
                    placeholder="Nhập tag mới, ví dụ: Ăn khuya"
                    aria-label="Tên tag mới"
                    maxLength={80}
                    disabled={saving || creatingTag}
                  />
                  <button
                    type="button"
                    className="button button--flame button--small"
                    onClick={() => void handleCreateTag()}
                    disabled={saving || creatingTag || newTagName.trim() === ''}
                  >
                    {creatingTag ? 'Đang thêm...' : '+ Thêm tag'}
                  </button>
                </div>
                {tagFeedback ? (
                  <p className={`admin-verification__tag-feedback admin-verification__tag-feedback--${tagFeedback.type}`} role={tagFeedback.type === 'error' ? 'alert' : 'status'}>
                    {tagFeedback.message}
                  </p>
                ) : null}
                <div className="admin-verification__chips" role="group" aria-label="Tags">
                  {adminTags.map((tag) => {
                    const active = form.tag_ids.includes(tag.id)
                    return (
                      <button
                        key={tag.id}
                        type="button"
                        className={`filter-chip admin-verification__tag-chip ${active ? 'filter-chip--active admin-verification__tag-chip--active' : ''}`}
                        aria-pressed={active}
                        onClick={() =>
                          setForm({
                            ...form,
                            tag_ids: active ? form.tag_ids.filter((id) => id !== tag.id) : [...form.tag_ids, tag.id],
                          })
                        }
                      >
                        {active ? '✓ ' : ''}{tag.name}
                      </button>
                    )
                  })}
                </div>
              </div>
              <div className="form-grid">
                <label className="form-field">
                  <span>Trạng thái *</span>
                  <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                    <option value="active">active</option>
                    <option value="hidden">hidden</option>
                  </select>
                </label>
                <label className="form-field">
                  <span>Google Place ID</span>
                  <input value={form.google_place_id} onChange={(e) => setForm({ ...form, google_place_id: e.target.value })} />
                </label>
              </div>
            </section>

            <section className="admin-card admin-card--form" aria-labelledby="contact-price">
              <h2 id="contact-price">Liên hệ và giá</h2>
              <div className="form-grid">
                <label className="form-field">
                  <span>Điện thoại</span>
                  <input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
                </label>
                <label className="form-field">
                  <span>Website</span>
                  <input value={form.website_url} onChange={(e) => setForm({ ...form, website_url: e.target.value })} placeholder="https://" />
                </label>
              </div>
              <div className="form-field">
                <span>Google Maps URL *</span>
                <div className="admin-verification__external-url">
                  <input value={form.google_maps_url} onChange={(e) => setForm({ ...form, google_maps_url: e.target.value })} required aria-label="Google Maps URL" />
                  {getExternalHttpUrl(form.google_maps_url) ? (
                    <a
                      className="button button--secondary"
                      href={getExternalHttpUrl(form.google_maps_url) ?? undefined}
                      target="_blank"
                      rel="noreferrer"
                      aria-label="Mở địa điểm trên Google Maps trong tab mới"
                    >
                      Mở Google Maps ↗
                    </a>
                  ) : (
                    <button type="button" className="button button--secondary" disabled>
                      Mở Google Maps ↗
                    </button>
                  )}
                </div>
                <small className="admin-verification__hint">Chỉ mở liên kết để đối chiếu, không ghi nhận lượt đã đi.</small>
              </div>
              <div className="form-grid">
                <label className="form-field">
                  <span>Vĩ độ *</span>
                  <input type="number" step="0.0000001" value={form.latitude} onChange={(e) => setForm({ ...form, latitude: e.target.value })} required />
                </label>
                <label className="form-field">
                  <span>Kinh độ *</span>
                  <input type="number" step="0.0000001" value={form.longitude} onChange={(e) => setForm({ ...form, longitude: e.target.value })} required />
                </label>
              </div>
              <div className="form-grid">
                <label className="form-field">
                  <span>Giá tối thiểu (VND)</span>
                  <input type="number" min={0} value={form.min_price} onChange={(e) => setForm({ ...form, min_price: e.target.value })} />
                </label>
                <label className="form-field">
                  <span>Giá tối đa (VND)</span>
                  <input type="number" min={0} value={form.max_price} onChange={(e) => setForm({ ...form, max_price: e.target.value })} />
                </label>
              </div>
              <label className="form-field">
                <span>Mô tả</span>
                <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={4} maxLength={5000} />
              </label>
            </section>

            <section className="admin-card admin-card--form" aria-labelledby="hours-title">
              <h2 id="hours-title">Giờ mở cửa (7 ngày)</h2>
              <div className="admin-verification__hours">
                {form.opening_hours.map((h, idx) => (
                  <div key={h.day_of_week} className="admin-verification__hour-row">
                    <span className="admin-verification__day">{OPENING_DAYS[idx].label}</span>
                    <select
                      value={h.schedule_type}
                      onChange={(e) => {
                        const next = [...form.opening_hours]
                        next[idx] = { ...next[idx], schedule_type: e.target.value }
                        setForm({ ...form, opening_hours: next })
                      }}
                      aria-label={`Loại lịch ngày ${OPENING_DAYS[idx].label}`}
                    >
                      <option value="regular">regular</option>
                      <option value="all_day">all_day</option>
                      <option value="closed">closed</option>
                    </select>
                    <input
                      type="time"
                      value={h.opens_at ?? ''}
                      onChange={(e) => {
                        const next = [...form.opening_hours]
                        next[idx] = { ...next[idx], opens_at: e.target.value || null }
                        setForm({ ...form, opening_hours: next })
                      }}
                      disabled={h.schedule_type !== 'regular'}
                      aria-label={`Giờ mở ${OPENING_DAYS[idx].label}`}
                    />
                    <input
                      type="time"
                      value={h.closes_at ?? ''}
                      onChange={(e) => {
                        const next = [...form.opening_hours]
                        next[idx] = { ...next[idx], closes_at: e.target.value || null }
                        setForm({ ...form, opening_hours: next })
                      }}
                      disabled={h.schedule_type !== 'regular'}
                      aria-label={`Giờ đóng ${OPENING_DAYS[idx].label}`}
                    />
                  </div>
                ))}
              </div>
              {fieldErrors.size > 0 && <p className="form-error">Kiểm tra lại giờ mở cửa.</p>}
            </section>

            <section className="admin-card admin-card--form" aria-labelledby="images-title">
              <h2 id="images-title">Ảnh (URL)</h2>
              <p className="admin-verification__hint">Nhập URL ảnh và chọn một thumbnail bắt buộc. Ảnh đang làm thumbnail chỉ có thể xóa khi còn ảnh đã lưu khác để tự thay thế.</p>
              {form.images.map((img, idx) => {
                const previewUrl = getExternalHttpUrl(img.image_url)
                const isThumbnail = img.id !== null && form.thumbnail_image_id === String(img.id)
                const hasReplacementThumbnail = form.images.some((candidate) => candidate.id !== null && candidate.id !== img.id)
                const cannotDelete = isThumbnail && !hasReplacementThumbnail

                return (
                  <div key={img.id ?? `new-${idx}`} className="admin-verification__image-item">
                    <div className="admin-verification__image-row">
                      <input
                        value={img.image_url}
                        onChange={(e) => {
                          const next = [...form.images]
                          next[idx] = { ...next[idx], image_url: e.target.value }
                          setForm({ ...form, images: next })
                        }}
                        placeholder="https://..."
                        aria-label={`URL ảnh ${idx + 1}`}
                      />
                      <input
                        value={img.alt_text}
                        onChange={(e) => {
                          const next = [...form.images]
                          next[idx] = { ...next[idx], alt_text: e.target.value }
                          setForm({ ...form, images: next })
                        }}
                        placeholder="Alt text"
                        aria-label={`Alt ảnh ${idx + 1}`}
                      />
                      <label className="admin-verification__radio">
                        <input
                          type="radio"
                          name="thumbnail"
                          checked={isThumbnail}
                          onChange={() => {
                            if (img.id !== null) setForm({ ...form, thumbnail_image_id: String(img.id) })
                          }}
                          disabled={img.id === null}
                        />
                        Thumbnail
                      </label>
                      <button
                        type="button"
                        className="button button--secondary button--small"
                        disabled={cannotDelete}
                        title={cannotDelete ? 'Không thể xóa thumbnail duy nhất. Hãy thêm và lưu ảnh khác trước.' : undefined}
                        onClick={() => {
                          const next = [...form.images]
                          const removed = next.splice(idx, 1)[0]
                          const deleted = removed.id ? [...form.deleted_image_ids, removed.id] : form.deleted_image_ids
                          const replacement = isThumbnail ? next.find((candidate) => candidate.id !== null) : null
                          setForm({
                            ...form,
                            images: next,
                            deleted_image_ids: deleted,
                            thumbnail_image_id: replacement?.id ? String(replacement.id) : form.thumbnail_image_id,
                          })
                        }}
                      >
                        Xóa
                      </button>
                    </div>
                    <div className={`admin-verification__image-preview ${previewUrl ? '' : 'admin-verification__image-preview--empty'}`}>
                      {previewUrl ? (
                        <img src={previewUrl} alt={img.alt_text.trim() || `Ảnh xem trước ${idx + 1}`} loading="lazy" />
                      ) : (
                        <span>Nhập URL http(s) hợp lệ để xem trước ảnh</span>
                      )}
                    </div>
                  </div>
                )
              })}
              <button
                type="button"
                className="button button--secondary"
                onClick={() => setForm({ ...form, images: [...form.images, { id: null, image_url: '', alt_text: '' }] })}
              >
                + Thêm ảnh
              </button>
            </section>
          </div>

          <div className="admin-verification__actions">
            <button type="submit" className="button button--primary" disabled={saving} aria-busy={saving}>
              {saving ? 'Đang lưu...' : 'Cập nhật và xác minh → tiếp'}
            </button>
            <button type="button" className="button button--danger" onClick={() => setShowDelete(true)} disabled={saving || deleting}>
              Xóa vĩnh viễn
            </button>
          </div>
        </form>
      )}

      {showDelete && place && (
        <div className="admin-verification__modal" role="dialog" aria-modal="true" aria-labelledby="delete-title">
          <div className="admin-verification__modal-card">
            <h2 id="delete-title">Xác nhận xóa vĩnh viễn</h2>
            <p>
              Bạn có chắc muốn xóa vĩnh viễn <strong>{place.name}</strong>? Thao tác này xóa toàn bộ dữ liệu liên quan và không thể hoàn tác.
            </p>
            <div className="admin-verification__modal-actions">
              <button type="button" className="button button--secondary" onClick={() => setShowDelete(false)} disabled={deleting}>
                Hủy
              </button>
              <button
                type="button"
                className="button button--danger"
                onClick={() => void handleDelete()}
                disabled={deleting}
                autoFocus
              >
                {deleting ? 'Đang xóa...' : 'Xóa vĩnh viễn'}
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
