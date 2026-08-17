# API bookmark địa điểm yêu thích — HNAJ

- **Trạng thái:** Đã triển khai
- **Cập nhật:** 2026-08-17
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 5.3, [`docs/erd.md`](erd.md) mục 3.4
- **Auth:** [`docs/api-auth.md`](api-auth.md) — bắt buộc bearer token của User hoặc Sub-admin

## 1. Tổng quan

Bookmark là danh sách địa điểm yêu thích **riêng tư** của User đã đăng nhập
(role `user` hoặc `sub_admin`). Mỗi User chỉ có tối đa một bookmark cho một place.

Quy tắc nghiệp vụ:

- Chỉ User đã đăng nhập mới bookmark được; mọi endpoint yêu cầu
  `Authorization: Bearer <token>` và role `user,sub_admin`.
- Bookmark chỉ hiển thị cho chính User đó — không bao giờ trả bookmark của user khác.
- Place `hidden` hoặc soft-deleted **bị ẩn khỏi danh sách** nhưng bản ghi bookmark
  không bị xóa; khi place được khôi phục, bookmark hiển thị lại.
- Danh sách chỉ trả place có `status = active` và chưa soft-deleted, sắp xếp theo
  thời điểm bookmark mới nhất trước.

## 2. `GET /api/bookmarks`

Trả về danh sách place đã bookmark của User hiện tại, phân trang page-based.

### 2.1. Request

| Param | Type | Bắt buộc | Mô tả | Ràng buộc |
|---|---|---|---|---|
| `page` | int | Không | Trang kết quả | ≥ 1, mặc định 1 |
| `per_page` | int | Không | Số kết quả mỗi trang | 1–50, mặc định 10 |

### 2.2. Response — HTTP 200

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": [
    {
      "id": 42,
      "name": "Phở Gia Truyền",
      "address_text": "49 Bát Đàn, Hoàn Kiếm",
      "district": { "id": 7, "name": "Hoàn Kiếm" },
      "category": { "id": 1, "name": "Ăn uống", "slug": "an-uong" },
      "tags": [
        { "id": 190, "name": "Đồ ăn đường phố", "slug": "do-an-duong-pho" }
      ],
      "min_price": 40000,
      "max_price": 80000,
      "rating": 4.8,
      "thumbnail": {
        "image_url": "https://.../photo.jpg",
        "alt_text": "Phở Gia Truyền"
      },
      "latitude": 21.0333330,
      "longitude": 105.8500000,
      "google_maps_url": "https://maps.google.com/?q=...",
      "opening_hours": [],
      "is_bookmarked": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25
  }
}
```

Payload mỗi phần tử là [`PlaceResource`](../hnaj-be/app/Http/Resources/PlaceResource.php) — cùng
shape với kết quả discovery/search, kèm `is_bookmarked: true`.

## 3. `POST /api/bookmarks`

Lưu bookmark cho một place.

### 3.1. Request body

```json
{
  "place_id": 42
}
```

| Field | Type | Bắt buộc | Ràng buộc |
|---|---|---|---|
| `place_id` | int | Có | `exists:places,id` |

### 3.2. Response — HTTP 201

```json
{
  "success": true,
  "message": "Đã lưu địa điểm yêu thích.",
  "data": {
    "id": 15,
    "created_at": "2026-08-17T08:00:00+00:00",
    "place_id": 42,
    "is_bookmarked": true,
    "place": {
      "id": 42,
      "name": "Phở Gia Truyền",
      "...": "..."
    }
  }
}
```

### 3.3. Lỗi

- HTTP `409` — `code: BOOKMARK_ALREADY_EXISTS` khi place đã được User này bookmark.
- HTTP `404` — `code: BOOKMARK_PLACE_NOT_AVAILABLE` khi place `hidden`/soft-deleted
  hoặc không tồn tại.
- HTTP `422` — `code: VALIDATION_ERROR` khi `place_id` thiếu/sai định dạng/không tồn tại.

## 4. `DELETE /api/bookmarks/{place}`

Bỏ bookmark theo `place_id`.

### 4.1. Response — HTTP 200

```json
{
  "success": true,
  "message": "Đã bỏ lưu địa điểm.",
  "data": null
}
```

### 4.2. Lỗi

- HTTP `404` — `code: BOOKMARK_NOT_FOUND` khi User hiện tại không có bookmark cho
  place này (kể cả khi place thuộc user khác — không rò rỉ thông tin).

## 5. Mã lỗi ổn định

| Code | HTTP | Ý nghĩa |
|---|---|---|
| `BOOKMARK_ALREADY_EXISTS` | 409 | Đã bookmark place này |
| `BOOKMARK_PLACE_NOT_AVAILABLE` | 404 | Place không active hoặc không tồn tại |
| `BOOKMARK_NOT_FOUND` | 404 | Không có bookmark để xóa |
| `UNAUTHENTICATED` | 401 | Thiếu/sai bearer token |
| `FORBIDDEN_ROLE` | 403 | Role không đủ quyền |
| `VALIDATION_ERROR` | 422 | Dữ liệu gửi lên không hợp lệ |

## 6. `is_bookmarked` trên endpoint khác

[`PlaceResource`](../hnaj-be/app/Http/Resources/PlaceResource.php) trả thêm
`is_bookmarked: boolean` khi request mang bearer token hợp lệ (dùng cho discovery
`POST /api/discovery/random` và search `GET /api/places/search`). Với guest,
field này **không xuất hiện** trong response.
