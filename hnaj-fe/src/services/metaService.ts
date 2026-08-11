import { apiRequest } from './httpClient'

export type FilterCategory = {
  id: number
  name: string
  slug: string
}

export type FilterDistrict = {
  id: number
  name: string
  code: string | null
}

export type FilterTag = {
  id: number
  name: string
  slug: string
}

export type DiscoveryMetadata = {
  categories: FilterCategory[]
  districts: FilterDistrict[]
  tags: FilterTag[]
}

/** Contract at docs/api-meta.md — GET /api/meta/discovery (public). */
export async function getDiscoveryMetadata(): Promise<DiscoveryMetadata> {
  const response = await apiRequest<DiscoveryMetadata>('/meta/discovery')

  return response.data
}

export const DEFAULT_RADIUS_KM = 5
