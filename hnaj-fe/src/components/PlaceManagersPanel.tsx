import { useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import {
  createPlaceManager,
  getPlaceManagers,
  resendPlaceManagerSetup,
  revokePlaceManager,
  type CreatePlaceManagerPayload,
  type PlaceManager,
} from '../services/adminPlaceService'
import { getApiErrorMessage } from '../services/httpClient'
import type { AdminPlace } from '../services/adminPlaceService'

type PlaceManagersPanelProps = {
  place: AdminPlace
  onClose: () => void
}

const EMPTY_MANAGER_FORM: CreatePlaceManagerPayload = {
  username: '',
  email: '',
  password: '',
  full_name: '',
}

export function PlaceManagersPanel({ place, onClose }: PlaceManagersPanelProps) {
  const headingRef = useRef<HTMLHeadingElement>(null)
  const [managers, setManagers] = useState<PlaceManager[]>([])
  const [managerForm, setManagerForm] = useState<CreatePlaceManagerPayload>(EMPTY_MANAGER_FORM)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)

  useEffect(() => {
    headingRef.current?.focus()
    headingRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, [])

  useEffect(() => {
    let active = true
    setLoading(true)
    setError(null)

    getPlaceManagers(place.id)
      .then((result) => {
        if (active) setManagers(result)
      })
      .catch((requestError: unknown) => {
        if (active) setError(getApiErrorMessage(requestError, 'Không tải được danh sách Sub-admin.'))
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [place.id])

  function updateForm(field: keyof CreatePlaceManagerPayload, value: string) {
    setManagerForm((currentForm) => ({ ...currentForm, [field]: value }))
  }

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setSuccess(null)
    try {
      await createPlaceManager(place.id, managerForm)
      setManagerForm(EMPTY_MANAGER_FORM)
      setSuccess('Đã tạo Sub-admin và gửi email kích hoạt.')
      setManagers(await getPlaceManagers(place.id))
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không tạo được Sub-admin.'))
    } finally {
      setSaving(false)
    }
  }

  async function handleResend(manager: PlaceManager) {
    setError(null)
    setSuccess(null)
    try {
      await resendPlaceManagerSetup(place.id, manager.user.id)
      setSuccess('Đã gửi lại email kích hoạt.')
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không gửi lại được email.'))
    }
  }

  async function handleRevoke(manager: PlaceManager) {
    if (!window.confirm(`Thu hồi quyền quản lý của "${manager.user.full_name || manager.user.username}"?`)) return
    setError(null)
    setSuccess(null)
    try {
      await revokePlaceManager(place.id, manager.user.id)
      setSuccess('Đã thu hồi quyền quản lý.')
      setManagers(await getPlaceManagers(place.id))
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không thu hồi được quyền.'))
    }
  }

  return (
    <section className="admin-card" aria-labelledby="managers-title">
      <div className="admin-page-header">
        <div>
          <p className="home-hero__kicker">Sub-admin</p>
          <h2 id="managers-title" ref={headingRef} tabIndex={-1}>{place.name}</h2>
        </div>
        <button className="text-button" type="button" onClick={onClose}>Đóng</button>
      </div>

      <form className="admin-form admin-form--inline" onSubmit={handleCreate} noValidate>
        <label>
          Username *
          <input autoComplete="username" value={managerForm.username} onChange={(event) => updateForm('username', event.target.value)} required />
        </label>
        <label>
          Email nhận thông báo *
          <input type="email" autoComplete="email" value={managerForm.email} onChange={(event) => updateForm('email', event.target.value)} required />
        </label>
        <label>
          Mật khẩu tạm *
          <input type="password" autoComplete="new-password" minLength={8} value={managerForm.password} onChange={(event) => updateForm('password', event.target.value)} required />
        </label>
        <label>
          Tên đầy đủ
          <input autoComplete="name" value={managerForm.full_name} onChange={(event) => updateForm('full_name', event.target.value)} />
        </label>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Đang tạo…' : 'Tạo Sub-admin'}
        </button>
      </form>

      {error ? <p className="admin-feedback admin-feedback--error" role="alert">{error}</p> : null}
      {success ? <p className="admin-feedback admin-feedback--success" role="status">{success}</p> : null}

      {loading ? (
        <p className="admin-feedback" role="status">Đang tải…</p>
      ) : error && managers.length === 0 ? null : managers.length === 0 ? (
        <p className="admin-empty" role="status">Place này chưa có Sub-admin.</p>
      ) : (
        <ul className="admin-manager-list">
          {managers.map((manager) => (
            <li key={manager.id} className="admin-manager-row">
              <div>
                <strong>{manager.user.full_name || manager.user.username}</strong>
                <span>@{manager.user.username} · {manager.user.email}</span>
                <span>{manager.revoked_at ? 'Đã thu hồi' : manager.user.email_verified ? 'Đã kích hoạt' : 'Chưa kích hoạt'}</span>
              </div>
              {!manager.revoked_at ? (
                <div className="admin-row-actions">
                  {!manager.user.email_verified ? (
                    <button className="text-button" type="button" onClick={() => void handleResend(manager)}>Gửi lại email</button>
                  ) : null}
                  <button className="text-button text-button--danger" type="button" onClick={() => void handleRevoke(manager)}>Thu hồi</button>
                </div>
              ) : null}
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
