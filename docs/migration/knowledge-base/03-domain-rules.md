# 03 — Business / Domain Rules

> Nguồn: các Action trong [`hnaj-be/app/Actions/`](../../../hnaj-be/app/Actions/),
> [`Repositories/`](../../../hnaj-be/app/Repositories/), [`Services/`](../../../hnaj-be/app/Services/)
> và các quyết định nghiệp vụ đã chốt trong [`plans/project-progress.md`](../../../plans/project-progress.md:47).

---

## 1. Auth

### Register (`RegisterUser`)
- Transaction: create user (`status=active`) + gán role `user` + phát hành email verification token.
- User sau register **chưa login được** cho tới khi verify email.
- Username: lowercase, `[a-z0-9._]`, 3–50 ký tự, không đầu/cuối bằng `.`/`_`.
- Password: min 8, có cả chữ và số, `confirmed` (field `password_confirmation`).

### Login (`AuthenticateCredentials` → `LoginUser`)
- Tìm theo **username** (không phải email).
- **Anti user-enumeration:** khi không tìm thấy user vẫn chạy `Hash::check` với DUMMY_HASH để thời gian phản hồi không lộ sự tồn tại username. Spring: giữ nguyên pattern này.
- Thứ tự check: credentials → `isActive()` → `hasVerifiedEmailAddress()`. Sai credentials = 401 `INVALID_CREDENTIALS`; account không active = 403 `ACCOUNT_NOT_ACTIVE`; chưa verify = 403 `EMAIL_NOT_VERIFIED`.
- User thường VÀ sub_admin đều login qua `POST /api/auth/login` (cùng endpoint).
- Token phát hành qua Sanctum (`IssueAccessToken`), **token không mang role** (role luôn đọc từ DB).

### Email verification (`VerifyEmail`, `ResendEmailVerification`)
- Token lưu dạng SHA-256 hash; khi verify dùng `lockForUpdate()` trong transaction (chống race double-use).
- Token có `expires_at` + `used_at`; không hợp lệ/hết hạn/đã dùng → 422 `INVALID_VERIFICATION_TOKEN`; đã verify rồi → 409 `EMAIL_ALREADY_VERIFIED`.

### Account setup (sub-admin kích hoạt, `CompleteAccountSetup`)
- Token one-time (bảng `account_setup_tokens`), 24h; đặt password + verify email + consume token; `lockForUpdate` trong transaction. Không đổi status, không phát/revoke bearer token. Password setup chỉ min 8 + confirmed, không bắt buộc chữ và số như register.

### Profile (`UpdateProfile`)
- `PATCH /api/auth/me`: **chỉ `full_name` writable**; `username`, `email`, `avatar_url` read-only.

---

## 2. Google OAuth (`RedirectToGoogle`, `HandleGoogleCallback`, `ExchangeGoogleCode`)

**Design quan trọng:** bearer token KHÔNG bao giờ xuất hiện trong redirect URL.

Luồng:
1. `GET /auth/google/redirect` → tạo `state` + `flow_cookie`; lưu `Cache::put(stateKey, {flow_hash: hash(flow_cookie)}, 300s)`; trả JSON `data.authorization_url` và Set-Cookie (5 phút), frontend tự chuyển tới Google.
2. `GET /auth/google/callback` → `Cache::pull(stateKey)` (**pull = one-time, không reuse được state**), so `hash_equals(flow_hash)`. Resolve user:
   - Theo `google_id` → update avatar.
   - Không có → theo `email`: nếu tồn tại, **link** google_id (nếu email chưa verify → auto-verify vì Google đã verify); nếu email đang link google_id khác → lỗi.
   - Không có cả hai → create user mới với password random 64 ký tự (không dùng được), auto-verify email, gán role `user`.
   - **Username collision retry:** tối đa 3 lần với `UniqueConstraintViolationException` on `users_username_unique` (UsernameGenerator sinh từ email).
   - User không active → lỗi; user không có role `user` (ví dụ admin) → 403 `FORBIDDEN_ROLE`.
3. Issue `exchange_code` (Str::random(64)), lưu `Cache::put('auth:google:exchange:'.sha256(code), {user_id, flow_hash}, 60s)`; redirect về FE kèm code.
4. `POST /auth/google/exchange` → `Cache::pull` code one-time, verify flow_hash khớp, trả bearer token.

**Spring mapping:** Caffeine, TTL state **300 giây**, cookie **5 phút**, exchange code **60 giây**. Consume phải atomic remove, không get rồi evict. `hash_equals` → `MessageDigest.isEqual`. Thiếu cookie không consume; mismatch cookie sau pull có consume. Callback payload lỗi vẫn redirect SPA, không JSON 422.

---

## 3. Discovery (`PlaceScorer`, `SelectBestPlace`, `DiscoveryFilters`)

- Endpoint `POST /api/discovery/random`, public, optional auth.
- **Hard filter** trước (PlaceScorer chỉ chấm ứng viên đã qua filter):
  - Chỉ place `status=active`, `is_verified=true`, chưa soft-delete.
  - category/district/tag_ids/min_price/max_price.
  - `open_now`: mặc định **true**; không có opening hours hoặc `always_open` coi như mở (nguyên tắc "unknown = open").
  - `lat/lng` phải đi cặp; bán kính mặc định **5 km** khi có tọa độ mà thiếu `radius_km`; `radius_km` in 0.5–50.
