import { Link } from 'react-router-dom'
import type { AdminPlace } from '../services/adminPlaceService'

type AdminPlacesListProps = {
  places: AdminPlace[]
  loading: boolean
  error: string | null
  total: number
  page: number
  lastPage: number
  query: string
  statusFilter: string
  deletingId: number | null
  onQueryChange: (value: string) => void
  onStatusChange: (value: string) => void
  onOpenManagers: (place: AdminPlace) => void
  onDelete: (place: AdminPlace) => void
  onPageChange: (page: number) => void
}

export function AdminPlacesList({
  places,
  loading,
  error,
  total,
  page,
  lastPage,
  query,
  statusFilter,
  deletingId,
  onQueryChange,
  onStatusChange,
  onOpenManagers,
  onDelete,
  onPageChange,
}: AdminPlacesListProps) {
  return (
    <>
      <div className="admin-toolbar">
        <input
          aria-label="Tìm kiếm địa điểm"
          placeholder="Tìm theo tên hoặc địa chỉ…"
          value={query}
          onChange={(event) => onQueryChange(event.target.value)}
        />
        <select
          aria-label="Lọc trạng thái"
          value={statusFilter}
          onChange={(event) => onStatusChange(event.target.value)}
        >
          <option value="">Tất cả trạng thái</option>
          <option value="active">active</option>
          <option value="hidden">hidden</option>
        </select>
      </div>

      {error ? <p className="admin-feedback admin-feedback--error" role="alert">{error}</p> : null}

      {loading ? (
        <p className="admin-feedback" role="status">Đang tải…</p>
      ) : error ? null : places.length === 0 ? (
        <p className="admin-empty" role="status">Không có địa điểm nào.</p>
      ) : (
        <div className="admin-table-wrap">
          <table className="admin-table">
            <caption className="visually-hidden">Danh sách {total} địa điểm</caption>
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Quận</th>
                <th>Trạng thái</th>
                <th>Đã kiểm duyệt</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              {places.map((place) => (
                <tr key={place.id}>
                  <td data-label="ID">{place.id}</td>
                  <td data-label="Tên">{place.name}</td>
                  <td data-label="Quận">{place.district?.name ?? '—'}</td>
                  <td data-label="Trạng thái">{place.status}</td>
                  <td data-label="Đã kiểm duyệt">{place.is_verified ? 'Có' : 'Chưa'}</td>
                  <td data-label="Hành động">
                    <div className="admin-row-actions">
                      <button className="text-button" type="button" onClick={() => onOpenManagers(place)}>
                        Sub-admin
                      </button>
                      <Link className="text-button" to={`/admin/places/${place.id}/edit`}>Sửa</Link>
                      <button
                        className="text-button text-button--danger"
                        type="button"
                        disabled={deletingId === place.id}
                        onClick={() => onDelete(place)}
                      >
                        {deletingId === place.id ? 'Đang xóa…' : 'Xóa'}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {!loading && !error && lastPage > 1 ? (
        <nav className="admin-pagination" aria-label="Phân trang">
          <button className="text-button" type="button" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
            Trước
          </button>
          <span>Trang {page} / {lastPage}</span>
          <button className="text-button" type="button" disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>
            Sau
          </button>
        </nav>
      ) : null}
    </>
  )
}
