# API khám phá / đề xuất địa điểm — HNAJ

- **Trạng thái:** Đã triển khai (backlog discovery/random, chưa có phần còn lại của luồng khám phá)
- **Cập nhật:** 2026-08-07
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 5.1

## 1. Tổng quan

Endpoint cho phép khách chưa đăng nhập lẫn User đã đăng nhập nhận **một** đề xuất địa điểm
phù hợp với bộ lọc. Endpoint **public**, không yêu cầu token; được throttle để chống spam.

Endpoint **không random thuần**: sau khi lọc cứng theo bộ lọc, mọi ứng viên được chấm điểm
tổng hợp có trọng số và place điểm cao nhất được chọn (xem §2.3). Ứng viên đồng điểm được
chọn ngẫu nhiên.

**Auth optional:** nếu request mang bearer token hợp lệ, server đọc bookmark và lịch sử
"Đi tới đó" của user đó để cá nhân hóa xếp hạng. Không có token thì hai tiêu chí này bằng 0
và thứ tự chỉ phụ thuộc `excluded_place_ids`, khoảng cách và điểm đánh giá. Token không hợp
lệ **không** làm request thất bại — chỉ mất phần cá nhân hóa.

Danh sách place bị bỏ qua khi roll (`excluded_place_ids`) **không được lưu ở server** — theo
PRD, danh sách này là trạng thái tạm thời của lượt khám phá phía frontend và được gửi kèm
mỗi lần gọi.

## 2. `POST /api/discovery/random`

Chọn **một** place `active` khả dụng nhất khớp bộ lọc.

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
| `excluded_place_ids` | int[] | Place đã roll trong lượt hiện tại (hạ ưu tiên, không loại bỏ) | tối đa 100 |

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
- `excluded_place_ids`: **không** phải bộ lọc cứng. Place trong danh sách chỉ bị hạ ưu tiên
  xuống dưới mọi place khác, nên vẫn được trả về khi là ứng viên duy nhất (lượt khám phá
  không bao giờ rỗng chỉ vì roll).

### 2.3. Cơ chế xếp hạng và chọn kết quả

Sau khi lọc cứng, mỗi ứng viên nhận một điểm tổng hợp. Thứ tự ưu tiên giảm dần:

| # | Tiêu chí | Trọng số | Điểm thành phần |
|---|---|---|---|
| 1 | Không phải địa điểm vừa xuất hiện (không nằm trong `excluded_place_ids`) | 32 | 0 hoặc 1 |
| 2 | User đã lưu (bookmark) địa điểm | 16 | 0 hoặc 1 |
| 3 | User đã bấm "Đi tới đó" (có visit event) | 8 | 0 hoặc 1 |
| 4 | Địa điểm gần hơn | 4 | `1 − distance_km / radius_km`, kẹp về [0, 1] |
| 5 | Điểm đánh giá cao hơn | 2 | `rating / 5`, kẹp về [0, 1] |

Trọng số được chọn sao cho mỗi tiêu chí luôn thắng tổng của toàn bộ tiêu chí xếp sau nó
(32 > 16+8+4 = 28; 16 > 8+4 = 12; 8 > 4). Nhờ vậy thứ tự ưu tiên không bị đảo do tích lũy
điểm nhỏ, nhưng tiêu chí sau vẫn quyết định khi các tiêu chí trước bằng nhau.

Lưu ý:

- Không gửi `lat`/`lng` → tiêu chí độ gần bằng 0 cho mọi ứng viên (không thiên vị).
- Khách chưa đăng nhập → tiêu chí 2 và 3 bằng 0 cho mọi ứng viên.
- Ứng viên đồng điểm được chọn ngẫu nhiên, nên lượt khám phá không luôn trả cùng một place
  khi các tiêu chí không phân biệt được.

Thực thi:

- Lọc có index (category, district, khoảng giá, tags ALL) chạy trong SQL.
- Khoảng cách và giờ mở cửa được tính bằng PHP; ứng viên được hydrate theo lô 500 id để
  chặn bộ nhớ, nhưng **toàn bộ** ứng viên đều được chấm điểm (không dừng sớm).
- Bookmark và visit event được nạp bằng hai truy vấn giới hạn trong tập ứng viên.

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
    "rating": 4.6,
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
- `rating` là điểm đánh giá tổng hợp 0.0–5.0 (một chữ số thập phân), tính từ review của
  User HNAJ; place chưa có review giữ mặc định `5.0`.
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
4. Khi mọi place phù hợp đều đã nằm trong excluded, tiêu chí 1 bằng 0 cho tất cả ứng viên
   nên thứ tự do các tiêu chí còn lại quyết định; server vẫn trả về một place (QA-6).
