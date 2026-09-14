# 00 — Inventory Endpoints (Laravel → Spring Boot)

> Nguồn sự thật: [`hnaj-be/routes/api.php`](../../../hnaj-be/routes/api.php:1)  
> Mọi endpoint đều có prefix `/api` (configured in `bootstrap/app.php`).  
> Throttle được ghi bên cạnh; Spring Boot sẽ dùng Bucket4j hoặc tương đương.

## Trạng thái ký hiệu

- `[ ]` chưa triển khai trong Spring Boot  
- `[x]` đã triển khai và test đạt  
- `[-]` đang triển khai

---

## Public / No auth

| # | Method | Path | Throttle | Laravel Controller | Domain | Status |
|---|--------|------|----------|--------------------|--------|--------|
| 1 | GET | `/api/test` | — | `TestController` | infra | `[x]` |
| 2 | GET | `/api/meta/discovery` | 60/min | `DiscoveryMetadataController` | taxonomy | `[ ]` |
| 3 | POST | `/api/discovery/random` | 30/min | `PlaceDiscoveryController` | discovery | `[ ]` |
| 4 | GET | `/api/places/search` | 60/min | `SearchPlaceController` | places | `[ ]` |
| 5 | GET | `/api/places/{place}` | 60/min | `PlaceShowController` | places | `[ ]` |

> **Note #5:** `{place}` phải là số nguyên (`whereNumber`). Guest không có `is_bookmarked` trong response. User với bearer token hợp lệ thì có.

---

## Auth User — `POST /api/auth/*` (public với throttle)

| # | Method | Path | Throttle | Laravel Controller | Domain | Status |
|---|--------|------|----------|--------------------|--------|--------|
| 6 | POST | `/api/auth/register` | 10/min | `RegisterController` | auth | `[x]` |
| 7 | POST | `/api/auth/login` | 5/min | `LoginController` | auth | `[x]` |
| 8 | POST | `/api/auth/email/verify` | 10/min | `EmailVerificationController@verify` | auth | `[x]` |
| 9 | POST | `/api/auth/email/resend` | 5/min | `EmailVerificationController@resend` | auth | `[x]` |
| 10 | POST | `/api/auth/account/setup` | 10/min | `AccountSetupController` | auth | `[x]` |
| 11 | GET | `/api/auth/google/redirect` | 10/min | `GoogleAuthController@redirect` | auth/oauth | `[x]` |
| 12 | GET | `/api/auth/google/callback` | 10/min | `GoogleAuthController@callback` | auth/oauth | `[x]` |
| 13 | POST | `/api/auth/google/exchange` | 10/min | `GoogleAuthController@exchange` | auth/oauth | `[x]` |

> **Note #12:** callback trả HTTP redirect (không phải JSON); Spring cần trả `302` redirect về `FRONTEND_URL/auth/google/callback?code=...`.

---

## Auth User — Authenticated (`auth:sanctum` + `role:user,sub_admin`)

| # | Method | Path | Throttle | Laravel Controller | Domain | Status |
|---|--------|------|----------|--------------------|--------|--------|
| 14 | GET | `/api/auth/me` | — | `MeController` | auth | `[x]` |
| 15 | PATCH | `/api/auth/me` | — | `UpdateProfileController` | auth | `[x]` |
| 16 | POST | `/api/auth/logout` | — | `LogoutController` | auth | `[x]` |

---

## Manager Applications (user/sub_admin)

| # | Method | Path | Throttle | Laravel Controller | Domain | Status |
|---|--------|------|----------|--------------------|--------|--------|
| 17 | POST | `/api/manager-applications` | 10/min | `SubmitManagerApplicationController` | manager-app | `[ ]` |

---

## Bookmarks (user/sub_admin)

| # | Method | Path | Throttle | Laravel Controller | Domain | Status |
|---|--------|------|----------|--------------------|--------|--------|
| 18 | GET | `/api/bookmarks` | 60/min | `BookmarkIndexController` | bookmarks | `[ ]` |
| 19 | POST | `/api/bookmarks` | 30/min | `BookmarkStoreController` | bookmarks | `[ ]` |
| 20 | DELETE | `/api/bookmarks/{place}` | 30/min | `BookmarkDestroyController` | bookmarks | `[ ]` |

---

## Visits

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|----|--------|
| 21 | POST | `/api/visits` | 30/min | `VisitStoreController` | **public** (optional auth) | `[ ]` |
| 22 | GET | `/api/visits` | 60/min | `VisitIndexController` | auth user/sub_admin | `[ ]` |

> **Note #21:** Public + optional bearer token. Nếu có token hợp lệ → ghi vào `visit_events`. Nếu không có → cần `X-Anonymous-Id` header (ghi vào `anonymous_visit_events`).

