# Theo dõi tiến độ dự án HNAJ

- **Cập nhật:** 2026-09-12 (Cấu hình agent skills cho Phase 3: local-markdown tracker + labels mặc định + commit policy manual)
- **Trạng thái tổng:** Phase 2 Auth migration hoàn tất (51/51 test PASS trên MySQL); Phase 3 đã có kế hoạch 5 sub-phase (3A–3E) chờ triển khai qua workflow to-spec → to-tickets → implement

## Setup agent skills (2026-09-12)

- Chạy `/setup-matt-pocock-skills` hoàn tất: issue tracker **local markdown** (`.scratch/<feature>/spec.md` + `issues/<NN>-<slug>.md`), 5 triage labels mặc định, domain docs single-context (`CONTEXT.md`/`docs/adr/` tạo lazily; trước đó dùng `docs/migration/knowledge-base/` làm nguồn domain cho port BE).
- Commit policy chốt **manual** — skill không tự commit/push, dừng ở diff + gợi ý commit message (bám AGENTS.md §13/§14); người dùng commit tay.
- File tạo/sửa: `docs/agents/issue-tracker.md`, `docs/agents/triage-labels.md`, `docs/agents/domain.md`, thêm section `## Agent skills` vào `AGENTS.md`. `skills-lock.json` thay đổi do lệnh `npx skills@latest add` của người dùng.
- Kế hoạch Phase 3 (5 sub-phase 3A–3E, 24/39 endpoint còn lại) đã trình bày và được duyệt hướng; bước tiếp theo: `/to-spec` tổng hợp spec rồi `/to-tickets` tách ticket theo blocking edges.

## Phase 3 — spec + tickets (2026-09-12)

- `/to-spec` xong: spec tại `.scratch/be-java-phase-3/spec.md` (label `ready-for-agent`) — 35 user stories, seams tái dùng từ Phase 2 (MockMvc HTTP + MySQL thật; unit thuần cho scorer/validator), out-of-scope ghi rõ (PlaceImport Q10, notification Q18, image storage Q19, cutover chỉ làm checklist).
- `/to-tickets` xong: 13 ticket tracer-bullet tại `.scratch/be-java-phase-3/issues/01..13` — người dùng vắng mặt nên agent tự quyết theo khuyến nghị (granularity như đề xuất). Blocking edges: 01 không blocker; 02–04, 06–07, 09 block bởi 01; 03 block 01; 05 block 01+03; 08 block 07; 10 block 01+09; 11 block 10; 12 block 11+08; 13 block 02+04+06+11+12.
- Frontier khởi đầu: ticket 01 (sau đó 02/03/04/06/07/09 chạy được song song khi blocker xanh). Mỗi ticket làm trong context riêng, TDD port test Laravel trước, verify trong Docker, matrix 4 cột mới được ✅.
- Trạng thái: `planned` — chưa triển khai code; chờ người dùng duyệt bắt đầu ticket 01.

## Task Migration BE Laravel → Java Spring Boot — Phase 0 + Phase 1 + Phase 2 hoàn thành

