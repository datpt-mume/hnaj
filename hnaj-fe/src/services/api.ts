import { apiRequest } from './httpClient'

export { ApiRequestError } from './httpClient'
export type {
  ApiErrorResponse,
  ApiResponse,
  ApiSuccessResponse,
} from './httpClient'

export type ApiTestData = {
  service: string
  status: string
}

export async function getApiTest() {
  return apiRequest<ApiTestData>('/test')
}