---

## Admin Auth

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|------|--------|
| 23 | POST | `/api/admin/auth/login` | 5/min | `AdminLoginController` | public | `[x]` |
| 24 | GET | `/api/admin/auth/me` | — | `AdminMeController` | `role:admin` | `[x]` |
| 25 | POST | `/api/admin/auth/logout` | — | `LogoutController` | `role:admin` | `[x]` |

---

## Admin Tags

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|------|--------|
| 26 | POST | `/api/admin/tags` | 30/min | `AdminTagStoreController` | `role:admin` | `[ ]` |

---

## Admin Manager Applications

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|------|--------|
| 27 | GET | `/api/admin/manager-applications` | 60/min | `AdminManagerApplicationIndexController` | `role:admin` | `[ ]` |
| 28 | POST | `/api/admin/manager-applications/{id}/approve` | 10/min | `AdminManagerApplicationReviewController@approve` | `role:admin` | `[ ]` |
| 29 | POST | `/api/admin/manager-applications/{id}/reject` | 10/min | `AdminManagerApplicationReviewController@reject` | `role:admin` | `[ ]` |

---

## Admin Places

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|------|--------|
| 30 | GET | `/api/admin/places` | 60/min | `AdminPlaceIndexController` | `role:admin` | `[ ]` |
| 31 | POST | `/api/admin/places` | 30/min | `AdminPlaceStoreController` | `role:admin` | `[ ]` |
| 32 | GET | `/api/admin/places/verification-queue` | 60/min | `AdminPlaceVerificationQueueController` | `role:admin` | `[ ]` |
| 33 | GET | `/api/admin/places/{place}` | 60/min | `AdminPlaceShowController` | `role:admin` | `[ ]` |
| 34 | PATCH | `/api/admin/places/{place}` | 30/min | `AdminPlaceUpdateController` | `role:admin` | `[ ]` |
| 35 | DELETE | `/api/admin/places/{place}` | 10/min | `AdminPlaceDestroyController` | `role:admin` | `[ ]` |

> **Note #35:** Soft-delete (không phải hard-delete); hard-delete là route riêng đã được đơn giản hóa (không cần `confirm_name` trong body).

---

## Admin Place Managers

| # | Method | Path | Throttle | Laravel Controller | Auth | Status |
|---|--------|------|----------|--------------------|------|--------|
| 36 | GET | `/api/admin/places/{place}/managers` | 60/min | `AdminPlaceManagerIndexController` | `role:admin` | `[ ]` |
| 37 | POST | `/api/admin/places/{place}/managers` | 30/min | `AdminPlaceManagerStoreController` | `role:admin` | `[ ]` |
| 38 | POST | `/api/admin/places/{place}/managers/{user}/resend` | 10/min | `AdminPlaceManagerResendController` | `role:admin` | `[ ]` |
| 39 | DELETE | `/api/admin/places/{place}/managers/{user}` | 10/min | `AdminPlaceManagerRevokeController` | `role:admin` | `[ ]` |

---

## Tổng số endpoints: 39

### Phân loại theo domain cho phase implementation

| Domain | Endpoints | IDs |
|--------|-----------|-----|
| infra/test | 1 | 1 |
| auth (user + admin) | 15 | 6–16, 23–25 |
| auth/oauth (Google) | 3 | 11–13 |
| taxonomy | 1 | 2 |
| discovery | 1 | 3 |
| places (public) | 2 | 4–5 |
| bookmarks | 3 | 18–20 |
| visits | 2 | 21–22 |
| manager-applications | 3 | 17, 27–29 |
| admin/places | 10 | 30–39 |
| admin/tags | 1 | 26 |

### Thứ tự triển khai đề xuất (dependency order)

1. `infra/test` (1) — nền tảng, health check
2. `auth user` (6–10, 14–16) — đăng ký, login, email verify, me, update, logout
3. `auth admin` (23–25) — admin login, me, logout
4. `auth/oauth Google` (11–13) — phức tạp nhất, có cache state
5. `taxonomy` (2) — public, read-only, không auth
6. `discovery` (3) — PlaceScorer logic phức tạp nhưng thuần Java
7. `places public` (4–5) — search + detail, optional auth
8. `bookmarks` (18–20) — CRUD đơn giản
9. `visits` (21–22) — idempotency + anonymous hash
10. `manager-applications user` (17) — submit application
11. `admin/places` (30–35) — CRUD admin
12. `admin/place-managers` (36–39) — assign, revoke, resend
13. `admin/manager-applications` (27–29) — review
14. `admin/tags` (26) — tạo tag
