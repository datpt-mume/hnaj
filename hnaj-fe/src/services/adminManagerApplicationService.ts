import { apiRequest } from './httpClient'
import { adminTokenStorage } from './tokenStorage'
import type { ManagerApplication, ManagerApplicationStatus } from './managerApplicationTypes'

export async function getManagerApplications(params: {
  page?: number
  per_page?: number
  status?: ManagerApplicationStatus
  signal?: AbortSignal
}): Promise<{
  applications: ManagerApplication[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}> {
  const search = new URLSearchParams()
  if (params.page) search.set('page', String(params.page))
  if (params.per_page) search.set('per_page', String(params.per_page))
  if (params.status) search.set('status', params.status)

  const queryString = search.toString()
  const path = `/admin/manager-applications${queryString ? `?${queryString}` : ''}`
  const response = await apiRequest<ManagerApplication[]>(path, {
    token: adminTokenStorage.get(),
    signal: params.signal,
  })
  const meta = response.meta as unknown as {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }

  return { applications: response.data, meta }
}

export async function approveManagerApplication(id: number) {
  return apiRequest<{ id: number; status: string }>(`/admin/manager-applications/${id}/approve`, {
    method: 'POST',
    token: adminTokenStorage.get(),
  })
}

export async function rejectManagerApplication(id: number, reason: string) {
  return apiRequest<{ id: number; status: string }>(`/admin/manager-applications/${id}/reject`, {
    method: 'POST',
    token: adminTokenStorage.get(),
    body: { reason },
  })
}
