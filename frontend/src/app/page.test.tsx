import { afterEach, describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import Home from "./page";

describe("Home page", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("displays connected status in Vietnamese when backend is UP", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(JSON.stringify({ status: "UP" }), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      })
    );

    const ui = await Home();
    render(ui);

    expect(
      screen.getByRole("heading", {
        name: /Khám phá & Chia sẻ Địa điểm Hà Nội/i,
      })
    ).toBeDefined();
    expect(screen.getByText("Đang hoạt động (UP)")).toBeDefined();
    expect(
      screen.getByText(/Đã kết nối thành công tới máy chủ Spring Boot/i)
    ).toBeDefined();
  });

  it("displays clear guidance in Vietnamese when backend is unreachable", async () => {
    vi.spyOn(globalThis, "fetch").mockRejectedValue(
      new Error("connect ECONNREFUSED 127.0.0.1:8080")
    );

    const ui = await Home();
    render(ui);

    expect(screen.getByText("Mất kết nối (DOWN)")).toBeDefined();
    expect(
      screen.getByText(/Hãy đảm bảo máy chủ Spring Boot đang chạy ở cổng 8080/i)
    ).toBeDefined();
  });
});
