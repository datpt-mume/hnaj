# Theo dõi tiến độ dự án HNAJ

- **Cập nhật:** 2026-08-11
- **Trạng thái tổng:** Đang chuẩn bị contract API MVP
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
| Auth Sub-admin | `planned` | Chưa có login endpoint riêng; cần quyết định dùng auth user chung hay endpoint riêng. |
| Discovery/search | `done` | Random, search, filter, pagination đang có implementation/test. |
| Giá hiển thị | `done` | API/DB giữ integer VND; UI thống nhất `vi-VN` + hậu tố `VNĐ` qua `formatVnd`. |
| Profile | `planned` | Chỉ sửa `full_name`; `username`, `email`, `avatar_url` read-only. |
| Password recovery/change | `planned` | User/Sub-admin: reset token một lần 24 giờ; đổi cần password hiện tại; đổi xong thu hồi token khác. Admin chỉ đổi nội bộ. |
| Bookmark/visit/history | `planned` | Nghiệp vụ đã chốt, route/API domain chưa có. |
| Review/comment | `planned` | User đăng nhập; review cần visit, một review/place; comment nhiều; reply theo quyền. |
| User content images | `planned` | Tối đa 5 ảnh/nội dung, 5 MB/ảnh, JPEG/PNG/WebP; hiển thị ngay; Admin ẩn/gỡ sau. |
| Report/moderation | `planned` | Report review/comment/ảnh; lý do cố định; một report mở/User/nội dung; Admin pending → dismissed/actioned. |
| Place request | `planned` | Người gửi xem/sửa/hủy khi pending; Admin duyệt/từ chối kèm lý do. |
| Manager application | `planned` | Duyệt place + role rồi mới tạo User/Sub-admin và setup password. |
| Promotion request | `planned` | Sub-admin tạo/xem/sửa/hủy khi pending; Admin duyệt/từ chối kèm lý do; chưa placement. |
| Notification center | `planned` | User/Sub-admin/Admin; request, promotion, moderation, account; pagination, realtime, mark-one/read-all, lưu đến người dùng xoá/đánh dấu đã đọc. |
| Email notifications | `planned` | Kết quả request gửi email; không lộ dữ liệu nhạy cảm. |
| Admin management | `planned` | User/role, place, taxonomy, request, moderation, reports, dashboard. |
| Soft-delete UX | `planned` | Place và nội dung liên quan hiển thị mờ nếu còn truy cập; không thêm/sửa/xóa. |
| Place hard-delete | `blocked` | Xoá place và toàn bộ bookmark/visit/review/comment/ảnh/history liên quan; cần transaction, FK/cascade và xác nhận destructive. |
| Taxonomy delete | `done` | Category/tag/district không xoá; chỉ update/status theo quyết định. |
| API contract | `in_progress` | Cần hoàn thiện URL, payload, auth, status, pagination, validation, resource. |
| Frontend integration | `planned` | Chờ contract API. |

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

### Cần thiết kế và triển khai MVP

- Profile: `GET/PATCH /api/auth/me`; chỉ `full_name` writable.
- Password: forgot request, reset, change; token one-time 24 giờ; thu hồi token khác sau change.
- Places: detail, hot, public status behavior.
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
- [`hnaj-be/routes/api.php`](../hnaj-be/routes/api.php:16) hiện mới có auth/discovery/search/test; domain API chưa triển khai.

## Blockers và rủi ro

1. Realtime notification chưa có stack/transport trong dependency hoặc Compose; không tự chọn WebSocket/SSE/provider.
2. Hard-delete cascade có nguy cơ mất dữ liệu; mọi migration/destructive test cần phê duyệt cụ thể.
3. Auth Sub-admin chưa có contract: dùng endpoint user chung hay endpoint riêng.
4. Upload storage chưa có contract runtime/credential; không đọc secret, không tự chọn storage.
5. Request manager approval cần transaction và account setup flow đồng bộ.
6. Các tài liệu hiện còn mâu thuẫn với quyết định QA mới; cần cập nhật trước implementation.

## Lịch sử cập nhật

| Ngày | Thay đổi | Nguồn |
|---|---|---|
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
