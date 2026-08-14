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

export type AdminPlaceListMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type PlaceManager = {
  id: number
  place_id: number
  user: {
    id: number
    username: string
    full_name: string
    email: string
    email_verified: boolean
    status: string
  }
  assigned_at: string | null
  revoked_at: string | null
}

export type CreatePlaceManagerPayload = {
  username: string
  email: string
  password: string
  full_name?: string
}

export type AdminPlaceListResult = {
  places: AdminPlace[]
  meta: AdminPlaceListMeta
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
): Promise<AdminPlace> {
  const response = await apiRequest<AdminPlace>(`/admin/places/${id}`, {
    method: 'PATCH',
    token: adminTokenStorage.get(),
    body: payload,
  })

  return response.data
}

export async function deleteAdminPlace(id: number): Promise<void> {
  await apiRequest<null>(`/admin/places/${id}`, {
    method: 'DELETE',
    token: adminTokenStorage.get(),
  })
}

export async function getAdminPlaces(params: {
  page?: number
  per_page?: number
  q?: string
  district_id?: number
  category_id?: number
  tag_id?: number
  status?: string
  is_verified?: boolean
  with_trashed?: boolean
  signal?: AbortSignal
}): Promise<AdminPlaceListResult> {
  const search = new URLSearchParams()
  if (params.page) search.set('page', String(params.page))
  if (params.per_page) search.set('per_page', String(params.per_page))
  if (params.q) search.set('q', params.q)
  if (params.district_id) search.set('district_id', String(params.district_id))
  if (params.category_id) search.set('category_id', String(params.category_id))
  if (params.tag_id) search.set('tag_id', String(params.tag_id))
  if (params.status) search.set('status', params.status)
  if (params.is_verified !== undefined) search.set('is_verified', String(params.is_verified))
  if (params.with_trashed) search.set('with_trashed', '1')

  const qs = search.toString()
  const path = `/admin/places${qs ? `?${qs}` : ''}`

  const response = await apiRequest<AdminPlace[]>(path, {
    token: adminTokenStorage.get(),
    signal: params.signal,
  })

  const meta = response.meta as unknown as AdminPlaceListMeta

  return {
    places: response.data,
    meta: {
      current_page: meta.current_page,
      last_page: meta.last_page,
      per_page: meta.per_page,
      total: meta.total,
    },
  }
}

export type CreateAdminPlacePayload = {
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
  images: { image_url: string; alt_text: string | null }[]
  thumbnail_image_id: number | null
}

export async function createAdminPlace(payload: CreateAdminPlacePayload): Promise<AdminPlace> {
  const response = await apiRequest<AdminPlace>('/admin/places', {
    method: 'POST',
    token: adminTokenStorage.get(),
    body: payload,
  })

  return response.data
}

export async function getPlaceManagers(placeId: number, signal?: AbortSignal): Promise<PlaceManager[]> {
  const response = await apiRequest<PlaceManager[]>(`/admin/places/${placeId}/managers`, {
    token: adminTokenStorage.get(),
    signal,
  })

  return response.data
}

export async function createPlaceManager(placeId: number, payload: CreatePlaceManagerPayload) {
  return apiRequest<{ id: number; username: string }>(`/admin/places/${placeId}/managers`, {
    method: 'POST',
    token: adminTokenStorage.get(),
    body: payload,
  })
}

export async function resendPlaceManagerSetup(placeId: number, userId: number) {
  return apiRequest<null>(`/admin/places/${placeId}/managers/${userId}/resend`, {
    method: 'POST',
    token: adminTokenStorage.get(),
  })
}

export async function revokePlaceManager(placeId: number, userId: number) {
  return apiRequest<null>(`/admin/places/${placeId}/managers/${userId}`, {
    method: 'DELETE',
    token: adminTokenStorage.get(),
  })
}