- Phạm vi đã duyệt (trao đổi 2026-09-05): (1) trích xuất toàn bộ tri thức BE Laravel thành knowledge base; (2) tạo folder mới `hnaj-be-java` với stack Java 21 + Spring Boot 3.3.x + Spring Data JPA + Maven + Flyway, DB riêng `hnaj_java`, giữ nguyên API contract để FE không đổi; (3) triển khai từng domain theo knowledge base kèm traceability matrix chống miss cases.
- Phase 0 (đã xong): 9 file tại `docs/migration/knowledge-base/` — README, 00-inventory-endpoints (39 endpoint + throttle + auth), 01-api-contract (envelope + error codes + exception mapping), 02-schema (38 migration → Flyway baseline), 03-domain-rules (business rules từng use case), 04-auth-security (Sanctum/role-from-DB/BCrypt/cache/throttle), 05-test-catalog (239 tests → behavioral spec), 06-traceability-matrix (39 endpoint × 20 cross-cutting × symbol inventory), 07-open-questions (16 câu hỏi cần duyệt).
- Quyết định Q1–Q16 đã chốt 2026-09-05: package `com.hnaj`, Flyway baseline V1 gộp 38 migration, Caffeine in-memory cache, DB `hnaj_java`/`hnaj_java_test`, `docker-compose.spring.yaml` riêng (không sửa `compose.yaml`), JPA `ddl-auto=none`, Spring phát hành token riêng (chỉ tương thích API contract, không tương thích Sanctum token), `PlaceImport` KHÔNG port.
- Phase 1 scaffold (đã xong): folder `hnaj-be-java/` gồm `pom.xml` (Spring Boot 3.3.5, Java 21, JPA, Flyway, Security, Cache, Caffeine, Lombok, H2 test), `Dockerfile` (multi-stage temurin 21, non-root, health check), `docker-compose.spring.yaml` (override, share network `hnaj_hnaj`), `application.yml` (DB env-driven, JPA `none`, Flyway baseline V1, BCrypt rounds=12, Asia/Ho_Chi_Minh, day_of_week 2..8, search constants), `application-smoke.yml` cho test offline, `V1__baseline_schema.sql` 603 dòng / 27 bảng (RESTRICT FK + CASCADE cho review_images/comment_images + CHECK rating 0.0–5.0), `ApiResponse`/`ApiExceptionHandler` mapping 1-1 với [`hnaj-be/app/Http/Responses/ApiResponse.php`](../../hnaj-be/app/Http/Responses/ApiResponse.php) + [`ApiExceptionHandler.php`](../../hnaj-be/app/Exceptions/ApiExceptionHandler.php), `AuthFlowException`/`BookmarkException`/`VisitException` + 3 enum code mapping 1-1 với `hnaj-be/app/Enums/*ErrorCode.php`, `TestController` endpoint #1 `GET /api/test`, `SecurityConfig` (stateless, BCryptPasswordEncoder(12)), `TestControllerTest` (MockMvc, H2 in-memory profile `smoke`).
- Kiểm chứng Phase 1: `mvn -B -q test` chạy trên host Maven 3.9.16 + JDK 21 → `TestControllerTest.returnsSuccessEnvelopeWithServiceStatus` **PASS** (1/1 tests, 0 failures, 0 errors); envelope shape khớp Laravel TestController (`success: true`, `message`, `data.service`, `data.status`). DB integration test thật sẽ chạy trong Docker Compose theo [`AGENTS.md` §3.1](../../AGENTS.md:87) khi có service `backend-java` chạy.
- Không sửa code Laravel/FE, không sửa `compose.yaml` chính, không tự commit.
- Còn lại: chưa chạy `docker compose -f docker-compose.spring.yaml up` (cần MySQL tạo DB `hnaj_java` + `hnaj_java_test`); chưa dùng Bucket4j vì cần duyệt version thật (chưa thuộc Q1–Q6).
- Phase 2 — `done` (hoàn tất 2026-09-11 sau review + vá gap + regression test; xem chi tiết ở mục kiểm chứng 2026-09-11 bên dưới): 14 endpoint #6–#16, #23–#25 và bootstrap admin non-HTTP. Q17 đã chốt opaque token 64 hex, SHA-256, bảng riêng `access_tokens`/Flyway V2; không còn chờ JWT/opaque.
- Quyết định bổ sung: người dùng cho phép dùng AGENTS.md/convention hiện có khi `.agents/rules/` và `.agents/skills/` vắng; port theo code/test Laravel và sửa KB: OAuth state 300s/cookie 5m/exchange 60s, redirect trả JSON URL, logout thường auth-only, setup không đổi status.
- Thứ tự triển khai đã duyệt: sửa scaffold Docker/Maven/config → persistence/security → credentials/mail/profile/bootstrap → Google OAuth/Bucket4j → port 80 test methods nguồn và bổ sung edge cases → Docker/MySQL integration, contract/matrix.
- Khu vực dự kiến: `hnaj-be-java/`, `hnaj-docker/docker-compose.spring.yaml`, `docs/migration/knowledge-base/`, file tiến độ này. Không sửa Laravel/FE, không cutover, không commit/push. Dependency bổ sung mail/Thymeleaf/Flyway MySQL/Boot Maven plugin theo Boot parent và Bucket4j starter 0.9.0 đã được duyệt trong plan.
- Blocker/rủi ro cần kiểm tra: Docker/MySQL readiness, database Java riêng và quyền kết nối; provision DB/user/grant, credential thật, SMTP/Google live và tạo admin thật cần phê duyệt riêng. Không clean/drop/truncate/reset database. Caffeine chỉ single-instance.
- Kiểm chứng hiện tại (2026-09-07): scaffold Docker đã sửa xong (build context `../hnaj-be-java`, target `runtime`/`backend-java-test`, network external `hnaj_hnaj`, bỏ `depends_on` MySQL không hợp lệ, placeholder `:` thay `:-`, `DB_PASSWORD` bắt buộc, test profile `DB_TEST_DATABASE=hnaj_java_test` + `clean-disabled: true`); persistence hoàn tất (entity/repository auth + `V2__access_tokens.sql` + `V3__auth_roles.sql`).
- Mã Java Phase 2 đã viết: access token opaque (SHA-256) + bearer filter + role đọc DB mỗi request + `@RequireRole`; services register/login/user+admin/verify/resend/setup/profile/logout; mail log-mode Thymeleaf (2 template txt); Google OAuth flow (state 300s/exchange 60s/cookie 5p, resolve-user, username retry ≤3, transaction-atomic create); Bootstrap admin CLI opt-in (`HNAJ_BOOTSTRAP_ADMIN=true`); rate limit Bucket4j core (`RateLimitService`, mirror throttle Laravel: register 10, login 5, verify 10, resend 5, setup 10, google 3×10, admin login 5); toàn bộ controller cho 14 endpoint.
- Kiểm chứng đã chạy trong Docker (DOCKER_BUILDKIT=0 do buildx lỗi read-only): `mvn -DskipTests test-compile` **BUILD SUCCESS** (73 file chính); `mvn test` offline (smoke/H2) và MySQL integration (profile `test` → `hnaj_java_test`) đều PASS.
- **2026-09-07 — MySQL integration FULL PASS:** 47/47 tests (AuthInput 6, RateLimit 4, UsernameGenerator 4, TokenCodec 2, AuthSecurityContract 6, TestController 1, AuthFlowIntegration 24) trên MySQL 8.4 thật (container `hnaj-mysql-1`, DB `hnaj_java`/`hnaj_java_test` + user `hnaj_java` đã được người dùng phê duyệt provision; password dev sinh ngẫu nhiên lưu trong `hnaj-docker/.env` — git-ignored, KHÔNG vào repo). Flyway V1+V2+V3 chạy sạch; BCrypt cost 12 xác thực chéo; UserView khớp UserResource snake_case (`full_name`, `email_verified`, `avatar_url`) — một lỗi camelCase đã bị integration test bắt và sửa.
- Phạm vi auth đã test thật trên MySQL: register (duplicate/mail-fail rollback/normalize), login (sai mật khẩu/unverified/suspended/admin-role→403), verify (tokenc 24h, 409 không tiêu token, invalid), resend (neutral + token mới invalidates token cũ), account setup (đặt mật khẩu + verify email, giữ status, token dùng 1 lần), me/PATCH (chỉ full_name, username/email không đổi), logout (chỉ thu hồi token hiện tại), admin login/me/logout, role revocation tác dụng ngay request kế (đọc DB mỗi lần), Google OAuth full flow (redirect JSON+cookie → callback 302 code one-time → exchange; wrong-cookie đốt code như Laravel `Cache::pull` trước `hash_equals`; callback thiếu cookie → redirect SPA error), bootstrap admin (tạo admin đầu, từ chối admin thứ 2/username/email trùng).
- Rate limit Bucket4j core (`RateLimitService`/`RateLimitFilter`): mirror throttle Laravel (register 10, login 5, verify 10, resend 5, setup 10, google redirect/callback/exchange 10, admin login 5), 429 `TOO_MANY_REQUESTS` + Retry-After 60; đã kiểm chứng theo ranh giới (login attempt thứ 6 → 429) và unit bucket riêng theo IP.
- Image runtime `backend-java` (target runtime) build OK → tag `hnaj-be-java:dev`.
- **2026-09-11 — Review Phase 2 + vá gap bảo mật + bổ sung regression test (hoàn tất Phase 2):**
  - Review code toàn bộ auth flow so với Laravel source (`LoadAuthenticatedUser`, Sanctum `Guard`, `AuthenticateCredentials`, `IssueAccessToken`, `HandleGoogleCallback`, Form Requests) và 2 báo cáo review phụ: xác nhận parity cho register/login/verify/resend/setup/me/profile/logout/admin/Google/bootstrap; tìm thấy gap thực sự ở bearer-token lifecycle.
  - **Vá 1 — TTL token:** `AccessTokenService.issue` giờ đặt `expires_at = now + 24h` (mặc định `hnaj.auth.token-expires-in-hours` = `HNAJ_TOKEN_EXPIRES_IN_HOURS`, đặt `0` để tắt TTL); `resolve()` đã lọc token hết hạn từ trước. Lệch Laravel (Sanctum không hạn) — đã ghi nhận trong `04-auth-security.md` như hardening được duyệt khi review.
  - **Vá 2 — trạng thái tài khoản:** `BearerAuthFilter` qua `AccessTokenService.resolve` chỉ accept token khi user `status=active` và `deleted_at IS NULL` (`findByTokenHashAndUserStatusAndUserDeletedAtIsNull`). Laravel mặc định vẫn accept token của user suspended/soft-deleted; Spring siết chặt (login đã chặn suspended trước nên parity phần login giữ nguyên).
  - **Vá 3 — input bound:** `AuthInput.password` chặn password > 1024 byte (bcrypt chỉ dùng 72 byte đầu; Laravel không có max nhưng đây là safety net); `server.tomcat.max-http-form-post-size=64KB` + `max-http-request-header-size=16KB` giới hạn body/header ở edge.
  - Regression test mới: `suspendedAccountBearerTokenIsRejected`, `softDeletedAccountBearerTokenIsRejected`, `expiredBearerTokenIsRejected` (401 `UNAUTHENTICATED` sau khi đổi trạng thái/hết hạn), `meAndUpdateProfileWithBearer` mở rộng assert `expires_at` non-null, `AuthInputTest#passwordOverByteCapIsRejectedAsTooLong`.
  - **Kiểm chứng Docker (2026-09-11):** rebuild image test (`DOCKER_BUILDKIT=0`), chạy `mvn test` profile `test` trên MySQL 8.4 thật (`hnaj_java_test`): **51/51 PASS, BUILD SUCCESS** (AuthInput 7, RateLimit 4, UsernameGenerator 4, TokenCodec 2, AuthSecurityContract 6, TestController 1, AuthFlowIntegration 27). Lưu ý vận hành: container test cần `JAVA_TOOL_OPTIONS="-Xmx256m -Xms64m -XX:MaxMetaspaceSize=128m -XX:+UseSerialGC -Xss512k -XX:MaxDirectMemorySize=16m -XX:TieredStopAtLevel=1 -XX:CICompilerCount=1"` — JVM mặc định (~1GB+) khiến host thiếu RAM và OOM-kill MySQL giữa run (đã gặp lặp lại; không phải lỗi test).
  - Đồng bộ docs: `00-inventory-endpoints.md` đánh `[x]` cho #1, #6–#16, #23–#25; `06-traceability-matrix.md` chuyển C1–C4, C6–C7, C11–C14, C19–C20 sang `✅` kèm bằng chứng test; `04-auth-security.md` bổ sung 2 quyết định TTL + status-filter.
