import { apiRequest } from './httpClient'
import { userTokenStorage } from './tokenStorage'
import { getAnonymousId } from './anonymousId'
import type { DiscoveryPlace } from './discoveryService'

/** Contract at docs/api-visits.md. */

export type VisitSource = 'discovery' | 'detail' | 'search' | 'bookmarks' | 'history'

export type VisitRecord = {
  id?: number | null
  place_id: number
  visit_date: string
  visited_at: string | null
  source: VisitSource | null
  created: boolean
  anonymous: boolean
}

export type VisitHistoryMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type VisitHistoryPlace = DiscoveryPlace & {
  last_visited_at: string | null
  last_source: VisitSource | null
}

export type VisitHistoryResult = {
  places: VisitHistoryPlace[]
  meta: VisitHistoryMeta
}

/** Ghi nhận một lượt "Đi tới đó". Không await trước khi mở Maps. */
export async function recordVisit(placeId: number, source: VisitSource): Promise<VisitRecord> {
  const response = await apiRequest<VisitRecord>('/visits', {
    method: 'POST',
    token: userTokenStorage.get(),
    headers: { 'X-Anonymous-Id': getAnonymousId() },
    body: { place_id: placeId, source },
  })

  return response.data
}

export async function listVisitHistory(params: {
  page?: number
  per_page?: number
  signal?: AbortSignal
} = {}): Promise<VisitHistoryResult> {
  const search = new URLSearchParams()
  if (params.page) search.set('page', String(params.page))
  if (params.per_page) search.set('per_page', String(params.per_page))

  const qs = search.toString()
  const path = `/visits${qs ? `?${qs}` : ''}`

  const response = await apiRequest<VisitHistoryPlace[]>(path, {
    token: userTokenStorage.get(),
    signal: params.signal,
  })

  const meta = response.meta as unknown as VisitHistoryMeta

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