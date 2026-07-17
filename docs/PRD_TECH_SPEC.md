# Tài liệu Đặc tả & Phân tích Hệ thống — HNaj

> **Dự án:** Gợi ý địa điểm theo ngữ cảnh (Context-aware Place Recommendation)
> **Tech Lead / PM:** Phạm Tuấn Đạt
> **Stack:** PHP Laravel (Backend) + React.js/Vite (Frontend) + MySQL (Database)
> **Ngày:** 2026-06-20

---

## 1. GÓC ĐỘ BA (Business Analyst) — Phân tích luồng & Fallback

### 1.1 Đánh giá luồng hiện tại

Luồng chính hiện tại: Input → Lọc không gian → Lọc giá → Lọc tags (AND) → Random → Limit → Output.

#### Các điểm "Dead-end" tiềm ẩn:

| # | Tình huống | Nguyên nhân | Xác suất |
|---|-----------|-------------|----------|
| 1 | Không có quán nào trong bán kính | Khu vực ngoại ô, vùng mới | Trung bình |
| 2 | Không quán nào vừa túi tiền | Khu vực cao cấp, ngân sách thấp | Trung bình–Cao |
| 3 | Không quán nào có đủ tags (AND) | Chọn quá nhiều tags, tags hiếm | Cao |
| 4 | Kết hợp 2+ yếu tố trên | — | Thấp–Trung bình |

**Kết luận:** Luồng hiện tại CHƯA chặn hoàn toàn Dead-end. Cần cơ chế fallback.

### 1.2 Đề xuất Luồng Fallback (Cascading Relaxation)

Áp dụng chiến lược "nới lỏng dần" (Cascading Relaxation) theo thứ tự ưu tiên:

```
Lần 1: Query gốc (đúng bán kính, đúng giá, đủ tags AND)
   ↓ nếu empty
Lần 2: Nới bán kính ×1.5 (VD: 1km→1.5km, 3km→4.5km, 5km→7.5km)
   ↓ nếu empty
Lần 3: Nới bán kính ×2.0 + giảm tags xuống OR (chỉ cần có ít nhất 1 tag)
   ↓ nếu empty
Lần 4: Bỏ qua tag filter hoàn toàn + nới bán kính ×3.0
   ↓ nếu empty
Lần 5: Trả về thông báo "Không tìm thấy địa điểm phù hợp. Hãy thử khu vực khác nhé!"
```

#### UX Feedback cho từng lần fallback:
- **Lần 2:** Toast: "Đang mở rộng phạm vi tìm kiếm lên {X}km..."
- **Lần 3:** Toast: "Đang tìm kiếm linh hoạt hơn với các tags liên quan..."
- **Lần 4:** Toast: "Đang hiển thị các địa điểm gần bạn nhất..."
- **Lần 5:** Hiển thị Empty State UI với illustration + CTA "Thử lại với bộ lọc khác".

#### Chỉ số theo dõi (Metrics):
- Tỷ lệ fallback từng cấp (để tuning bán kính mặc định)
- Tỷ lệ bounce khi nhận Empty State cuối cùng

---

## 2. GÓC ĐỘ BACKEND — API Contract

### 2.1 Endpoint

```
POST /api/v1/recommendations
Content-Type: application/json
```

### 2.2 Request Payload

