# 04 — Authentication, Authorization & Security Config

> Nguồn: [`config/auth.php`](../../../hnaj-be/config/auth.php), [`config/sanctum.php`](../../../hnaj-be/config/), [`bootstrap/app.php`](../../../hnaj-be/bootstrap/app.php:17),
> [`EnsureUserHasRole`](../../../hnaj-be/app/Http/Middleware/EnsureUserHasRole.php:18),
> [`IssueAccessToken`](../../../hnaj-be/app/Actions/Auth/IssueAccessToken.php:14).

## Guards & providers
- API dùng guard `sanctum` (personal access token, DB driver).
- Provider `users` → model `App\Models\User`, table `users`.
- Session guard tồn tại cho web nhưng API thuần bearer token.

## Token lifecycle
- Phát hành: `$user->createToken('spa')->plainTextToken` — token không mang **abilities/role**.
- Thu hồi logout: `RevokeAccessTokens` xóa token hiện tại.
- Sau đổi password (luồng password recovery, **planned**): thu hồi toàn bộ token khác của user.
- Đổi password **chưa phải MVP đã code xong** — xem project-progress `planned`. Chỉ `PATCH /auth/me` (full_name) đã có.
- **Spring (Q17, chốt 2026-09-05):** opaque token 64 hex do Spring phát hành, DB chỉ lưu SHA-256 hash trong bảng `access_tokens` (Flyway `V2__access_tokens.sql`, `token_hash` UNIQUE, `expires_at` NULL indexed). **KHÔNG tương thích format Sanctum** — không chia sẻ token giữa hai backend; API contract giữ nguyên (`Authorization: Bearer <token>`, roles không nằm trong token).
- **Spring (cập nhật 2026-09-11, review Phase 2):** token phát hành có TTL mặc định **24 giờ** (`hnaj.auth.token-expires-in-hours`, env `HNAJ_TOKEN_EXPIRES_IN_HOURS`; đặt `0` để tắt TTL). Lệch Laravel (Sanctum `expiration=null` + không set `expires_at` → không hạn) nhưng được duyệt trong review Phase 2 như một hardening; `resolve()` lọc `expires_at IS NULL OR expires_at > now()`.
- **Spring (cập nhật 2026-09-11):** `BearerAuthFilter` chỉ chấp nhận token khi user `status=active` và chưa soft-delete (`deleted_at IS NULL`) — Laravel mặc định vẫn accept token của user suspended/soft-deleted (không có middleware trạng thái sau auth), Spring siết chặt hơn; login vẫn chặn suspended trước (parity).

## Authorization = DB read at request time
`EnsureUserHasRole` (`role:` middleware alias, khai báo ở [`bootstrap/app.php`](../../../hnaj-be/bootstrap/app.php:18)):
1. Không có user → 401 `UNAUTHENTICATED`.
2. `$user->loadMissing('roles')`, so với required roles → thiếu → 403 `FORBIDDEN_ROLE`.
- **Lý do design:** cho phép Admin thu hồi role có hiệu lực NGAY cả với token đã phát hành. Spring phải giữ nguyên (không nhét role vào JWT).

## Route middleware map (đọc từ [`routes/api.php`](../../../hnaj-be/routes/api.php))
| Nhóm middleware | Áp cho |
|---|---|
| `auth:sanctum` + `role:user,sub_admin` | me, patch me, manager-applications, bookmarks, GET visits |
| `auth:sanctum` (không role) | POST /auth/logout |
| `auth:sanctum` + `role:admin` | mọi `/api/admin/*` sau admin login |
| `optional` bearer (resolve qua sanctum nếu có) | `POST /visits`, `GET /places/{id}`, `POST /discovery/random` (cho is_bookmarked/visited boost) |
| `throttle:N,M` | từng endpoint — giá trị ở [`00-inventory-endpoints.md`](./00-inventory-endpoints.md) |
| `whereNumber('place')` | `GET /places/{place}` — place không phải số → 404 |

## Password hashing
- `BCRYPT_ROUNDS=12`, `$2y$` prefix. Spring: `BCryptPasswordEncoder(12)`. **Tương thích hash cũ** nếu chạy chung DB — verify bằng cách load 1 hash Laravel rồi check bằng Spring BCrypt (test trong Phase 3).

## Cache backend
- Laravel dùng cache cho Google OAuth: state **300 giây**, flow cookie **5 phút**, exchange code **60 giây** theo `RedirectToGoogle` và `HandleGoogleCallback`. Spring đã chọn Caffeine in-memory cho single instance; tách TTL và consume atomically. Không dùng TTL chung 2 phút của scaffold cho cả hai.

## Session/Queue
- `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`. API thuần bearer nên session ít dùng cho API. Mail hiện `MAIL_MAILER=log` (không gửi thật ở dev) — Spring dùng `spring.mail` JavaMailSender, dev có thể log.

## Security red lines (từ AGENTS.md §10)
- Không hard-code credential; mọi secret qua env.
- Không lộ stack trace/SQL/field nội bộ trong error response.
- Không log anonymous_id plaintext (chỉ hash).
- Rate limit là control backend, không public quota cho client.

## Checklist chống miss khi port auth
- [ ] Cùng 1 plaintext token → cùng 1 SHA-256 hash (tương thích Sanctum nếu chạy chung DB).
- [ ] Role đọc từ DB mỗi request, không từ token.
- [ ] Anti-enumeration dummy-hash trên login path.
- [ ] Token one-time dùng `SELECT ... FOR UPDATE` (JPA `@Lock(PESSIMISTIC_WRITE)`).
- [ ] Google state/exchange code pull = consume-once.
- [ ] Logout thu hồi đúng token hiện tại.
- [ ] 401 vs 403 phân biệt đúng (thiếu token vs sai role).