- **Scoring** (weight đảm bảo priority không bị đảo, xem [`PlaceScorer`](../../../hnaj-be/app/Actions/Discovery/PlaceScorer.php:26)):
  1. `WEIGHT_NOT_EXCLUDED = 32` — không có trong `excluded_place_ids` của round hiện tại
  2. `WEIGHT_BOOKMARKED = 16` — user đã bookmark
  3. `WEIGHT_VISITED = 8` — user từng "Đi tới đó"
  4. `WEIGHT_PROXIMITY = 4` — normalize [0,1] theo distance/radius; trung tính 0 khi không có tọa độ
  5. `WEIGHT_RATING = 2` — rating/5.0
- Chọn điểm cao nhất, **random tie-break** giữa các place đồng điểm.
- `excluded_place_ids` max `PlaceRepository::MAX_EXCLUDED_IDS` — đọc constant này khi port.
- Guest (không token): không có boost bookmark/visited.

## 4. Places public

- `GET /api/places/search`: `q` required, trim, max **100** ký tự; sort **rating DESC rồi name ASC**; page-based, mặc định **10**/trang, tối đa **50**. Chỉ place active+verified.
- `GET /api/places/{place}`: chỉ số nguyên; chỉ active + verified + not soft-deleted, ngược lại 404; optional bearer token → có `is_bookmarked`; detail expose thêm description/phone/website/images/opening_hours/is_verified so với card.

## 5. Bookmarks (`CreateBookmark`)

- Chỉ place active (không cần verified? — code check `status=Active`; giữ nguyên).
- Trùng user/place → 409 `ALREADY_EXISTS`; place không khả dụng → 404.
- List trả riêng tư của user, pagination.
- `DELETE /bookmarks/{place}` — theo place_id trong path.

## 6. Visits (`RecordVisit`, [`000016`](../../../hnaj-be/database/migrations/2026_07_26_000016_create_visit_events_table.php))

- `POST /api/visits` public + optional bearer:
  - Có user → `visit_events`; không có → yêu cầu header `X-Anonymous-Id` (thiếu → 422 `ANONYMOUS_KEY_REQUIRED`), lưu **SHA-256 hash** của anonymous id (128 hex chars) vào `anonymous_visit_events`. Không lưu IP/plaintext.
  - Place không public khả dụng → 404.
  - `visit_date` theo **Asia/Ho_Chi_Minh** (`ZonedDateTime.now(ZoneId.of("Asia/Ho_Chi_Minh")).toLocalDate()`), `visited_at` lưu **UTC**.
  - **Idempotent:** trùng (user/place/date) hoặc (place/anon_hash/date) → trả bản ghi cũ với `created=false`, HTTP 200.
  - `source` in: discovery,detail,search,bookmarks,history; max 30.
- `GET /api/visits` (auth): lịch sử **unique theo place** của chính user, pagination.

## 7. Manager applications

- User xin làm sub-admin cho place hiện hữu (`POST /api/manager-applications`, user_id + place_id) — transaction khi Admin approve: tạo/refresh `place_managers`, gán role `sub_admin` (nếu user chưa có), set `approved_user_id`, status, `reviewed_by/at/reason`.
- Reject: status `rejected` + `review_reason` bắt buộc khi review (mọi duyệt/moderation cần lý do).
- `CreateAdminPlace`/admin tạo place: place mới **`is_verified=false`** → vào verification queue.

## 8. Admin Places

- `ListAdminPlaces`: phân trang, filter; **bao gồm cả unverified**; soft-deleted bị loại trừ.
- `UpdateVerifiedPlace`: cập nhật full field + opening hours (xóa/ghi lại theo place); validation `day_of_week` in **2..8**.
- `SoftDeletePlace`: `deleted_at`; public endpoints (search/detail/discovery/bookmark visit) phải ẩn place đã soft-delete.
- `HardDeletePlace`: xóa hẳn kèm cascade (bookmark/visit/review...) trong transaction; **DELETE không cần body** (đã bỏ `confirm_name`).
- Verification queue: unverified + active, `index(is_verified,id)` phục vụ queue query.
- `CreatePlaceManager`: transaction — tạo user mới + role `sub_admin` (assigned_by=admin) + `place_managers` row + account setup token + email; **chưa login được tới khi kích hoạt** (email chưa verify).
- Revoke manager: set `revoked_at` (không xóa row); có thể thu hồi cả role sub_admin nếu không còn place nào — kiểm tra `AdminPlaceManagerRevokeController` khi port.
- `CreateTag`: slug tự sinh từ name, mặc định active.

## 9. Quy ước chung

- **Tiền:** DB/API giữ **integer VND** (min_price/max_price), không đổi contract số; format `vi-VN` là việc FE.
- **Ngày trong tuần:** `2=T2 … 7=T7, 8=CN` (đây là bug từng xảy ra — regression test `AdminPlaceOpeningHoursUpdateTest` bảo vệ).
- **Soft-delete semantics:** hidden/soft-deleted → dữ liệu liên quan chỉ hiển thị "làm mờ", không sửa/xóa/thêm mới.
- **Timestamps:** Laravel `timestamps()` = created_at/updated_at; port sang JPA dùng `@CreatedDate/@LastModifiedDate` hoặc trigger — chọn 1 cách ở Phase 1 và ghi vào matrix.
- **Múi giờ DB:** Laravel lưu `dateTime` theo cấu hình app (`config/app.php` timezone) — kiểm tra `APP_TIMEZONE`/`DB_TIMEZONE` khi viết Flyway defaults.