```json
{
  "location": {
    "lat": 10.762622,
    "lng": 106.660172
  },
  "radius_km": 3.0,
  "price_max": 150000,
  "tags": ["hen-ho", "rieng-tu", "trong-nha"],
  "limit": 3
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `location.lat` | float64 | **Yes** | Vĩ độ (WGS 84) |
| `location.lng` | float64 | **Yes** | Kinh độ (WGS 84) |
| `radius_km` | float64 | **Yes** | Bán kính tìm kiếm (km), range: 0.5–20.0 |
| `price_max` | int | **Yes** | Ngân sách tối đa/người (VNĐ) |
| `tags` | []string | No | Danh sách tag slug (phép AND nếu có) |
| `limit` | int | No | Số kết quả (1 hoặc 3), mặc định: 3 |
| `fallback_level` | int | No | Dùng nội bộ cho fallback, FE không cần gửi |

### 2.3 Response — Success (200)

```json
{
  "success": true,
  "data": {
    "places": [
      {
        "id": "plc_a1b2c3",
        "name": "Cà phê Trứng Giảng",
        "slug": "ca-phe-trung-giang",
        "cover_image": "https://cdn.hnaj.app/images/plc_a1b2c3_cover.jpg",
        "distance_km": 2.5,
        "price_min": 30000,
        "price_max": 80000,
        "price_display": "30k - 80k",
        "tags": [
          {"id": "tag_01", "name": "Hẹn hò", "slug": "hen-ho"},
          {"id": "tag_03", "name": "Trong nhà", "slug": "trong-nha"}
        ],
        "matched_tags": ["hen-ho", "trong-nha"],
        "address": "39 Nguyễn Hữu Huân, Hoàn Kiếm, Hà Nội",
        "rating": 4.5
      }
    ],
    "meta": {
      "total_matched": 12,
      "fallback_applied": false,
      "fallback_level": 0,
      "query_radius_km": 3.0,
      "message": "Tìm thấy 12 địa điểm, hiển thị 3 gợi ý ngẫu nhiên."
    }
  }
}
```

### 2.4 Response — Fallback Applied (200)

```json
{
  "success": true,
  "data": {
    "places": [ /* ... same structure ... */ ],
    "meta": {
      "total_matched": 4,
      "fallback_applied": true,
      "fallback_level": 2,
      "query_radius_km": 6.0,
      "relaxed_tags": true,
      "message": "Đã mở rộng phạm vi lên 6km và tìm kiếm linh hoạt hơn."
    }
  }
}
```

### 2.5 Response — No Results (200)

```json
{
  "success": true,
  "data": {
    "places": [],
    "meta": {
      "total_matched": 0,
      "fallback_applied": true,
      "fallback_level": 5,
      "query_radius_km": 15.0,
      "message": "Không tìm thấy địa điểm phù hợp. Hãy thử khu vực khác nhé!"
    }
  }
}
```

### 2.6 Response — Validation Error (422)

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid request parameters.",
    "details": [
      {"field": "radius_km", "message": "Bán kính phải trong khoảng 0.5–20.0 km"},
      {"field": "location.lat", "message": "Vĩ độ không hợp lệ"}
    ]
  }
}
```

### 2.7 Response — Server Error (500)

```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Đã xảy ra lỗi. Vui lòng thử lại sau."
  }
}
```

### 2.8 SQL Query Mẫu (MySQL)

```sql
-- Query gốc (fallback_level = 0), khoảng cách tính bằng Haversine (km)
SELECT p.id, p.name, p.slug, p.cover_image,
       p.price_min, p.price_max, p.address, p.rating,
       (6371 * ACOS(
         COS(RADIANS(?)) * COS(RADIANS(p.latitude)) *
         COS(RADIANS(p.longitude) - RADIANS(?)) +
         SIN(RADIANS(?)) * SIN(RADIANS(p.latitude))
       )) AS distance_km
FROM places p
WHERE p.is_active = 1
AND (6371 * ACOS(
  COS(RADIANS(?)) * COS(RADIANS(p.latitude)) *
  COS(RADIANS(p.longitude) - RADIANS(?)) +
  SIN(RADIANS(?)) * SIN(RADIANS(p.latitude))
)) <= ?
AND p.price_min <= ?
AND (
    ? IS NULL
    OR p.id IN (
        SELECT pt.place_id
        FROM place_tag pt
        JOIN tags t ON t.id = pt.tag_id
        WHERE t.slug IN (?)
        GROUP BY pt.place_id
        HAVING COUNT(DISTINCT t.slug) = ?
    )
)
ORDER BY random()
LIMIT ?;
```

---

## 3. GÓC ĐỘ FRONTEND — Component Architecture & State Management

### 3.1 UI Components Tree

```
<App>
  <RecommendationPage>
    ├── <Header>                          // Logo + tagline
    ├── <SearchForm>
    │   ├── <LocationPicker>              // Nút GPS + hiển thị địa chỉ
    │   ├── <RadiusSelector>              // Slider chọn bán kính
    │   ├── <BudgetSlider>               // Slider chọn ngân sách
    │   ├── <TagSelector>                // Checkbox/Chips multi-select
    │   └── <ResultModeToggle>           // Switch "1 duy nhất" / "Top 3"
    ├── <CTASubmitButton>                // Nút "Khám phá ngay"
    ├── <LoadingSpinner>                 // Hiệu ứng vòng quay ngẫu nhiên
    ├── <RecommendationResult>
    │   ├── <PlaceCard> (×1 hoặc ×3)
    │   │   ├── <PlaceImage>             // Ảnh cover
    │   │   ├── <PlaceInfo>              // Tên, giá, khoảng cách
    │   │   └── <TagBadges>              // Các tag trùng khớp
    │   └── <ResultMeta>                 // Thông báo fallback nếu có
    └── <EmptyState>                     // Khi không có kết quả
  </RecommendationPage>
</App>
```

