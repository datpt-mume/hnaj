import { useCallback, useEffect, useState } from 'react'
import { getDiscoveryMetadata, type DiscoveryMetadata } from '../services/metaService'
import { getApiErrorMessage } from '../services/httpClient'

export function useDiscoveryMetadata() {
  const [metadata, setMetadata] = useState<DiscoveryMetadata | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')

  const loadMetadata = useCallback(async () => {
    setIsLoading(true)
    setError('')

    try {
      setMetadata(await getDiscoveryMetadata())
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không thể tải bộ lọc. Hãy thử lại.'))
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    void loadMetadata()
  }, [loadMetadata])

  return { metadata, isLoading, error, retry: loadMetadata }
}