- Chưa xong (chuyển sang Phase 3 / backlog): C11 concurrency multi-thread, C12 TTL lệch clock, C13 username retry race, C14 timing enumeration — đã có path parity nhưng chưa test chuyên sâu; C19 SMTP thật; password recovery (planned); logout-all/password-change revocation; account-level brute-force lockout (rate limit hiện per IP/route); smoke FE/contract FE sau cutover (chưa duyệt).
- **Nguồn nghiệp vụ:** [`docs/prd.md`](../docs/prd.md:1)
- **Nguồn auth hiện có:** [`docs/api-auth.md`](../docs/api-auth.md:1)
- **Nguồn response:** [`docs/api-response-contract.md`](../docs/api-response-contract.md:1)

## Quy ước trạng thái

- `done`: đã có implementation và/hoặc contract đủ để kiểm chứng.
- `in_progress`: đang triển khai hoặc đang được thiết kế.
- `planned`: đã chốt phạm vi, chưa triển khai.
- `blocked`: thiếu quyết định, dependency hoặc điều kiện vận hành.
- `deferred`: ngoài MVP hoặc cố ý để phase sau.

## Tổng quan theo khu vực

| Khu vực | Trạng thái | Ghi chú |
|---|---|---|
| Docker/bootstrap | `done` | Compose có backend, backend-web, frontend, MySQL. |
| Auth User | `done` | Register, email verification, login, logout, me, Google OAuth đã có route/implementation. |
| Auth Admin | `done` | Admin login, logout, me, bootstrap one-time đã có. |
| Auth Sub-admin | `done` | Dùng chung `/api/auth/login` và `/api/auth/me` với role `user` hoặc `sub_admin`; tài khoản Sub-admin kích hoạt qua token setup. |
| Discovery/search | `done` | Random, search, filter, pagination đang có implementation/test. |
| Place detail | `done` | `GET /api/places/{id}` public + optional auth; FE `/places/:placeId` fetch theo id, gallery/hours/contact/placeholder review; visit tracking tách task. |
| Taxonomy discovery | `done` | Đã triển khai `GET /api/meta/discovery`; frontend dùng dữ liệu active từ backend, không còn taxonomy ID hard-code. Lint/build đạt; backend test bị chặn bởi dotenv Compose hiện tại. |
| Giá hiển thị | `done` | API/DB giữ integer VND; UI thống nhất `vi-VN` + hậu tố `VNĐ` qua `formatVnd`. |
| Profile | `done` | Chỉ sửa `full_name`; `username`, `email`, `avatar_url` read-only. `PATCH /api/auth/me` + UI trang `/account`. |
| Password recovery/change | `planned` | User/Sub-admin: reset token một lần 24 giờ; đổi cần password hiện tại; đổi xong thu hồi token khác. Admin chỉ đổi nội bộ. |
| Bookmark/visit/history | `done` | Bookmark API hoàn chỉnh (GET/POST/DELETE), frontend BookmarksPage redesign; Visit tracking + lịch sử đã có `POST /api/visits` (public, optional auth, idempotent) và `GET /api/visits` (auth, unique place). |
| Review/comment | `planned` | User đăng nhập; review cần visit, một review/place; comment nhiều; reply theo quyền. |
| User content images | `planned` | Tối đa 5 ảnh/nội dung, 5 MB/ảnh, JPEG/PNG/WebP; hiển thị ngay; Admin ẩn/gỡ sau. |
| Report/moderation | `planned` | Report review/comment/ảnh; lý do cố định; một report mở/User/nội dung; Admin pending → dismissed/actioned. |
| Place request | `planned` | Người gửi xem/sửa/hủy khi pending; Admin duyệt/từ chối kèm lý do. |
| Manager application | `done` | User có thể xin quản lý place hiện hữu; Admin approve/reject; assignment và role được xử lý trong transaction. |
| Promotion request | `planned` | Sub-admin tạo/xem/sửa/hủy khi pending; Admin duyệt/từ chối kèm lý do; chưa placement. |
| Notification center | `planned` | User/Sub-admin/Admin; request, promotion, moderation, account; pagination, realtime, mark-one/read-all, lưu đến người dùng xoá/đánh dấu đã đọc. |
| Email notifications | `planned` | Kết quả request gửi email; không lộ dữ liệu nhạy cảm. |
| Admin management | `done` | Đã có MVP quản lý Place, cấp/thu hồi Sub-admin và xử lý manager application. |
| Soft-delete UX | `in_progress` | Admin Place đã chuyển sang soft-delete; UX khôi phục và hiển thị nội dung liên quan còn là phần tiếp theo. |
| Place hard-delete | `deferred` | Luồng admin hiện dùng soft-delete; hard-delete chưa thuộc phạm vi task này và chưa nên chạy trong runtime. |
| Taxonomy delete | `done` | Category/tag/district không xoá; chỉ update/status theo quyết định. |
| API contract | `in_progress` | Taxonomy discovery đã có contract; các domain còn lại cần hoàn thiện URL, payload, auth, status, pagination, validation, resource. |
| Frontend integration | `in_progress` | Taxonomy discovery đã nối API; các domain khác chờ contract. |

