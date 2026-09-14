# 07 — Open Questions (cần người dùng chốt, không tự đoán)

> Theo AGENTS.md §3 và §14: không tự suy đoán tool/version/quyết định kiến trúc.

## ✅ Đã chốt (Phase 1, 2026-09-05)

| # | Câu hỏi | Quyết định |
|---|---------|------------|
| Q1 | Package + artifact | **`com.hnaj` / `hnaj-be-java`** |
| Q2 | Flyway: 1 baseline hay tái tạo tuần tự | **Baseline V1 gộp 38 migration** (DB mới, đã tạo `V1__baseline_schema.sql`) |
| Q3 | Cache store cho OAuth state/exchange code | **Caffeine in-memory** (`maximumSize=1000,expireAfterWrite=2m`) |
| Q4 | Tên database | **`hnaj_java`** (app) + **`hnaj_java_test`** (test profile) |
| Q5 | Compose: sửa chính hay override riêng | **`docker-compose.spring.yaml`** riêng, share network `hnaj_hnaj` |
| Q6 | JPA `ddl-auto` | **`none`** + Flyway quản lý schema hoàn toàn |
| Q7 | Tương thích Sanctum token | **Chỉ tương thích API contract**; Spring phát hành token riêng; bảng `personal_access_tokens` giữ structure cho parity DB |
| Q8 | BCrypt + SHA-256 token tương thích hash cũ | Có (luôn tương thích, dù Q7 chọn token riêng) |
| Q9 | `X-Anonymous-Id` header | Giữ nguyên format FE gửi |
| Q10 | PlaceImport service port? | **KHÔNG port** — giữ Laravel làm dev tool, hoặc viết script riêng sau |
| Q11 | Domain `planned` chưa có endpoint | **Chỉ port schema + JPA entity** (Phase 1 đã tạo table); business logic port khi Laravel có endpoint |
| Q12 | Mail views → Thymeleaf | Có (sẽ tạo ở domain auth, Phase 2) |
| Q13 | `password_reset_tokens` + `sessions` tables | Tạo cho parity schema (đã có trong V1) |
| Q14 | Parity test dual-runtime | Chưa khả thi ngay — Phase 3 sẽ thiết kế fixture-sharing |
| Q15 | Cutover FE | Sau khi matrix mục A đạt 100% done |
| Q16 | Rate limit library | **Bucket4j** `vipx/bucket4j-spring-boot-starter 0.9.0` |

## Còn treo

| # | Câu hỏi | Lý do chưa chốt | Deadline |
|---|---------|------------------|----------|
| Q17 | **JWT vs opaque token** | ✅ **Đã chốt 2026-09-05: Opaque token, bảng mới `access_tokens` (hash SHA-256), giống pattern Sanctum nhưng tách schema.** | — |
| Q18 | Realtime notification transport (SSE/WebSocket) | Domain chưa có endpoint, đợi tới khi implement | Trước domain notification |
| Q19 | Image storage (S3/local/Spaces) | Q12 + AGENTS §10 chưa cho phép | Trước review/comment image |

## Quyết định Q17 — chi tiết

- **Format:** opaque random 64 chars hex (bảo mật) lưu DB dưới dạng **SHA-256 hash**.
- **Table mới:** `access_tokens` (CHƯA từng tồn tại trong Laravel); KHÔNG dùng `personal_access_tokens` Sanctum (vì Q7 chốt Spring phát hành token riêng).
- **Cột:** `id`, `user_id` FK, `token_hash` UNIQUE, `name` (vd `spa`), `expires_at` NULL INDEX, `last_used_at` NULL, `created_at`, `updated_at`.
- **Lookup:** 1 query `WHERE token_hash = SHA256(plaintext) AND (expires_at IS NULL OR expires_at > NOW())`; load user từ `tokenable_id` (`user_id` ở đây, đơn giản hóa so với Sanctum morphs).
- **Role:** VẪN đọc từ `user_roles` mỗi request (đã chốt ở 04-auth-security §C3) — opaque token không chứa role.
- **Flyway:** thêm migration `V2__access_tokens.sql` ở Phase 2.

## Quy tắc cập nhật

- Khi 1 câu trả lời → chuyển từ `Q#` sang `Q# (đã chốt)` + ngày.
- Không xóa câu hỏi cũ; chỉ chuyển trạng thái để giữ lịch sử quyết định.
