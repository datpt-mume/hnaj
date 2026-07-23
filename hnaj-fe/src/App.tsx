import { useCallback, useEffect, useState } from 'react'
import { ApiRequestError, getApiTest } from './services/api'
import type { ApiTestData } from './services/api'
import './App.css'

type RequestState = 'idle' | 'loading' | 'success' | 'error'

function App() {
  const [requestState, setRequestState] = useState<RequestState>('idle')
  const [data, setData] = useState<ApiTestData | null>(null)
  const [message, setMessage] = useState('')
  const [errorCode, setErrorCode] = useState<string | null>(null)

  const checkApiConnection = useCallback(async () => {
    setRequestState('loading')
    setData(null)
    setMessage('')
    setErrorCode(null)

    try {
      const response = await getApiTest()
      setData(response.data)
      setMessage(response.message)
      setRequestState('success')
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Unable to reach the API.')
      setErrorCode(error instanceof ApiRequestError ? error.code ?? null : null)
      setRequestState('error')
    }
  }, [])

  useEffect(() => {
    void checkApiConnection()
  }, [checkApiConnection])

  const isLoading = requestState === 'loading'

  return (
    <main className="api-shell">
      <section className="api-card" aria-labelledby="page-title">
        <p className="eyebrow">HNAJ · API CONNECTIVITY</p>
        <h1 id="page-title">Frontend ↔ Backend</h1>
        <p className="intro">
          A shared response envelope keeps every API request predictable for both
          applications.
        </p>

        <div className={`status-panel status-${requestState}`} aria-live="polite">
          <div className="status-heading">
            <span className="status-dot" aria-hidden="true" />
            <span>{isLoading ? 'Checking connection' : requestState === 'success' ? 'Connected' : requestState === 'error' ? 'Connection failed' : 'Ready to check'}</span>
          </div>
          <p>{isLoading ? 'Waiting for a response from hnaj-be…' : message || 'The API test has not run yet.'}</p>
          {errorCode && <code>{errorCode}</code>}
        </div>

        {data && (
          <dl className="response-grid">
            <div>
              <dt>Service</dt>
              <dd>{data.service}</dd>
            </div>
            <div>
              <dt>State</dt>
              <dd>{data.status}</dd>
            </div>
          </dl>
        )}

        <button type="button" onClick={() => void checkApiConnection()} disabled={isLoading}>
          {isLoading ? 'Checking…' : 'Run API test'}
        </button>
        <p className="endpoint-label">GET /api/test</p>
      </section>
    </main>
  )
}

export default App
