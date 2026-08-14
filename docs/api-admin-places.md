# Admin Place verification API

Các endpoint yêu cầu Bearer token admin, middleware `auth:sanctum` và role `admin`. Response dùng envelope chung.

## Hàng đợi

```http
GET /api/admin/places/verification-queue?per_page=10&page=1
```

Chỉ trả Place có `is_verified=false`, sắp xếp `id` tăng dần.

## Chi tiết

```http
GET /api/admin/places/{place}
```

Trả toàn bộ thông tin Place, taxonomy, tags, giờ mở cửa và ảnh.

## Cập nhật và xác minh

```http
PATCH /api/admin/places/{place}
Content-Type: application/json
```

Body gồm thông tin Place, `tag_ids`, `opening_hours`, `images`, `thumbnail_image_id` và `deleted_image_ids`. Cập nhật thành công tự đặt `is_verified=true`.

`meta.next_unverified_id` chứa Place tiếp theo nếu còn.

## Tạo tag trong luồng verification

```http
POST /api/admin/tags
Content-Type: application/json
Authorization: Bearer {admin_token}
```

Tạo tag active để admin có thể gán ngay cho Place đang duyệt. Endpoint chỉ dành cho admin.

Request:

```json
{ "name": "Ăn khuya" }
```

Response `201`:

```json
{
  "success": true,
  "message": "Tag created successfully.",
  "data": { "id": 1, "name": "Ăn khuya", "slug": "an-khuya" }
}
```

Tên tag được trim, rút gọn khoảng trắng, bắt buộc unique trong các tag chưa bị soft-delete. Slug được sinh tự động và tránh trùng cả với tag đã soft-delete.

## Danh sách Place (admin)

```http
GET /api/admin/places?page=1&per_page=10&q=&status=&is_verified=&with_trashed=0
```

Trả tất cả Place (đã kiểm duyệt và chưa), hỗ trợ phân trang và filter `q`, `district_id`, `category_id`, `tag_id`, `status`, `is_verified`. Truyền `with_trashed=1` để kèm Place đã soft-delete.

## Tạo Place (admin)

```http
POST /api/admin/places
Content-Type: application/json
Authorization: Bearer {admin_token}
```

Body giống `PATCH /{place}` bên trên nhưng không có `deleted_image_ids`; `images` không cần `id`. Place tạo bởi admin mặc định `is_verified=true`, `created_by` = admin đang thực hiện. Response HTTP `201`.

## Xóa Place (soft-delete)

```http
DELETE /api/admin/places/{place}
Authorization: Bearer {admin_token}
```

Endpoint không yêu cầu request body. Giao diện admin phải hiển thị popup xác nhận trước khi gọi API. Thao tác **xóa mềm**: đặt `deleted_at`, Place ẩn khỏi mọi truy vấn người dùng, dữ liệu và quan hệ giữ nguyên, có thể khôi phục. Không xóa dữ liệu liên quan.

## Quản lý Sub-admin của Place

```http
GET  /api/admin/places/{place}/managers
POST /api/admin/places/{place}/managers
DELETE /api/admin/places/{place}/managers/{user}
POST /api/admin/places/{place}/managers/{user}/resend
```

`POST` tạo user mới (role `sub_admin`) gắn với place qua `place_managers` và gửi email kích hoạt. Body:

```json
{
  "username": "manager.one",
  "email": "manager.one@example.com",
  "password": "Password123",
  "full_name": "Manager One"
}
```

- `username`/`email` bắt buộc unique trong `users`.
- Account chưa thể đăng nhập cho tới khi click link activation trong email (đặt password mới + verify email).
- `DELETE` thu hồi quyền: đặt `revoked_at` (giữ lịch sử, không xóa bản ghi).
- `resend` gửi lại email kích hoạt; chỉ áp dụng cho assignment chưa bị thu hồi.

## Đơn User xin làm Sub-admin

### User gửi đơn

```http
POST /api/manager-applications
Content-Type: application/json
Authorization: Bearer {user_token}
```

```json
{ "place_id": 12 }
```

Tạo đơn `pending` gắn với `place_id` và `user_id` của người xin. Response HTTP `201`.

### Admin duyệt/từ chối

```http
GET  /api/admin/manager-applications?status=pending
POST /api/admin/manager-applications/{id}/approve
POST /api/admin/manager-applications/{id}/reject
Content-Type: application/json
```

`approve` (transaction): giữ role `user`, gán thêm role `sub_admin` và tạo/thu hồi-reset `place_managers` cho cặp `place_id + user_id`. `reject` yêu cầu body `{ "reason": "..." }`; trả `422 / REASON_REQUIRED` nếu thiếu.

## Lỗi

- `401`: thiếu hoặc token không hợp lệ.
- `403`: không có role admin.
- `404` / `NOT_FOUND`: Place không tồn tại.
- `422` / `VALIDATION_ERROR`: payload không hợp lệ đối với endpoint có request body.
