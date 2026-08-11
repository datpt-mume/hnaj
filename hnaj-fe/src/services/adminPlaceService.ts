import { adminTokenStorage } from './tokenStorage'
import { apiRequest } from './httpClient'
import type { FilterTag } from './metaService'

export type AdminPlaceImage = {
  id: number
  image_url: string
  alt_text: string | null
  is_visible: boolean
}

export type AdminPlaceOpeningHour = {
  day_of_week: number
  schedule_type: string
  opens_at: string | null
  closes_at: string | null
}

export type AdminPlace = {
  id: number
  name: string
  address_text: string
  google_place_id: string | null
  phone: string | null
  website_url: string | null
  google_maps_url: string
  district_id: number
  district: { id: number; name: string } | null
  category_id: number
  category: { id: number; name: string; slug: string } | null
  tags: { id: number; name: string; slug: string }[]
  latitude: string | number
  longitude: string | number
  min_price: number | null
  max_price: number | null
  rating: number | null
  description: string | null
  status: string
  is_verified: boolean
  thumbnail_image_id: number | null
  thumbnail: { id: number; image_url: string; alt_text: string | null } | null
  images: AdminPlaceImage[]
  opening_hours: AdminPlaceOpeningHour[]
}

export type VerificationQueueMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
  total_unverified: number
}

export type VerificationQueueResult = {
  places: AdminPlace[]
  meta: VerificationQueueMeta
}

export type UpdateAdminPlacePayload = {
  name: string
  address_text: string
  district_id: number
  category_id: number
  tag_ids: number[]
  phone: string | null
  website_url: string | null
  google_maps_url: string
  google_place_id: string | null
  latitude: number
  longitude: number
  min_price: number | null
  max_price: number | null
  description: string | null
  status: string
  opening_hours: AdminPlaceOpeningHour[]
  images: { id?: number | null; image_url: string; alt_text: string | null }[]
  thumbnail_image_id: number | null
  deleted_image_ids: number[]
}

export async function createAdminTag(name: string): Promise<FilterTag> {
  const response = await apiRequest<FilterTag>('/admin/tags', {
    method: 'POST',
    token: adminTokenStorage.get(),
    body: { name },
  })

  return response.data
}

export async function getVerificationQueue(params: {
  page?: number
  per_page?: number
  q?: string
  district_id?: number
  category_id?: number
  tag_id?: number
  signal?: AbortSignal
}): Promise<VerificationQueueResult> {
  const search = new URLSearchParams()
  if (params.page) search.set('page', String(params.page))
  if (params.per_page) search.set('per_page', String(params.per_page))
  if (params.q) search.set('q', params.q)
  if (params.district_id) search.set('district_id', String(params.district_id))
  if (params.category_id) search.set('category_id', String(params.category_id))
  if (params.tag_id) search.set('tag_id', String(params.tag_id))

  const qs = search.toString()
  const path = `/admin/places/verification-queue${qs ? `?${qs}` : ''}`

  const response = await apiRequest<AdminPlace[]>(path, {
    token: adminTokenStorage.get(),
    signal: params.signal,
  })

  const meta = response.meta as unknown as VerificationQueueMeta

  return {
    places: response.data,
    meta: {
      current_page: meta.current_page,
      last_page: meta.last_page,
      per_page: meta.per_page,
      total: meta.total,
      total_unverified: meta.total_unverified ?? meta.total,
    },
  }
}

export async function getAdminPlace(id: number, signal?: AbortSignal): Promise<AdminPlace> {
  const response = await apiRequest<AdminPlace>(`/admin/places/${id}`, {
    token: adminTokenStorage.get(),
    signal,
  })

  return response.data
}

export async function updateAdminPlace(
  id: number,
  payload: UpdateAdminPlacePayload,
): Promise<{ place: AdminPlace; next_unverified_id: number | null }> {
  const response = await apiRequest<AdminPlace>(`/admin/places/${id}`, {
    method: 'PATCH',
    token: adminTokenStorage.get(),
    body: payload,
  })

  const meta = response.meta as unknown as { next_unverified_id: number | null } | undefined

  return {
    place: response.data,
    next_unverified_id: meta?.next_unverified_id ?? null,
  }
}

export async function deleteAdminPlace(id: number): Promise<{ next_unverified_id: number | null }> {
  const response = await apiRequest<null>(`/admin/places/${id}`, {
    method: 'DELETE',
    token: adminTokenStorage.get(),
  })

  const meta = response.meta as unknown as { next_unverified_id: number | null } | undefined

  return { next_unverified_id: meta?.next_unverified_id ?? null }
}
