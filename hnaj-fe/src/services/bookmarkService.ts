import { apiRequest } from './httpClient'
import { userTokenStorage } from './tokenStorage'
import type { DiscoveryPlace } from './discoveryService'

/** Contract at docs/api-bookmarks.md — tất cả endpoint yêu cầu user token. */

export type BookmarkListMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type BookmarkListResult = {
  places: DiscoveryPlace[]
  meta: BookmarkListMeta
}

export type BookmarkRecord = {
  id: number
  created_at: string | null
  place_id: number
  is_bookmarked: boolean
  place?: DiscoveryPlace | null
}

export async function listBookmarks(params: {
  page?: number
  per_page?: number
  signal?: AbortSignal
} = {}): Promise<BookmarkListResult> {
  const search = new URLSearchParams()
  if (params.page) search.set('page', String(params.page))
  if (params.per_page) search.set('per_page', String(params.per_page))

  const qs = search.toString()
  const path = `/bookmarks${qs ? `?${qs}` : ''}`

  const response = await apiRequest<DiscoveryPlace[]>(path, {
    token: userTokenStorage.get(),
    signal: params.signal,
  })

  const meta = response.meta as unknown as BookmarkListMeta

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

export async function createBookmark(placeId: number): Promise<BookmarkRecord> {
  const response = await apiRequest<BookmarkRecord>('/bookmarks', {
    method: 'POST',
    token: userTokenStorage.get(),
    body: { place_id: placeId },
  })

  return response.data
}

export async function deleteBookmark(placeId: number): Promise<void> {
  await apiRequest<null>(`/bookmarks/${placeId}`, {
    method: 'DELETE',
    token: userTokenStorage.get(),
  })
}