### 3.2 State Management — Zustand Store

```typescript
// store/useRecommendationStore.ts

interface RecommendationState {
  // Input state
  location: { lat: number; lng: number } | null;
  locationAddress: string;
  radiusKm: number;
  priceMax: number;
  selectedTags: string[];
  resultMode: 'single' | 'top3';

  // Output state
  isLoading: boolean;
  results: Place[];
  meta: ResultMeta | null;
  error: string | null;

  // Actions
  setLocation: (loc: { lat: number; lng: number }, address: string) => void;
  setRadius: (km: number) => void;
  setPriceMax: (price: number) => void;
  toggleTag: (tag: string) => void;
  setResultMode: (mode: 'single' | 'top3') => void;
  fetchRecommendations: () => Promise<void>;
  reset: () => void;
}
```

### 3.3 Reason for Zustand over Context API:
- **Performance:** Zustand chỉ re-render components subscribed to changed slice, tránh re-render toàn bộ tree như Context.
- **Simplicity:** Không cần Provider wrapper, không boilerplate.
- **DevTools:** Tích hợp Redux DevTools để debug state changes.
- **Size:** ~1KB bundle size.

### 3.4 Dữ liệu tĩnh — Danh sách Tags

```typescript
const DEFAULT_TAGS = [
  { slug: 'hen-ho',       name: 'Hẹn hò',       emoji: '💑' },
  { slug: 'gia-sinh-vien', name: 'Giá sinh viên', emoji: '🎓' },
  { slug: 'rieng-tu',     name: 'Riêng tư',      emoji: '🤫' },
  { slug: 'trong-nha',    name: 'Trong nhà',      emoji: '🏠' },
  { slug: 'ngoai-troi',   name: 'Ngoài trời',    emoji: '🌳' },
  { slug: 'the-thao',     name: 'Thể thao',       emoji: '⚽' },
  { slug: 'van-dong',     name: 'Vận động',       emoji: '🏃' },
  { slug: 'thu-gian',     name: 'Thư giãn',       emoji: '🧘' },
  { slug: 'sang-trong',   name: 'Sang trọng',     emoji: '✨' },
  { slug: 'thu-cung',     name: 'Thú cưng',       emoji: '🐶' },
  { slug: 'gia-dinh',     name: 'Gia đình',       emoji: '👨‍👩‍👧‍👦' },
  { slug: 'lam-viec',     name: 'Làm việc',       emoji: '💻' },
];
```

### 3.5 Luồng xử lý Fallback ở FE

```
User click "Khám phá ngay"
  → Gọi API lần 1 (fallback_level=0)
  → Nếu meta.fallback_applied = true:
      → Hiển thị toast với meta.message
      → Kết quả vẫn hiển thị bình thường (BE đã xử lý fallback)
  → Nếu results rỗng + fallback_level=5:
      → Hiển thị EmptyState component
```

BE sẽ chịu trách nhiệm toàn bộ logic cascade fallback. FE chỉ cần gọi API 1 lần duy nhất và hiển thị kết quả + thông báo từ meta.

---

## 4. CẤU TRÚC THƯ MỤC DỰ ÁN

```
hnaj/
├── docs/
│   └── PRD_TECH_SPEC.md          ← File này
├── hnaj-be/                       ← Laravel Backend
│   ├── cmd/server/main.go
│   ├── internal/
│   │   ├── handler/
│   │   ├── model/
│   │   ├── repository/
│   │   ├── service/
│   │   └── database/
│   ├── migrations/
│   ├── go.mod
│   └── Makefile
└── hnaj-fe/                       ← React.js + Vite + Tailwind CSS
    ├── src/
    │   ├── app/
    │   ├── components/
    │   ├── store/
    │   ├── types/
    │   └── lib/
    ├── package.json
    ├── vite.config.ts
    ├── index.html
    └── tailwind.config.ts
```

---

<sub>**Người biên soạn:** Cross-functional Team (BA + BE + FE) | **Phê duyệt:** Phạm Tuấn Đạt</sub>
