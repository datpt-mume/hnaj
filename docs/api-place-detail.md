# API chi tiết địa điểm — HNAJ

- **Trạng thái:** Đã triển khai
- **Cập nhật:** 2026-08-18
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 5.1, 10 (màn hình Chi tiết place)
- **Auth:** Public; bearer token tùy chọn để trả `is_bookmarked` (User/Sub-admin)

## 1. Tổng quan

Endpoint trả **một** place công khai theo id cho trang `/places/:placeId`.

Quy tắc:

- Chỉ trả place `status = active`, `is_verified = true`, chưa soft-deleted.
- Place `hidden`, chưa xác minh, soft-deleted hoặc không tồn tại đều trả **HTTP 404** với `code = NOT_FOUND` (không dùng 403 để tránh lộ sự tồn tại của place ẩn).
- Guest không nhận field `is_bookmarked`.
- User đã đăng nhập (bearer token Sanctum hợp lệ) nhận `is_bookmarked: true|false`.
- Gallery `images` chỉ gồm ảnh `is_visible = true`; ảnh admin đã ẩn không lộ.
- `rating` là aggregate denormalize từ review HNAJ; place chưa có review giữ default `5.0` ở DB — UI có thể ẩn rating khi chưa có review thật (quyết định frontend).

## 2. `GET /api/places/{place}`

### 2.1. Path

| Param | Type | Mô tả |
|---|---|---|
| `place` | int | ID place |

### 2.2. Headers

| Header | Bắt buộc | Mô tả |
|---|---|---|
| `Authorization: Bearer <token>` | Không | Nếu có và hợp lệ, response có `is_bookmarked` |

### 2.3. Response — HTTP 200

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {
    "id": 42,
    "name": "Phở Gia Truyền",
    "address_text": "49 Bát Đàn, Hoàn Kiếm",
    "description": "Phở bò truyền thống.",
    "phone": "0123456789",
    "website_url": "https://example.com",
    "is_verified": true,
    "district": { "id": 7, "name": "Hoàn Kiếm" },
    "category": { "id": 1, "name": "Ăn uống", "slug": "an-uong" },
    "tags": [
      { "id": 190, "name": "Đồ ăn đường phố", "slug": "do-an-duong-pho" }
    ],
    "min_price": 40000,
    "max_price": 80000,
    "rating": 4.8,
    "thumbnail": {
      "image_url": "https://cdn.example.com/photo.jpg",
      "alt_text": "Phở Gia Truyền"
    },
    "images": [
      {
        "image_url": "https://cdn.example.com/photo.jpg",
        "alt_text": "Phở Gia Truyền"
      }
    ],
    "latitude": 21.0333330,
    "longitude": 105.8500000,
    "google_maps_url": "https://maps.google.com/?q=...",
    "opening_hours": [
      {
        "day_of_week": 2,
        "schedule_type": "regular",
        "opens_at": "08:00",
        "closes_at": "21:00"
      }
    ],
    "is_bookmarked": true
  }
}
```

Ghi chú field:

| Field | Ghi chú |
|---|---|
| `description`, `phone`, `website_url` | Nullable |
| `images` | Mảng; có thể rỗng |
| `opening_hours` | `day_of_week` theo quy ước `2=T2 … 7=T7, 8=CN`; `schedule_type`: `regular` \| `all_day` \| `closed` |
| `is_bookmarked` | Chỉ có khi request mang bearer token hợp lệ |
| `is_verified` | Place public luôn `true` |

Payload mở rộng từ [`PlaceResource`](../hnaj-be/app/Http/Resources/PlaceResource.php) qua [`PlaceDetailResource`](../hnaj-be/app/Http/Resources/PlaceDetailResource.php).

### 2.4. Response — HTTP 404

```json
{
  "success": false,
  "message": "Không tìm thấy địa điểm hoặc địa điểm không công khai.",
  "code": "NOT_FOUND"
}
```

## 3. Throttle

`60` request / phút / IP (middleware `throttle:60,1`).

## 4. Liên quan

- Discovery card: [`docs/api-discovery.md`](api-discovery.md)
- Search: [`docs/api-search.md`](api-search.md)
- Bookmark: [`docs/api-bookmarks.md`](api-bookmarks.md)
