# API khám phá / random địa điểm — HNAJ

- **Trạng thái:** Đã triển khai (backlog discovery/random, chưa có phần còn lại của luồng khám phá)
- **Cập nhật:** 2026-08-06
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 5.1

## 1. Tổng quan

Endpoint cho phép khách chưa đăng nhập lẫn User đã đăng nhập nhận một đề xuất địa điểm
ngẫu nhiên phù hợp với bộ lọc. Endpoint **public**, không yêu cầu token; được throttle
để chống spam.

Danh sách place bị bỏ qua khi roll (`excluded_place_ids`) **không được lưu ở server** — theo
PRD, danh sách này là trạng thái tạm thời của lượt khám phá phía frontend và được gửi kèm
mỗi lần gọi.

## 2. `POST /api/discovery/random`

Chọn ngẫu nhiên **một** place `active` khớp bộ lọc.

### 2.1. Request body

Tất cả field đều tùy chọn. Gửi body rỗng `{}` sẽ random trong toàn bộ place active.

| Field | Type | Mô tả | Ràng buộc |
|---|---|---|---|
| `category_id` | int | Place thuộc đúng một category | `exists:categories` |
| `district_id` | int | Quận/huyện/thị xã thuộc Hà Nội | `exists:districts` |
| `min_price` | int | Cận dưới khoảng giá VND | ≥ 0 |
| `max_price` | int | Cận trên khoảng giá VND | ≥ 0 |
| `tag_ids` | int[] | Lọc tags (**khớp ALL** — place phải có đủ mọi tag) | tối đa 20, `exists:tags` |
| `open_now` | bool | Lọc place đang mở tại thời điểm gọi | mặc định `true` |
| `lat` | float | Vĩ độ người dùng (đi cặp với `lng`) | −90..90 |
| `lng` | float | Kinh độ người dùng (đi cặp với `lat`) | −180..180 |
| `radius_km` | float | Bán kính tìm kiếm | 0.5–50; mặc định 5 nếu có tọa độ mà không gửi |
| `excluded_place_ids` | int[] | Place đã roll trong lượt hiện tại | tối đa 100 |

Ví dụ:

```json
{
  "category_id": 1,
  "district_id": 5,
  "min_price": 20000,
  "max_price": 150000,
  "tag_ids": [3, 7],
  "open_now": true,
  "lat": 21.0285,
  "lng": 105.8542,
  "radius_km": 5,
  "excluded_place_ids": [12, 34]
}
```

### 2.2. Quy tắc nghiệp vụ

- Chỉ trả place có `status = active`; place `hidden` hoặc soft-deleted không xuất hiện.
- `category_id` và `district_id` kết hợp theo **AND**.
- Khoảng giá: place có `min_price`/`max_price` null (chưa có thông tin giá) vẫn được xét
  tùy điều kiện; nếu cả hai cận đều được gửi, place chỉ khớp khi khoảng giá của place giao
  với khoảng lọc.
- `tag_ids`: place phải có **đủ tất cả** tag đã chọn (ALL).
- Khoảng cách: áp dụng **đồng thời** với `district_id` (AND). Nếu client gửi tọa độ mà
  không gửi `radius_km`, hệ thống dùng mặc định 5km.
- `open_now`:
  - Place **chưa có dữ liệu giờ** (không có record `place_opening_hours`) được giữ lại.
  - Place có giờ nhưng không khớp thời điểm hiện tại bị **loại** (kể cả `closed` hôm đó).
  - Ngày không được khai báo được hiểu là mở (unknown).
- `excluded_place_ids`: nếu danh sách này loại hết ứng viên, server **bỏ qua** excluded và
  random lại từ đầu (lượt khám phá không bao giờ rỗng chỉ vì roll).

### 2.3. Cơ chế chọn ngẫu nhiên

- Lọc có index (category, district, khoảng giá, tags ALL, excluded) chạy trong SQL.
- Khoảng cách và giờ mở cửa được tính bằng PHP trên tối đa 500 id ứng viên đã nạp.
- Chọn ngẫu nhiên 1 id bằng `shuffle` PHP (QA-2).

### 2.4. Response

#### Thành công (có kết quả) — HTTP 200

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {
    "id": 42,
    "name": "Cà phê Nhà Thờ",
    "address_text": "Số 1 Nhà Thờ, Hoàn Kiếm",
    "district": { "id": 7, "name": "Hoàn Kiếm" },
    "category": { "id": 2, "name": "Cà phê & đồ uống", "slug": "ca-phe-do-uong" },
    "tags": [
      { "id": 1, "name": "Chill", "slug": "chill" }
    ],
    "min_price": 30000,
    "max_price": 80000,
    "thumbnail": {
      "image_url": "https://.../photo.jpg",
      "alt_text": "Cà phê Nhà Thờ"
    },
    "latitude": 21.0285110,
    "longitude": 105.8498140,
    "google_maps_url": "https://maps.google.com/?q=...",
    "opening_hours": [
      { "day_of_week": 2, "schedule_type": "regular", "opens_at": "07:00", "closes_at": "22:00" }
    ]
  }
}
```

- `thumbnail` là `null` nếu place chưa có ảnh.
- `min_price`/`max_price` là `null` nếu chưa có thông tin giá.
- FE tự dựng link Google Maps directions từ `google_maps_url` hoặc tọa độ (không có
  `distance_km`/`directions_url` phía server).

#### Thành công (không còn kết quả) — HTTP 200

```json
{
  "success": true,
  "message": "Không tìm thấy địa điểm phù hợp.",
  "data": null
}
```

#### Lỗi validation — HTTP 422

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "lat": ["The lat field is required when lng is present."]
  },
  "code": "VALIDATION_ERROR"
}
```

## 3. Ví dụ luồng roll

1. FE gọi `POST /api/discovery/random` với `{}` → nhận place A.
2. User bấm roll → FE gọi lại với `{ "excluded_place_ids": [42] }` → nhận place B.
3. User bấm roll lần nữa → FE gọi với `{ "excluded_place_ids": [42, 50] }`.
4. Khi đã loại hết place phù hợp, server bỏ qua excluded và trả một place bất kỳ trong
   tập khớp bộ lọc (QA-6).
