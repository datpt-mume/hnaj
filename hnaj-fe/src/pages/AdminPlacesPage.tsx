import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { AdminPlaceCreateForm } from '../components/AdminPlaceCreateForm'
import { AdminPlacesList } from '../components/AdminPlacesList'
import { PlaceManagersPanel } from '../components/PlaceManagersPanel'
import { useAuth } from '../hooks/useAuth'
import { useDiscoveryMetadata } from '../hooks/useDiscoveryMetadata'
import {
  deleteAdminPlace,
  getAdminPlaces,
  type AdminPlace,
} from '../services/adminPlaceService'
import { getApiErrorMessage } from '../services/httpClient'

const PLACES_PER_PAGE = 10

export function AdminPlacesPage() {
  const { signOutAdmin } = useAuth()
  const { metadata } = useDiscoveryMetadata()
  const [places, setPlaces] = useState<AdminPlace[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [query, setQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [selectedPlace, setSelectedPlace] = useState<AdminPlace | null>(null)
  const [showCreate, setShowCreate] = useState(false)

  const loadPlaces = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const result = await getAdminPlaces({
        page,
        per_page: PLACES_PER_PAGE,
        q: query || undefined,
        status: statusFilter || undefined,
      })
      setPlaces(result.places)
      setTotal(result.meta.total)
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không tải được danh sách địa điểm.'))
    } finally {
      setLoading(false)
    }
  }, [page, query, statusFilter])

  useEffect(() => {
    void loadPlaces()
  }, [loadPlaces])

  async function handleDelete(place: AdminPlace) {
    if (!window.confirm(`Xóa địa điểm "${place.name}"? Place sẽ bị ẩn và có thể khôi phục sau.`)) return

    setDeletingId(place.id)
    setError(null)
    try {
      await deleteAdminPlace(place.id)
      if (selectedPlace?.id === place.id) setSelectedPlace(null)
      await loadPlaces()
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không xóa được địa điểm.'))
    } finally {
      setDeletingId(null)
    }
  }

  function handleQueryChange(value: string) {
    setQuery(value)
    setPage(1)
  }

  function handleStatusChange(value: string) {
    setStatusFilter(value)
    setPage(1)
  }

  const lastPage = useMemo(
    () => Math.max(1, Math.ceil(total / PLACES_PER_PAGE)),
    [total],
  )

  return (
    <main className="admin-shell">
      <nav className="home-nav" aria-label="Điều hướng quản trị">
        <Link className="wordmark" to="/admin" aria-label="HNAJ - Quản trị">
          <img src="/logo.png" alt="Hôm nay ăn gì?" />
        </Link>
        <div className="home-nav__links">
          <Link className="text-button" to="/admin">Dashboard</Link>
          <Link className="text-button" to="/admin/places/verification">Kiểm duyệt</Link>
          <button className="text-button" type="button" onClick={() => void signOutAdmin()}>
            Đăng xuất admin
          </button>
        </div>
      </nav>

      <section className="admin-card" aria-labelledby="places-title">
        <div className="admin-page-header">
          <div>
            <p className="home-hero__kicker">Quản lý địa điểm</p>
            <h1 id="places-title">Danh sách Places</h1>
            <p>{total} địa điểm</p>
          </div>
          <button className="button button--primary" type="button" onClick={() => setShowCreate((current) => !current)}>
            {showCreate ? 'Đóng form' : 'Thêm Place mới'}
          </button>
        </div>

        {showCreate ? (
          <AdminPlaceCreateForm
            districts={metadata?.districts ?? []}
            categories={metadata?.categories ?? []}
            onCreated={loadPlaces}
            onClose={() => setShowCreate(false)}
          />
        ) : null}

        <AdminPlacesList
          places={places}
          loading={loading}
          error={error}
          total={total}
          page={page}
          lastPage={lastPage}
          query={query}
          statusFilter={statusFilter}
          deletingId={deletingId}
          onQueryChange={handleQueryChange}
          onStatusChange={handleStatusChange}
          onOpenManagers={setSelectedPlace}
          onDelete={(place) => void handleDelete(place)}
          onPageChange={setPage}
        />
      </section>

      {selectedPlace ? (
        <PlaceManagersPanel place={selectedPlace} onClose={() => setSelectedPlace(null)} />
      ) : null}
    </main>
  )
}
