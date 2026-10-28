import { checkBackendHealth } from "@/lib/backend-health";

export const dynamic = "force-dynamic";

export default async function Home() {
  const health = await checkBackendHealth();

  return (
    <div className="flex flex-col flex-1 items-center justify-center bg-zinc-50 p-6 font-sans text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
      <main className="w-full max-w-2xl rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <header className="mb-8 flex flex-col gap-2">
          <span className="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            Bước 1 — Nền tảng kỹ thuật
          </span>
          <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
            Khám phá &amp; Chia sẻ Địa điểm Hà Nội
          </h1>
          <p className="text-sm text-zinc-600 dark:text-zinc-400">
            Giao diện kiểm tra kết nối giữa Next.js (Frontend) và Spring Boot (Backend API).
          </p>
        </header>

        <section
          aria-label="Trạng thái kết nối Backend"
          className="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-950"
        >
          <div className="flex items-center justify-between gap-4">
            <h2 className="text-base font-semibold">Trạng thái Backend API</h2>
            <span
              className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${
                health.connected
                  ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                  : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300"
              }`}
            >
              <span
                className={`h-2 w-2 rounded-full ${
                  health.connected ? "bg-emerald-600" : "bg-red-600"
                }`}
              />
              {health.connected ? "Đang hoạt động (UP)" : "Mất kết nối (DOWN)"}
            </span>
          </div>

          <p className="mt-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
            {health.message}
          </p>

          <dl className="mt-4 border-t border-zinc-200 pt-4 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            <div className="flex flex-wrap justify-between gap-2">
              <dt className="font-medium">Endpoint kiểm tra:</dt>
              <dd className="font-mono">{health.endpoint}</dd>
            </div>
          </dl>
        </section>
      </main>
    </div>
  );
}
