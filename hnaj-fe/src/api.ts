import type { AuthUser, ImportBatchDetail, ImportHistory, Place, RecommendationData, RecommendationRequest, UserLocation } from './types'

const API_BASE = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/(?:api)?\/?$/, '')

export async function publicRequest<T>(path: string): Promise<T> {
  const response = await fetch(`${API_BASE}/api/v1${path}`, { headers: { Accept: 'application/json' } })
  const payload = await response.json()
  if (!response.ok || !payload.success) throw new Error(payload.error?.message_key || 'discovery.request_failed')
  return payload.data
}

export async function recommend(request: RecommendationRequest): Promise<RecommendationData> {
  const query = new URLSearchParams()
  query.set('location[lat]', String(request.location.lat))
  query.set('location[lng]', String(request.location.lng))
  query.set('radius_km', String(request.radius_km))
  query.set('category_slug', request.category_slug)
  query.set('price_min', String(request.price_min))
  if (request.price_max !== null) query.set('price_max', String(request.price_max))
  if (request.district_id) query.set('district_id', request.district_id)
  request.tags?.forEach((tag) => query.append('tags[]', tag))
  if (request.limit) query.set('limit', String(request.limit))
  return publicRequest<RecommendationData>(`/recommendations?${query.toString()}`)
}

export async function getPlaceDetail(slug: string, location: UserLocation | null): Promise<Place> {
  const query = new URLSearchParams()
  if (location) {
    query.set('location[lat]', String(location.lat))
    query.set('location[lng]', String(location.lng))
  }
  const suffix = query.toString() ? `?${query.toString()}` : ''
  return publicRequest<Place>(`/places/${encodeURIComponent(slug)}${suffix}`)
}

export async function authRequest(path: string, options: RequestInit = {}) {
  const xsrf = decodeURIComponent(document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (!(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (xsrf) headers.set('X-XSRF-TOKEN', xsrf)
  const response = await fetch(`${API_BASE}/api/v1${path}`, { ...options, credentials: 'include', headers })
  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new Error(payload?.error?.message_key || payload?.error?.code || payload?.message || payload?.errors?.email?.[0] || 'auth.request_failed')
  }
  return response.status === 204 ? null : response.json()
}

export async function uploadImport(file: File): Promise<ImportBatchDetail> {
  await authRequest('/auth/csrf-cookie')
  const form = new FormData()
  form.append('file', file)
  const payload = await authRequest('/admin/imports/places/preview', { method: 'POST', body: form })
  return payload.data
}

export async function updateImport(batchId: string, confirm = false): Promise<ImportBatchDetail> {
  const payload = await authRequest(`/admin/imports/places/${batchId}${confirm ? '/confirm' : ''}`, { method: confirm ? 'POST' : 'GET' })
  return payload.data
}

export async function transitionImport(batchId: string, action: 'pause' | 'resume'): Promise<ImportBatchDetail> {
  const payload = await authRequest(`/admin/imports/places/${batchId}/${action}`, { method: 'POST' })
  return payload.data
}

export async function listImports(page = 1): Promise<ImportHistory> {
  const payload = await authRequest(`/admin/imports/places?page=${page}&per_page=10`)
  return payload.data
}

export async function getCurrentUser(): Promise<AuthUser | null> {
  try {
    const payload = await authRequest('/auth/me')
    return payload.data
  } catch {
    return null
  }
}