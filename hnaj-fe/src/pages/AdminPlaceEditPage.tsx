import { useCallback, useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useParams } from 'react-router-dom'
import { AdminPlaceForm } from '../components/AdminPlaceForm'
import { useAuth } from '../hooks/useAuth'
import { useDiscoveryMetadata } from '../hooks/useDiscoveryMetadata'
import { getAdminPlace, updateAdminPlace } from '../services/adminPlaceService'
import { getApiErrorMessage } from '../services/httpClient'
import {
  formStateFromPlace,
  toUpdatePlacePayload,
  type AdminPlaceFormState,
} from '../utils/adminPlaceForm'

export function AdminPlaceEditPage() {
  const { placeId } = useParams()
  const { signOutAdmin } = useAuth()
  const { metadata } = useDiscoveryMetadata()
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [form, setForm] = useState<AdminPlaceFormState | null>(null)

  const id = Number(placeId)

  const loadPlace = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const place = await getAdminPlace(id)
      setForm(formStateFromPlace(place))
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không tải được chi tiết địa điểm.'))
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => {
    if (!Number.isInteger(id) || id <= 0) {
      setError('Địa điểm không hợp lệ.')
      setLoading(false)
      return
    }
    void loadPlace()
  }, [id, loadPlace])

  function updateTextField(field: Exclude<keyof AdminPlaceFormState, 'opening_hours'>, value: string) {
    setForm((currentForm) => currentForm ? { ...currentForm, [field]: value } : currentForm)
  }

  function updateOpeningHours(opening_hours: AdminPlaceFormState['opening_hours']) {
    setForm((currentForm) => currentForm ? { ...currentForm, opening_hours } : currentForm)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!form) return

    setSaving(true)
    setError(null)
    setSuccess(null)
    try {
      await updateAdminPlace(id, toUpdatePlacePayload(form))
      setSuccess('Đã lưu thay đổi.')
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không lưu được địa điểm.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <main className="admin-shell">
      <nav className="home-nav" aria-label="Điều hướng quản trị">
        <Link className="wordmark" to="/admin" aria-label="HNAJ - Quản trị">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <div className="home-nav__links">
          <Link className="text-button" to="/admin">Dashboard</Link>
          <Link className="text-button" to="/admin/places">Places</Link>
          <button className="text-button" type="button" onClick={() => void signOutAdmin()}>
            Đăng xuất admin
          </button>
        </div>
      </nav>

      <section className="admin-card" aria-labelledby="edit-title">
        <p className="home-hero__kicker">Chỉnh sửa địa điểm</p>
        <h1 id="edit-title">Place #{id}</h1>
        {error ? <p className="admin-feedback admin-feedback--error" role="alert">{error}</p> : null}
        {success ? <p className="admin-feedback admin-feedback--success" role="status">{success}</p> : null}

        {loading ? (
          <p className="admin-feedback" role="status">Đang tải…</p>
        ) : form ? (
          <form className="admin-form" onSubmit={handleSubmit} noValidate>
            <AdminPlaceForm
              form={form}
              districts={metadata?.districts ?? []}
              categories={metadata?.categories ?? []}
              onTextChange={updateTextField}
              onOpeningHoursChange={updateOpeningHours}
            />
            <button className="button button--primary" type="submit" disabled={saving}>
              {saving ? 'Đang lưu…' : 'Lưu thay đổi'}
            </button>
          </form>
        ) : null}
      </section>
    </main>
  )
}
