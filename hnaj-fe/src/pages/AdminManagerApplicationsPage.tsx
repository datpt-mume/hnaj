import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import {
  approveManagerApplication,
  getManagerApplications,
  rejectManagerApplication,
} from '../services/adminManagerApplicationService'
import type { ManagerApplication } from '../services/managerApplicationTypes'
import { getApiErrorMessage } from '../services/httpClient'

export function AdminManagerApplicationsPage() {
  const { signOutAdmin } = useAuth()
  const [applications, setApplications] = useState<ManagerApplication[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [busyId, setBusyId] = useState<number | null>(null)
  const [rejectReason, setRejectReason] = useState<Record<number, string>>({})

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const result = await getManagerApplications({ per_page: 50 })
      setApplications(result.applications)
    } catch (err) {
      setError(getApiErrorMessage(err, 'Không tải được danh sách đơn.'))
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    void load()
  }, [load])

  async function handleApprove(application: ManagerApplication) {
    setBusyId(application.id)
    setError(null)
    try {
      await approveManagerApplication(application.id)
      await load()
    } catch (err) {
      setError(getApiErrorMessage(err, 'Không duyệt được đơn.'))
    } finally {
      setBusyId(null)
    }
  }

  async function handleReject(application: ManagerApplication) {
    const reason = (rejectReason[application.id] ?? '').trim()
    if (!reason) {
      setError('Vui lòng nhập lý do từ chối.')
      return
    }
    setBusyId(application.id)
    setError(null)
    try {
      await rejectManagerApplication(application.id, reason)
      await load()
    } catch (err) {
      setError(getApiErrorMessage(err, 'Không từ chối được đơn.'))
    } finally {
      setBusyId(null)
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

      <section className="admin-card" aria-labelledby="applications-title">
        <p className="home-hero__kicker">Quản trị</p>
        <h1 id="applications-title">Đơn xin làm Sub-admin</h1>

        {error ? <p className="admin-feedback admin-feedback--error" role="alert">{error}</p> : null}

        {loading ? (
          <p className="admin-feedback" role="status">Đang tải…</p>
        ) : error ? null : applications.length === 0 ? (
          <p className="admin-empty" role="status">Chưa có đơn xin quản lý.</p>
        ) : (
          <ul className="admin-manager-list">
            {applications.map((application) => (
              <li key={application.id} className="admin-manager-row">
                <div>
                  <strong>{application.representative_name}</strong>
                  <span>{application.email}</span>
                  <span>Place: {application.place?.name ?? '—'}</span>
                  <span>Trạng thái: {application.status}</span>
                  {application.review_reason ? <span>Lý do: {application.review_reason}</span> : null}
                </div>
                {application.status === 'pending' ? (
                  <div className="admin-manager-actions">
                    <button
                      className="button button--primary"
                      type="button"
                      disabled={busyId === application.id}
                      onClick={() => void handleApprove(application)}
                    >
                      Duyệt
                    </button>
                    <div className="admin-reject-row">
                      <input
                        aria-label={`Lý do từ chối đơn ${application.id}`}
                        placeholder="Lý do từ chối"
                        value={rejectReason[application.id] ?? ''}
                        onChange={(e) => setRejectReason((r) => ({ ...r, [application.id]: e.target.value }))}
                      />
                      <button
                        className="button button--secondary"
                        type="button"
                        disabled={busyId === application.id}
                        onClick={() => void handleReject(application)}
                      >
                        Từ chối
                      </button>
                    </div>
                  </div>
                ) : null}
              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  )
}
