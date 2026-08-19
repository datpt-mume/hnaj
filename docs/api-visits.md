# API ghi nhận và lịch sử "Đi tới đó" — HNAJ

- **Trạng thái:** Đã triển khai
- **Cập nhật:** 2026-08-19
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 5.4, 5.5; [`docs/erd.md`](erd.md) mục 3.6 `visit_events` / `anonymous_visit_events`
- **Auth:** [`docs/api-auth.md`](api-auth.md) — `POST` public với bearer token tùy chọn; `GET` bắt buộc token User/Sub-admin

## 1. Tổng quan

Một lượt "Đi tới đó" chỉ được tạo khi người dùng bấm nút điều hướng. Roll lại hoặc
chọn lại **không** tạo visit. Có hai luồng:

- **User đăng nhập** (`user`/`sub_admin`) → ghi vào `visit_events`, dùng cho lịch sử
  cá nhân và thống kê độ hot.
- **Khách chưa đăng nhập** → ghi vào `anonymous_visit_events` bằng hash của định
  danh tạm thời; chỉ dùng cho thống kê hot, không tạo lịch sử cá nhân.

Quy tắc:

- Deduplicate theo cặp `user_id + place_id + visit_date` (với khách là
  `anonymous_key_hash + place_id + visit_date`). Cùng ngày bấm lại cùng place chỉ
  là một bản ghi; sang ngày mới được tạo bản ghi mới.
- `visit_date` tính theo múi giờ **Asia/Ho_Chi_Minh** để "cùng ngày" khớp với lịch
  Việt Nam; `visited_at` lưu theo UTC.
- Place phải `active`, `is_verified = true` và chưa soft-deleted. Place còn lại
  trả HTTP 404, không lộ sự tồn tại.
- Lịch sử chỉ chứa place `active` và chưa soft-deleted; visit của place bị ẩn/xóa
  mềm không bị xóa và sẽ hiện lại khi place được khôi phục.

### Định danh khách

Vì frontend (`FRONTEND_PORT`) và backend (`BACKEND_PORT`) khác origin, định danh
khách dùng `localStorage` + header thay vì cookie:

- Frontend tạo UUID, lưu tại key `hnaj.anonymous_id`.
- Gửi qua header `X-Anonymous-Id` cho `POST /api/visits`.
- Backend chỉ lưu `hash('sha256', <id>)` vào `anonymous_key_hash`; không lưu IP
  thô, không log plaintext. Xóa/đổi localStorage được xem như khách mới.

## 2. `POST /api/visits`

Ghi nhận một lượt "Đi tới đó". Idempotent: trả HTTP 201 lần đầu trong ngày,
HTTP 200 nếu đã có bản ghi cùng ngày.

### 2.1. Request

```json
{
  "place_id": 42,
  "source": "detail"
}
```

| Field | Type | Bắt buộc | Ràng buộc |
|---|---|---|---|
| `place_id` | int | Có | integer, `exists:places,id` |
| `source` | string | Không | `discovery` \| `detail` \| `search` \| `bookmarks` \| `history`; mặc định `detail` |

Header:

| Header | Bắt buộc | Mô tả |
|---|---|---|
| `Authorization: Bearer <token>` | Không | User/Sub-admin hợp lệ → ghi `visit_events` |
| `X-Anonymous-Id` | Chỉ khi không có token User/Sub-admin | UUID định danh khách; ghi `anonymous_visit_events` |

### 2.2. Response — HTTP 201 (lần đầu trong ngày)

```json
{
  "success": true,
  "message": "Đã ghi nhận lượt đi tới.",
  "data": {
    "id": 10,
    "place_id": 42,
    "visit_date": "2026-08-19",
    "visited_at": "2026-08-19T01:15:00+00:00",
    "source": "detail",
    "created": true,
    "anonymous": false
  }
}
```

### 2.3. Response — HTTP 200 (đã có bản ghi cùng ngày)

Cùng shape, `created: false`, `id`/`visited_at` trả về bản ghi hiện có.

Guest response không có `id` (tránh lộ primary key nội bộ):

```json
{
  "success": true,
  "message": "Đã ghi nhận lượt đi tới.",
  "data": {
    "place_id": 42,
    "visit_date": "2026-08-19",
    "visited_at": "2026-08-19T01:15:00+00:00",
    "source": "detail",
    "created": true,
    "anonymous": true
  }
}
```

### 2.4. Lỗi

- HTTP `404` — `code: VISIT_PLACE_NOT_AVAILABLE` khi place `hidden`/unverified/soft-deleted hoặc không tồn tại.
- HTTP `422` — `code: VISIT_ANONYMOUS_KEY_REQUIRED` khi guest thiếu/sai `X-Anonymous-Id`.
- HTTP `422` — `code: VALIDATION_ERROR` khi `place_id`/`source` sai định dạng.

## 3. `GET /api/visits`

Trả lịch sử địa điểm đã "Đi tới đó" của User hiện tại, **unique theo place**,
sắp xếp theo lần đi gần nhất trước.

### 3.1. Request

| Param | Type | Bắt buộc | Mô tả | Ràng buộc |
|---|---|---|---|---|
| `page` | int | Không | Trang kết quả | ≥ 1, mặc định 1 |
| `per_page` | int | Không | Số kết quả mỗi trang | 1–50, mặc định 10 |

Bắt buộc `Authorization: Bearer <token>` và role `user` hoặc `sub_admin`.

### 3.2. Response — HTTP 200

`data[]` là shape [`PlaceResource`](../hnaj-be/app/Http/Resources/PlaceResource.php)
kèm `last_visited_at` và `last_source`.

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
      "tags": [],
      "min_price": 40000,
      "max_price": 80000,
      "rating": 4.8,
      "thumbnail": null,
      "latitude": 21.0333330,
      "longitude": 105.8500000,
      "google_maps_url": "https://maps.google.com/?q=...",
      "opening_hours": [],
      "is_bookmarked": true,
      "last_visited_at": "2026-08-19T01:15:00+00:00",
      "last_source": "detail"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

### 3.3. Lỗi

- HTTP `401` — `code: UNAUTHENTICATED` khi guest truy cập.
- HTTP `403` — `code: FORBIDDEN_ROLE` khi role không phải `user`/`sub_admin`.

## 4. Mã lỗi ổn định

| Code | HTTP | Ý nghĩa |
|---|---|---|
| `VISIT_PLACE_NOT_AVAILABLE` | 404 | Place không active/unverified/soft-deleted hoặc không tồn tại |
| `VISIT_ANONYMOUS_KEY_REQUIRED` | 422 | Guest thiếu định danh tạm thời |
| `UNAUTHENTICATED` | 401 | Thiếu/sai bearer token |
| `FORBIDDEN_ROLE` | 403 | Role không đủ quyền |
| `VALIDATION_ERROR` | 422 | Dữ liệu gửi lên không hợp lệ |