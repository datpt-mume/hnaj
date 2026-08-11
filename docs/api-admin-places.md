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

## Hard-delete

```http
DELETE /api/admin/places/{place}
Authorization: Bearer {admin_token}
```

Endpoint không yêu cầu request body. Giao diện admin phải hiển thị popup xác nhận trước khi gọi API. Thao tác xóa vĩnh viễn Place cùng dữ liệu phụ thuộc trong transaction; không thể hoàn tác.

## Lỗi

- `401`: thiếu hoặc token không hợp lệ.
- `403`: không có role admin.
- `404` / `NOT_FOUND`: Place không tồn tại.
- `422` / `VALIDATION_ERROR`: payload không hợp lệ đối với endpoint có request body.