## Quyết định nghiệp vụ đã chốt

### Account

- `username` là tên đăng nhập, unique, cố định; không có API đổi.
- Profile MVP chỉ cập nhật `full_name`.
- `email` và `avatar_url` giữ nguyên, read-only.
- Reset password cho User/Sub-admin: email reset token một lần, hết hạn 24 giờ.
- Đổi password trong phiên: bắt buộc password hiện tại + password mới.
- Sau đổi password: thu hồi toàn bộ token đăng nhập khác.
- Admin không mở quên password công khai; chỉ đổi qua luồng nội bộ.

### Money

- API/database giữ số nguyên VND, không đổi contract số.
- UI dùng locale `vi-VN`, ví dụ `500.000 VNĐ`, `1.000.000 VNĐ`.
- Không hiển thị dạng `500 nghìn`, `1tr`, `500000đ` hoặc `vnđ` viết thường trong UI chuẩn.

### User content

- Review/comment cho phép User upload ảnh trong MVP.
- Tối đa 5 ảnh cho mỗi review/comment.
- Mỗi ảnh tối đa 5 MB.
- Định dạng cho phép: JPEG, PNG, WebP.
- Ảnh hiển thị ngay sau upload.
- Admin có thể duyệt/ẩn/gỡ sau.

### Reports and anti-spam

- Chỉ User đăng nhập gửi report review/comment/ảnh.
- Lý do: `spam`, `abuse`, `inappropriate`, `copyright`, `other`.
- Mô tả tùy chọn.
- Một User chỉ có một report đang mở cho cùng nội dung.
- Admin xử lý `pending` → `dismissed` hoặc `actioned`.
- Rate limit là control backend, không công khai quota cho client.
- Backend áp dụng giới hạn nội dung/report và IP throttle cho endpoint public; giá trị cụ thể không đưa vào response/document public.

### Request, notification, admin

- Request pending được người gửi xem, sửa hoặc hủy.
- Admin duyệt/từ chối kèm lý do.
- Kết quả gửi email và notification center.
- Notification center hỗ trợ pagination, realtime, mark-one/read-all; User/Sub-admin/Admin sử dụng.
- Admin xem/tìm kiếm/phân trang; khóa/mở tài khoản; cấp/thu hồi role Sub-admin.
- Admin CRUD place/category/tag/district theo status; category/tag/district không được hard-delete, chỉ update/status.
- Admin duyệt request, ẩn/gỡ review/comment/ảnh, xử lý report, xem dashboard hot/visit/request.
- Mọi duyệt/moderation cần lý do.

### Place lifecycle

- Soft-delete/hidden: dữ liệu liên quan có thể truy cập dưới trạng thái làm mờ; không chỉnh sửa/xóa/thêm mới.
- Hard-delete place: xóa hẳn place cùng bookmark, visit, review, comment, ảnh và lịch sử liên quan; history User mất dữ liệu đó.
- Hard-delete là thao tác destructive; cần transaction, authorization mạnh, confirmation và test cascade/rollback.

## API draft theo trạng thái

### Đã có implementation/route

- `GET /api/admin/places`
- `POST /api/admin/places`
- `GET /api/admin/places/verification-queue`
- `GET /api/admin/places/{place}`
- `PATCH /api/admin/places/{place}`
- `DELETE /api/admin/places/{place}` (soft-delete)
- `GET/POST /api/admin/places/{place}/managers`
- `POST /api/admin/places/{place}/managers/{user}/resend`
- `DELETE /api/admin/places/{place}/managers/{user}`
- `GET /api/admin/manager-applications`
- `POST /api/admin/manager-applications/{id}/approve`
- `POST /api/admin/manager-applications/{id}/reject`
- `POST /api/manager-applications`
- `POST /api/auth/account/setup`

