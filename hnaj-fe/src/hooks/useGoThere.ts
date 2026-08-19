import { useCallback } from 'react'
import type { DiscoveryPlace } from '../services/discoveryService'
import { recordVisit, type VisitSource } from '../services/visitService'

/**
 * Mở Google Maps ngay và ghi nhận lượt "Đi tới đó" fire-and-forget.
 * API visit không bao giờ chặn việc mở Maps.
 */
export function useGoThere() {
  return useCallback((place: DiscoveryPlace, source: VisitSource) => {
    window.open(place.google_maps_url, '_blank', 'noopener,noreferrer')
    void recordVisit(place.id, source).catch(() => {
      // Không hiển thị lỗi hay chặn điều hướng; visit là tăng cường cho hot/personalization.
    })
  }, [])
}