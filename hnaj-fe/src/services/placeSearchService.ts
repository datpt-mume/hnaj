import { apiRequest } from './httpClient'
import type { DiscoveryPlace } from './discoveryService'

type SearchSignal = { signal?: AbortSignal }

/** Contract at docs/api-search.md — GET /api/places/search (public). */
export type SearchMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type SearchResult = {
  places: DiscoveryPlace[]
  meta: SearchMeta
}

const DEFAULT_PER_PAGE = 10

export async function searchPlaces(
  query: string,
  page = 1,
  perPage = DEFAULT_PER_PAGE,
  options: SearchSignal = {},
): Promise<SearchResult> {
  const params = new URLSearchParams({
    q: query,
    page: String(page),
    per_page: String(perPage),
  })

  const response = await apiRequest<DiscoveryPlace[]>(`/places/search?${params.toString()}`, {
    signal: options.signal,
  })

  const meta = response.meta as unknown as SearchMeta

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
