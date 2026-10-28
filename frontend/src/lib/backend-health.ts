export interface BackendHealthResult {
  readonly connected: boolean;
  readonly status: "UP" | "DOWN";
  readonly endpoint: string;
  readonly message: string;
}

const DEFAULT_BACKEND_URL = "http://localhost:8080";
const HEALTH_TIMEOUT_MS = 3000;

function isValidHealthPayload(value: unknown): value is { readonly status: "UP" } {
  if (typeof value !== "object" || value === null) {
    return false;
  }
  const record = value as Record<string, unknown>;
  return record.status === "UP";
}

export async function checkBackendHealth(
  baseUrl: string = process.env.BACKEND_URL ?? DEFAULT_BACKEND_URL
): Promise<BackendHealthResult> {
  const normalizedBaseUrl = baseUrl.replace(/\/+$/, "");
  const endpoint = `${normalizedBaseUrl}/api/health`;

  try {
    const response = await fetch(endpoint, {
      method: "GET",
      cache: "no-store",
      signal: AbortSignal.timeout(HEALTH_TIMEOUT_MS),
    });

    if (!response.ok) {
      return {
        connected: false,
        status: "DOWN",
        endpoint,
        message: `Backend trả về lỗi HTTP ${response.status}. Hãy kiểm tra tiến trình Spring Boot tại ${normalizedBaseUrl}.`,
      };
    }

    const payload: unknown = await response.json();
    if (!isValidHealthPayload(payload)) {
      return {
        connected: false,
        status: "DOWN",
        endpoint,
        message: "Phản hồi từ backend không đúng định dạng mong đợi ({ status: 'UP' }).",
      };
    }

    return {
      connected: true,
      status: "UP",
      endpoint,
      message: "Đã kết nối thành công tới máy chủ Spring Boot.",
    };
  } catch (error: unknown) {
    const detail = error instanceof Error ? error.message : "Lỗi không xác định";
    return {
      connected: false,
      status: "DOWN",
      endpoint,
      message: `Không thể kết nối tới backend (${detail}). Hãy đảm bảo máy chủ Spring Boot đang chạy ở cổng 8080.`,
    };
  }
}
