import { afterEach, describe, expect, it, vi } from "vitest";
import { checkBackendHealth } from "./backend-health";

describe("checkBackendHealth", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("returns connected status when backend responds with 200 and { status: 'UP' }", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(JSON.stringify({ status: "UP" }), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      })
    );

    const result = await checkBackendHealth("http://localhost:8080");

    expect(fetchSpy).toHaveBeenCalledWith(
      "http://localhost:8080/api/health",
      expect.objectContaining({
        cache: "no-store",
      })
    );
    expect(result).toEqual({
      connected: true,
      status: "UP",
      endpoint: "http://localhost:8080/api/health",
      message: "Đã kết nối thành công tới máy chủ Spring Boot.",
    });
  });

  it("returns disconnected status when backend responds with non-200 HTTP status", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response("Internal Server Error", { status: 503 })
    );

    const result = await checkBackendHealth("http://localhost:8080");

    expect(result.connected).toBe(false);
    expect(result.status).toBe("DOWN");
    expect(result.message).toContain("HTTP 503");
  });

  it("returns disconnected status when backend returns malformed payload or unexpected status", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(JSON.stringify({ status: "UNKNOWN" }), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      })
    );

    const result = await checkBackendHealth("http://localhost:8080");

    expect(result.connected).toBe(false);
    expect(result.status).toBe("DOWN");
    expect(result.message).toContain("không đúng định dạng");
  });

  it("returns disconnected status with guidance when fetch throws network or timeout error", async () => {
    vi.spyOn(globalThis, "fetch").mockRejectedValue(
      new Error("connect ECONNREFUSED 127.0.0.1:8080")
    );

    const result = await checkBackendHealth("http://localhost:8080");

    expect(result.connected).toBe(false);
    expect(result.status).toBe("DOWN");
    expect(result.message).toContain("Không thể kết nối tới backend");
  });
});
