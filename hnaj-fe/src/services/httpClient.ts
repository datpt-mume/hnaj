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

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown
  token?: string | null
}

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? '/api'

export async function apiRequest<T>(
  path: string,
  options: RequestOptions = {},
): Promise<ApiSuccessResponse<T>> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body !== undefined) {
    headers.set('Content-Type', 'application/json')
  }

  if (options.token) {
    headers.set('Authorization', `Bearer ${options.token}`)
  }

  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    headers,
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  })

  const payload = (await response.json()) as ApiResponse<T>

  if (!response.ok || !payload.success) {
    if (!payload.success) {
      throw new ApiRequestError(payload, response.status)
    }

    throw new Error('API trả về phản hồi không hợp lệ.')
  }

  return payload
}
