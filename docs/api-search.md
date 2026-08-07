# API tìm kiếm địa điểm — HNAJ

- **Trạng thái:** Đã triển khai (search place MVP)
- **Cập nhật:** 2026-08-07
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)
- **Nghiệp vụ:** [`docs/prd.md`](prd.md) mục 11 (Places: danh sách, tìm kiếm/lọc)

## 1. Tổng quan

Endpoint cho phép khách chưa đăng nhập lẫn User đã đăng nhập tìm kiếm địa điểm
theo từ khóa. Endpoint **public**, không yêu cầu token; được throttle để chống
spam query.

- Chỉ trả place có `status = active`; place `hidden` hoặc soft-deleted không xuất hiện.
- Matching **ANY** trong từng token (name / address / tên tag / tên category),
  **AND** giữa các token. Không phân biệt hoa thường.
- Sort cố định: `rating` giảm dần, sau đó `name` tăng dần.
- Pagination page-based; `per_page` mặc định 10, tối đa 50.

## 2. `GET /api/places/search`

### 2.1. Request

| Param | Type | Bắt buộc | Mô tả | Ràng buộc |
|---|---|---|---|---|
| `q` | string | Có | Từ khóa tìm kiếm | trim; không rỗng; tối đa 100 ký tự |
| `page` | int | Không | Trang kết quả | ≥ 1, mặc định 1 |
| `per_page` | int | Không | Số kết quả mỗi trang | 1–50, mặc định 10 |

Ví dụ:

```
GET /api/places/search?q=pho%20bo&page=2&per_page=10
```

### 2.2. Quy tắc nghiệp vụ

- Query được tách thành token theo khoảng trắng. **Mỗi token phải khớp** ít nhất
  một trong: `places.name`, `places.address_text`, `categories.name` (category
  của place), `tags.name` (tag của place). Đây là tìm kiếm ANY trong token và
  AND giữa các token — kết quả "phở bò" yêu cầu place khớp cả "phở" và "bò".
- Không yêu cầu độ dài token tối thiểu; `q` chỉ cần không rỗng sau trim.
- Chưa dùng fulltext ranking: matching là `LIKE %token%`. Dữ liệu lớn sẽ nâng
  cấp riêng, không đổi contract.

### 2.3. Response — HTTP 200

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
      "opening_hours": [
        { "day_of_week": 2, "schedule_type": "regular", "opens_at": "06:00", "closes_at": "21:00" }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 27
  }
}
```

- Item shape giống payload card của [`docs/api-discovery.md`](api-discovery.md) §2.4
  (dùng chung `PlaceResource`).
- `thumbnail` là `null` nếu place chưa có ảnh; `min_price`/`max_price` là `null`
  nếu chưa có thông tin giá; `rating` mặc định `5.0` cho place chưa có review.
- Không có kết quả → HTTP 200, `data: []`, `meta.total = 0`.

### 2.4. Lỗi validation — HTTP 422

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "q": ["The q field is required."]
  },
  "code": "VALIDATION_ERROR"
}
```

## 3. Frontend contract

- Service: `searchPlaces(query, page, perPage)` trong
  [`hnaj-fe/src/services/placeSearchService.ts`](../hnaj-fe/src/services/placeSearchService.ts).
- FE **không gọi API** khi query rỗng hoặc toàn khoảng trắng — hiển thị empty
  state hướng dẫn gõ từ khóa.
