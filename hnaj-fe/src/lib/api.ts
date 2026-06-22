import { RecommendationRequest, RecommendationResponse, TagsResponse } from '@/types';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8080';

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  private async request<T>(endpoint: string, options?: RequestInit): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const res = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
      },
      ...options,
    });

    const json = await res.json();

    if (!json.success) {
      const errMsg = json.error?.message || 'Unknown error';
      throw new ApiError(errMsg, json.error?.code, json.error?.details, res.status);
    }

    return json.data as T;
  }

  /** POST /api/v1/recommendations */
  async getRecommendations(req: RecommendationRequest): Promise<RecommendationResponse> {
    return this.request<RecommendationResponse>('/api/v1/recommendations', {
      method: 'POST',
      body: JSON.stringify(req),
    });
  }

  /** GET /api/v1/tags */
  async getTags(): Promise<TagsResponse> {
    return this.request<TagsResponse>('/api/v1/tags');
  }
}

export class ApiError extends Error {
  code: string;
  details?: { field: string; message: string }[];
  status: number;

  constructor(message: string, code: string, details?: { field: string; message: string }[], status = 500) {
    super(message);
    this.name = 'ApiError';
    this.code = code;
    this.details = details;
    this.status = status;
  }
}

export const apiClient = new ApiClient(API_BASE);