- `GET /api/test`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/email/verify`
- `POST /api/auth/email/resend`
- `GET /api/auth/google/redirect`
- `GET /api/auth/google/callback`
- `POST /api/auth/google/exchange`
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `POST /api/admin/auth/login`
- `GET /api/admin/auth/me`
- `POST /api/admin/auth/logout`
- `POST /api/discovery/random`
- `GET /api/places/search`

### Đã triển khai gần nhất

- Place detail: `GET /api/places/{place}`; public, optional bearer token cho `is_bookmarked`; chỉ place active + verified; 404 cho hidden/unverified/soft-deleted; FE `/places/:placeId` fetch theo id (không còn phụ thuộc `location.state` để F5/share).
- Taxonomy discovery: `GET /api/meta/discovery`; public, read-only, trả category/district/tag active trong một response; không migration, không dependency mới.

### Cần thiết kế và triển khai MVP

- Profile: `GET/PATCH /api/auth/me`; chỉ `full_name` writable.
- Password: forgot request, reset, change; token one-time 24 giờ; thu hồi token khác sau change.
- Places: hot, public status behavior (detail đã có `GET /api/places/{place}`).
- Categories/districts/tags: public list; Admin update/status.
- Bookmarks: list/create/delete của User.
- Visits/history: create-on-go, history list, dedupe theo User-place-ngày.
- Reviews/comments: CRUD owner, review eligibility, reply, pagination.
- Images: upload/delete/attach cho review/comment; validation 5 ảnh, 5 MB, JPEG/PNG/WebP.
- Reports: create/list Admin/update status; polymorphic target review/comment/image.
- Place requests: create/list/detail/update/cancel pending; Admin list/detail/approve/reject với reason.
- Manager applications: create/list/detail/update/cancel pending; Admin approve/reject; account setup token.
- Promotion requests: Sub-admin create/list/detail/update/cancel pending; Admin approve/reject.
- Notifications: list, unread count, mark-one/read-all/delete; realtime transport cần chốt kỹ thuật trước implementation.
- Admin: users/roles, places, taxonomy, requests, moderation, reports, dashboard.
- Place lifecycle: hide/restore/hard-delete, blurred related resources, destructive cascade.

### Đang lệch hoặc cần đồng bộ tài liệu

- [`docs/prd.md`](../docs/prd.md:291) còn nói User không upload ảnh, trái với quyết định mới: User upload ảnh review/comment.
- [`docs/prd.md`](../docs/prd.md:634) còn ghi password reset chưa thuộc phạm vi, trái với quyết định mới.
- [`docs/prd.md`](../docs/prd.md:814) chỉ ghi email request, cần bổ sung notification center/realtime.
- [`docs/api-auth.md`](../docs/api-auth.md:13) chưa có auth Sub-admin.
- [`hnaj-be/app/Enums/NotificationType.php`](../hnaj-be/app/Enums/NotificationType.php:8) mới có email delivery types, chưa model hóa in-app notification.
- [`hnaj-be/database/migrations/2026_07_26_000024_create_notification_deliveries_table.php`](../hnaj-be/database/migrations/2026_07_26_000024_create_notification_deliveries_table.php:11) là email delivery log, chưa phải notification center.

## Task Profile — đã hoàn thành

- Phạm vi đã duyệt: thêm API `PATCH /api/auth/me` chỉ cập nhật `full_name` (writable); `username`, `email`, `avatar_url` read-only theo quyết định [mục Account](#quyết-định-nghiệp-vụ-đã-chốt). User/Sub-admin dùng chung endpoint; trả `{ user: UserResource }` theo envelope chung.
- Khu vực ảnh hưởng: backend routes/request/action/controller/repository/test, docs API auth, frontend service/context/page/CSS.
- Không migration, không dependency, không đổi Docker config, không breaking contract (endpoint mới bổ sung).
- Đã triển khai: `UpdateProfileRequest` (`full_name` required, string, max 255, trim), `UpdateProfile` action (dùng `UserRepository::update` + `loadRoles`), `UpdateProfileController` (envelope success), route `PATCH /api/auth/me` với `role:user,sub_admin`; frontend thêm `updateProfile` service, context `updateProfile` cập nhật user state, trang `/account` có form đổi tên (loading/success/error), CSS cho `.account-form` và `.form-success`.
- Đã sửa finding review: bỏ import PHP không dùng, test xác nhận `avatar_url` read-only, đồng bộ input `fullName` khi user state đổi, xóa heading tài liệu bị trùng.
- Kiểm chứng trước vòng sửa trong Docker Compose: backend focused `UpdateProfileTest` 7 passed/26 assertions; backend full test 201 passed/811 assertions; frontend `npm run lint` 0 lỗi; frontend `npm run build` (tsc type check + vite) thành công; `git diff --check` đạt. Cần chạy lại sau vòng sửa.
- Ghi chú: trước task, workspace đã có thay đổi ngoài phạm vi là `hnaj-be/app/Repositories/PlaceQuery.php` và `hnaj-be/tests/Feature/Discovery/DiscoveryRandomTest.php`; giữ nguyên theo phê duyệt. Browser QA tại 375/768/1440 chưa thực hiện trong vòng này (môi trường không có browser tool).

## Task admin tag create trong verification — đang triển khai

- Phạm vi đã duyệt: thêm API admin `POST /api/admin/tags`, tag mới mặc định active, response qua envelope + `TagResource`; frontend thêm input/nút tạo tag ngay trong phần Tags của màn hình verification và tự chọn tag vừa tạo.
- Khu vực đã ảnh hưởng: backend routes/controller/request/action/repository/test, docs admin API, frontend admin service/page/CSS.
- Không thay đổi database schema, dependency, Docker config hoặc breaking API hiện có.
- Đã triển khai API tạo tag theo luồng `Route → Controller → Action → Repository → Model`; frontend có input `Thêm tag`, loading/validation feedback, tự chọn tag mới và chip selected màu cam primary.
- Kiểm chứng đạt trong Docker: `docker compose --env-file .env ps`, backend focused test `AdminTagCreateTest` 5 passed/20 assertions, backend full test 166 passed/692 assertions, route list có `POST api/admin/tags`, frontend `npm run lint` 0 lỗi, frontend `npm run build` thành công, `git diff --check` đạt.
- Đã kiểm chứng UI thật bằng Chromium headless trên route admin verification tại 375px, 768px và 1440px: vùng tag hiển thị đầy đủ, selected chip màu cam kèm dấu chọn, input/nút thêm tag rõ ràng, responsive không có horizontal overflow (`scrollWidth = clientWidth`) ở cả ba viewport.
- Đã rà soát ảnh chụp: mobile xếp input và nút theo cột, tablet/desktop xếp cùng hàng; danh sách chip wrap đúng, trạng thái selected không chỉ dựa vào màu. Ảnh, browser profile, script và token QA tạm đã được xóa/thu hồi sau kiểm tra.

## Bug admin opening hours — đang sửa

- Phát hiện khi submit Place: frontend gửi Chủ nhật `day_of_week=8` theo dữ liệu lưu trữ, nhưng `UpdateAdminPlaceRequest` đang validate sai `between:0,6`, gây response `422 VALIDATION_ERROR`.
- Quy ước chuẩn đã được xác minh từ import/discovery/factory: `2=T2 ... 7=T7, 8=CN`.
- Phạm vi đã duyệt: sửa validation sang `2..8`, chuẩn hóa form frontend theo thứ tự `T2..T7,CN`, thêm regression test đủ 7 ngày và kiểm chứng submit thật. Không migration/dependency/breaking contract.
- Đã sửa backend validation và frontend mapping bằng cấu hình ngày rõ ràng `T2=2 ... CN=8`; không còn giữ/mix quy ước `0..6` trong form.
- Regression test mới đạt 2 tests/8 assertions; backend full test đạt 168 tests/700 assertions; frontend lint 0 lỗi và build thành công.
- Đã kiểm chứng PATCH thật qua Chromium trên Place 14 với `days=[2,3,4,5,6,7,8]`: API trả HTTP 200, `success=true`, response lưu đủ Chủ nhật `day_of_week=8`. Đã thu hồi token, xóa artifact/profile và trả Place 14 về `is_verified=false` sau QA.

## Điều chỉnh UX admin Place verification — đã triển khai, QA browser bị chặn

- Đã hiển thị preview cạnh từng URL ảnh hợp lệ; URL trống/không hợp lệ có placeholder ổn định và preview dùng lazy loading.
- Đã bỏ nút bỏ chọn thumbnail; dữ liệu cũ thiếu thumbnail tự chọn ảnh đã lưu đầu tiên. Khi xóa thumbnail, form tự chọn ảnh đã lưu khác; nút xóa bị khóa nếu đó là thumbnail duy nhất.
- Đã thêm nút mở Google Maps bằng liên kết tab mới `target="_blank"` + `rel="noreferrer"`; không gọi service/API visit nên không ghi nhận visited.
- Khu vực ảnh hưởng: frontend page/CSS và file tiến độ; không thay đổi API, database, dependency hoặc Docker.
- Kiểm chứng đạt trong Docker: frontend lint 0 lỗi, frontend build thành công, `git diff --check` đạt.
- QA Chromium 375px/768px/1440px chưa hoàn thành: token admin QA cũ đã hết hiệu lực nên route tự chuyển về `/admin/login`; không tự đọc/tạo credential thật hoặc thay đổi dữ liệu để né auth. Script và ảnh QA tạm của task đã được xóa.

## Đơn giản hóa xác nhận hard-delete Place — đã triển khai, QA browser bị chặn

- Popup frontend chỉ còn nội dung cảnh báo và hai nút Hủy/Xóa vĩnh viễn; đã bỏ ô nhập tên và state liên quan.
- Đã bỏ `confirm_name` khỏi frontend service, Form Request, controller và action; endpoint DELETE không còn request body.
- Đây là thay đổi contract có chủ đích đã được người dùng duyệt; endpoint vẫn yêu cầu Bearer token admin, role admin và thao tác xóa vẫn chạy trong transaction.
- Đã đồng bộ tài liệu API và kế hoạch admin verification; thêm regression test xác nhận admin xóa không cần body và request chưa đăng nhập vẫn bị 401.
- Kiểm chứng đạt trong Docker: focused test 2 passed/6 assertions; backend full test 170 passed/706 assertions; frontend lint 0 lỗi; frontend build thành công; `git diff --check` đạt.
- QA modal Chromium 375px/768px/1440px chưa thực hiện được do token admin QA cũ đã hết hiệu lực và route chuyển về `/admin/login`; không tự tạo/sử dụng credential thật để né auth. Không tạo artifact QA mới.

## Quy ước lưu kế hoạch — đã cập nhật

- Đã xóa bốn file plan riêng theo từng task trong [`plans/`](./), chỉ giữ [`plans/project-progress.md`](project-progress.md:1).
- Đã bổ sung quy tắc vào [`AGENTS.md`](../AGENTS.md:118): kế hoạch vẫn phải trình bày và chờ duyệt trong trao đổi, nhưng không tạo/lưu thêm file plan riêng; tiến độ dài hạn chỉ cập nhật tại file này và tài liệu chính thức thuộc [`docs/`](../docs/).
- Kiểm chứng: [`plans/`](./) chỉ còn file tiến độ, không còn tham chiếu Markdown đến bốn plan đã xóa và `git diff --check` đạt.
- Không ảnh hưởng backend, frontend, API, database, Docker hoặc runtime; không cần chạy test/lint/build ứng dụng.

## Task Admin CRUD Places + Sub-admin — đã hoàn thành

- Phạm vi: admin list/create/update/soft-delete places; cấp Sub-admin thủ công với activation email token 24h; activation đặt password và verify email; User xin làm Sub-admin cho place hiện hữu; Sub-admin dùng chung `/api/auth/login`; xóa Place đổi hard-delete → soft-delete.
- Khu vực ảnh hưởng: backend routes/controllers/requests/actions/repositories/resources/mail/migration/tests; frontend routes/pages/services/CSS; docs API và ERD.
- Thay đổi contract đã duyệt: DELETE Place chuyển hard-delete → soft-delete; migration mở rộng `manager_applications`; nới role `/auth/login` và `/auth/me` cho `sub_admin`.
- Đã cập nhật frontend verification để không còn phụ thuộc `next_unverified_id` trong response update/delete; màn hình tự chọn ID tiếp theo từ queue hiện tại.
- Migration `2026_08_12_000001` đã ở trạng thái `Ran` trong Docker Compose, batch 7; không chạy lại hoặc reset dữ liệu.
- Đã rà soát CSS admin trong `hnaj-fe/src/App.css`: không phát hiện conflict thực tế cần sửa; các selector lặp lại nằm trong cascade/pseudo-state/media query có chủ đích.
- Kiểm chứng trong Docker Compose: backend `185 passed (749 assertions)`; frontend lint đạt; frontend build đạt; `git diff --check` đạt; không còn tham chiếu frontend tới `next_unverified_id`.
- Chưa thực hiện browser QA tại 375px, 768px và 1440px trong vòng này; đây vẫn là giới hạn còn lại đối với kiểm tra giao diện thật.

## Task Visit Tracking + lịch sử "Đi tới đó" — đã hoàn thành

- Phạm vi đã duyệt: `POST /api/visits` public optional-auth (idempotent, `visit_date` theo `Asia/Ho_Chi_Minh`), `GET /api/visits` lịch sử auth `user,sub_admin` unique place; ghi visit từ CTA Đi tới đó ở Home/Detail/Bookmarks/Search; trang `/history` dưới `RequireAuth`.
- Không migration (bảng `visit_events`/`anonymous_visit_events` đã tồn tại), không dependency, không đổi Docker, không breaking contract (endpoint mới).
- Backend: `VisitErrorCode`, `VisitException` + handler, `VisitRepository`, `RecordVisit`, `ListVisitHistory`, `StoreVisitRequest`, `VisitResource`, `VisitHistoryResource`, `VisitStoreController`, `VisitIndexController`, route mới; guest dùng hash `SHA-256(X-Anonymous-Id)` — không lưu IP/plaintext.
- Frontend: `visitService`, `anonymousId` (localStorage `hnaj.anonymous_id`), `useGoThere` (fire-and-forget, mở Maps trước), gắn CTA ở 4 nơi (Home `discovery`, Detail `detail`, Bookmarks `bookmarks`, Search `search`), `HistoryPage` (grid/list, pagination, bookmark toggle, empty/error/loading), route `/history`.
- Kiểm chứng trong Docker Compose: backend `VisitTest` 15 passed/50 assertions; backend full test `239 passed (953 assertions)`; frontend `npm run lint` 0 lỗi; frontend `npm run build` (tsc + vite) thành công; `git diff --check` đạt.
- Sau review, đã fix: `is_bookmarked` trong history được gán từ `Bookmark` (tránh false), source `history` được thêm vào validation/docs/frontend.
- Chưa kiểm chứng: browser QA thật tại 375/768/1440 (môi trường chưa cung cấp browser tool); không tuyên bố UI đạt bằng mắt.

## Blockers và rủi ro

- Không còn blocker runtime cho migration/task verification; migration đã được xác nhận trong Docker Compose.
- Browser QA cho thay đổi UI chỉ được tuyên bố đạt nếu có thể chạy đúng route ở 375px, 768px và 1440px; vòng này chưa thực hiện browser QA.

### Task admin Place verification — kết quả

- Đã thêm migration `is_verified` mặc định `false`; migration đã chạy trong Docker.
- Public discovery/search lọc `is_verified=true`; factory test mặc định verified để giữ contract test hiện tại, có state `unverified()` cho test queue.
- Đã thêm admin queue/detail/update/hard-delete, frontend route `/admin/places/verification`, form toàn bộ field, URL images, thumbnail, auto-verify/auto-next.
- Kiểm chứng đạt: frontend lint, frontend build, backend route list, PHP syntax, backend full test `161 passed (672 assertions)`, `git diff --check`.
- Chưa kiểm chứng: browser screenshot/interaction tại 375/768/1440 vì môi trường hiện tại không cung cấp browser tool; chưa có regression test chuyên biệt cho 4 endpoint mới.
- Rủi ro cần xử lý tiếp: bổ sung feature tests admin; kiểm tra thực tế nested comment hard-delete và optimistic locking nếu nhiều admin cùng duyệt.


1. Realtime notification chưa có stack/transport trong dependency hoặc Compose; không tự chọn WebSocket/SSE/provider.
2. Hard-delete cascade có nguy cơ mất dữ liệu; mọi migration/destructive test cần phê duyệt cụ thể.
3. Upload storage chưa có contract runtime/credential; không đọc secret, không tự chọn storage.
4. Các tài liệu hiện còn mâu thuẫn với quyết định QA mới; cần cập nhật trước implementation.
5. Browser QA chỉ hoàn tất khi có thể xác thực admin trong môi trường chạy thực tế.

## Lịch sử cập nhật

| 2026-08-18 | Place detail page end-to-end: backend `GET /api/places/{place}` (PlaceShowController/ShowPlace/findPublicDetail/PlaceDetailResource/PlaceImageResource), feature test 8 passed; docs `api-place-detail.md`; FE `placeService` + rewrite `PlaceDetailsPage` (fetch theo id, gallery, hours open/closed, contact, share, review placeholder, sticky CTA mobile); CSS token-based. Kiểm chứng Docker: PlaceDetail+Search+Bookmark 39 passed, frontend lint/build đạt, smoke API 200/404. Browser QA 375/768/1440 đã chạy (xem dòng dưới). Visit tracking tách task. | Kế hoạch đã duyệt + repository |
| 2026-08-18 | Browser QA place detail (375/768/1440): chạy bằng Playwright/Chromium headless trên `/places/29` và `/places/999999`. Kết quả: không horizontal overflow ở 3 viewport (`scrollWidth = clientWidth`); sticky CTA mobile chỉ hiện ở 375 (được ẩn ở ≥768 theo CSS); trạng thái not-found render đúng ở 375/1440; focus outline 2px hiển thị trên nút bookmark. Phát hiện và đã sửa: hero image khi URL ảnh lỗi (HTTP 400/BLOCKED_BY_ORB) hiện icon broken, thiếu `onError` fallback như PlaceCard — đã thêm state `mediaFailed` + `onError` hiển thị letter placeholder theo CSS `.place-details__media span` sẵn có, reset khi tải place mới và khi chuyển thumbnail. Frontend lint 0 lỗi, build thành công, `git diff --check` đạt; đã chụp lại 3 viewport xác nhận fallback hiển thị placeholder. Toàn bộ ảnh/script QA tạm đã xóa. | Phản hồi người dùng + browser QA |
| 2026-08-17 | Redesign `/bookmarks`: grid/list toggle, tách `PlaceCard` (grid, tái dùng discovery) và `PlaceListCard` (list ngang), CSS shell/grid/list/toggle/pagination, image onError fallback, localStorage persist view mode (`hnaj.bookmarks.view`). Frontend lint 0 lỗi, build thành công; backend không thay đổi. Browser QA 3 viewport bị chặn do môi trường không có browser tool. | Repository |
| 2026-08-18 | Xác nhận Bookmark API hoàn chỉnh: backend routes/controllers/actions/repositories/resources/tests (15 test cases), frontend service/page/integration, docs contract. Trạng thái chuyển từ `planned` → `done`. Visit tracking chưa triển khai. | Kiểm tra hệ thống |
| 2026-08-14 | Sửa findings review Profile: bỏ import thừa, bảo vệ `avatar_url` read-only bằng regression assertion, đồng bộ input `fullName` theo auth state, sửa heading API auth trùng. Focused test 7 passed/27 assertions, full backend test đạt, frontend lint/build đạt, `git diff --check` đạt. Hai file Discovery ngoài scope được giữ nguyên theo phê duyệt; browser QA Profile chưa thực hiện vì môi trường không cung cấp browser tool. | Phản hồi người dùng + repository |
| 2026-08-14 | Hoàn tất rà soát task Admin CRUD Places + Sub-admin: bỏ frontend dependency vào `next_unverified_id`, xác nhận CSS không có conflict thực tế, migration đã `Ran` trong Docker Compose, backend 185/749, frontend lint/build và diff check đạt. Browser QA ba viewport chưa thực hiện trong vòng này. | Phản hồi người dùng + repository |
| 2026-08-11 | Đơn giản hóa hard-delete Place: popup chỉ hỏi xác nhận, bỏ ô nhập tên và bỏ `confirm_name` khỏi API. Thêm regression test; backend full 170/706, frontend lint/build và diff check đạt. Browser QA 3 viewport bị chặn do token QA hết hiệu lực. | Phản hồi người dùng + repository |
| 2026-08-11 | Điều chỉnh admin Place verification: preview URL ảnh, bắt buộc giữ thumbnail, tự chọn thumbnail thay thế khi xóa, thêm liên kết mở Google Maps tab mới không ghi visit. Frontend lint/build và diff check đạt; browser QA 3 viewport bị chặn do token QA hết hiệu lực và route chuyển về admin login; artifact tạm đã xóa. | Phản hồi người dùng + repository |
| 2026-08-11 | Sửa lỗi admin opening hours 422: đồng bộ quy ước `day_of_week` thành `2..8`, frontend hiển thị `T2..T7,CN`, thêm regression test. Backend full test 168/700, frontend lint/build đạt; PATCH thật qua Chromium trả HTTP 200 với đủ ngày `2..8`; cleanup QA hoàn tất. | Phản hồi người dùng + browser/API QA |
| 2026-08-11 | Hoàn tất API/UI tạo tag trong admin verification; backend full test 166 passed/692 assertions, frontend lint/build đạt. Chromium headless QA tại 375/768/1440 xác nhận selected chip màu cam, nút thêm tag và responsive không overflow; đã xóa toàn bộ artifact/token QA tạm. | Phản hồi người dùng + browser QA |
| 2026-08-11 | Triển khai MVP admin Place verification: migration/cờ verified, public filtering, admin API queue/update/hard-delete, frontend slideshow form, URL image management, docs. Migration Docker đạt; backend 161 tests, frontend lint/build, route/PHP syntax và diff check đạt. Browser QA và regression test chuyên biệt còn thiếu. | Trao đổi người dùng + repository |

| Ngày | Thay đổi | Nguồn |
|---|---|---|
| 2026-08-11 | Đã sửa regression responsive trong recommendation modal: tại mobile, cả ba nút `Xem chi tiết`, `Đi tới đó`, `Roll lại` chuyển thành cột nhưng không còn bị kéo cao bởi `flex-basis` desktop. Lint/build đạt; Chromium đã mở modal và chụp kiểm tra tại 375/768/1440, cả ba nút hiển thị đúng chiều cao và đủ chiều rộng; ảnh tạm đã xóa. | Phản hồi người dùng + browser QA |
| 2026-08-11 | Hoàn tất Taxonomy API: action/repository/controller/resources/route/test, tài liệu [`docs/api-meta.md`](../docs/api-meta.md:1), hook và frontend filter integration; đã quote `APP_NAME` trong `.env` để sửa lỗi dotenv. Feature test `DiscoveryMetadataTest` đạt 2 tests/15 assertions; frontend lint/build đạt; endpoint browser/API trả HTTP 200. Đã chụp và kiểm tra UI tại 375/768/1440; ảnh tạm đã được xóa sau QA. | Repository |
| 2026-08-11 | Bắt đầu triển khai Taxonomy API đã duyệt: endpoint gộp metadata discovery, bỏ ID hard-code frontend, thêm test/tài liệu và QA UI ba viewport. Không migration/dependency/breaking change. | Trao đổi người dùng + repository |
| 2026-08-11 | QA chốt profile, password, user image upload, report/anti-spam, request, notification realtime, admin CRUD/delete, soft/hard-delete, money format. | Trao đổi người dùng |
| 2026-08-11 | Khảo sát route, model, migration, Compose và manifest hiện có. | Repository |
| 2026-08-11 | Chuẩn hóa hiển thị tiền: `formatVnd` thêm hậu tố ` VNĐ`; bỏ hậu tố trùng ở PriceRangeSlider/PlaceCard/PlaceDetailsPage; lint+build đạt trong Docker. | Task money format |
| 2026-08-11 | Chưa kiểm tra UI bằng browser tại 375/768/1440 vì môi trường không có browser/screenshot; cần kiểm tra thủ công. | Task money format |

## Cách cập nhật bắt buộc

Mỗi agent phải cập nhật file này khi:

1. Bắt đầu task: ghi phạm vi, trạng thái và blocker.
2. Chốt quyết định hoặc phát hiện mâu thuẫn: cập nhật mục tương ứng.
3. Thay đổi file: cập nhật khu vực/file và trạng thái.
4. Chạy kiểm chứng: ghi lệnh, kết quả, giới hạn.
5. Kết thúc task: cập nhật trạng thái cuối và rủi ro còn lại.
