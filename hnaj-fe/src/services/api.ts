export type ApiSuccessResponse<T> = {
  success: true
  message: string
  data: T
  meta?: Record<string, unknown>
}

export type ApiErrorResponse = {
  success: false
  message: string
  errors?: Record<string, string[]>
  code?: string
}

export type ApiResponse<T> = ApiSuccessResponse<T> | ApiErrorResponse

export type ApiTestData = {
  service: string
  status: string
}

export class ApiRequestError extends Error {
  readonly status: number
  readonly code?: string
  readonly errors?: Record<string, string[]>

  constructor(response: ApiErrorResponse, status: number) {
    super(response.message)
    this.name = 'ApiRequestError'
    this.status = status
    this.code = response.code
    this.errors = response.errors
  }
}

export async function getApiTest(): Promise<ApiSuccessResponse<ApiTestData>> {
  const response = await fetch('/api/test', {
    headers: {
      Accept: 'application/json',
    },
  })

  const payload = (await response.json()) as ApiResponse<ApiTestData>

  if (!response.ok || !payload.success) {
    if (!payload.success) {
      throw new ApiRequestError(payload, response.status)
    }

    throw new Error('The API returned an unexpected response.')
  }

  return payload
}
