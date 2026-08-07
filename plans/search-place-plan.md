# Plan — Chức năng tìm kiếm địa điểm (Search Place)

- **Trạng thái:** Chờ duyệt
- **Ngày soạn:** 2026-08-07
- **Phạm vi:** Trang kết quả tìm kiếm riêng `/search`, public, không yêu cầu đăng nhập
- **Quy tắc áp dụng:** `AGENTS.md` mục 5 (workflow), 7 (kiến trúc backend), 8 (kiến trúc frontend), 9 (API contract), 12 (kiểm thử), 3.1 (kiểm chứng trong Docker)
- **Nguồn nghiệp vụ:** [`docs/prd.md`](../docs/prd.md:635) mục 11 (Places: danh sách, tìm kiếm/lọc), mục 6.1 (chỉ place `active` xuất hiện công khai)

---

## 1. Mục tiêu và phạm vi

### 1.1. Hành vi mong muốn

1. Ô search trong nav (hiện đang chết tại [`HomePage.tsx`](../hnaj-fe/src/pages/HomePage.tsx:135)) khi submit sẽ điều hướng tới `/search?q=<từ khóa>`.
2. Trang `/search` hiển thị danh sách place active khớp từ khóa, pagination, sort theo rating giảm dần rồi đến name tăng dần.
3. Public — khách chưa đăng nhập dùng được; không cá nhân hóa kết quả.

### 1.2. Đã chốt (QA)

| # | Quyết định |
|---|---|
| 1 | Query rỗng → FE **không gọi API**, hiển thị empty state hướng dẫn gõ từ khóa |
| 2 | Matching: query khớp `name` **HOẶC** `address_text` **HOẶC** tên tag **HOẶC** tên category (ANY), không phân biệt hoa thường, không yêu cầu từ đủ |
| 3 | Sort cố định: `rating` DESC, sau đó `name` ASC (tiếng Việt dùng collation `utf8mb4_unicode_ci`) |
| 4 | Pagination: page-based, `per_page` mặc định 10, tối đa 50 |
| 5 | Không kết hợp bộ lọc discovery (category/district/price/tags/open_now) trong MVP search |
| 6 | Không yêu cầu độ dài query tối thiểu (chỉ yêu cầu query không rỗng/không phải toàn khoảng trắng) |

### 1.3. Ngoài phạm vi (không làm trong task này)

- Fulltext index / MySQL `FULLTEXT` / ranking relevance phức tạp (dùng `LIKE` trước; nâng cấp sau khi có dữ liệu lớn — `ponytail`)
- Sort linh hoạt theo field (chỉ rating DESC + name ASC)
- Autocomplete/suggest khi gõ
- Kết hợp bộ lọc discovery, tìm kiếm theo khoảng cách/GPS
- Trang chi tiết place `/places/:id` (kết quả search sẽ click mở Google Maps hoặc để placeholder; trang detail là task riêng)

---

## 2. Contract API — `GET /api/places/search`

### 2.1. Request

| Param | Type | Bắt buộc | Ràng buộc |
|---|---|---|---|
| `q` | string | Có | trim; không rỗng; tối đa 100 ký tự |
| `page` | int | Không | ≥ 1, mặc định 1 |
| `per_page` | int | Không | 1–50, mặc định 10 |

Auth: **public**, không token. Throttle: `60,1` (chống spam query, theo convention endpoint public hiện có).

Ví dụ: `GET /api/places/search?q=pho&page=2&per_page=10`

### 2.2. Matching (backend)

- Chỉ place `status = active`, không soft-deleted (scope mặc định của [`Place`](../hnaj-be/app/Models/Place.php:13)).
- Query được split theo khoảng trắng thành các token; **mỗi token phải khớp ít nhất một** trong: `places.name`, `places.address_text`, `tags.name`, `categories.name` (AND giữa các token, OR trong mỗi token — hành vi tìm kiếm thông thường).
- Không phân biệt hoa/thường (collation `utf8mb4_unicode_ci` mặc định MySQL 8.4; SQLite test dùng `LIKE` case-insensitive với ASCII).
- Dùng `LIKE %token%` (không fulltext). Nếu query không phải toàn khoảng trắng và hợp lệ, luôn trả paginator (có thể rỗng).

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
      "tags": [ { "id": 190, "name": "Đồ ăn đường phố", "slug": "do-an-duong-pho" } ],
      "min_price": 40000,
      "max_price": 80000,
      "rating": 4.8,
      "thumbnail": { "image_url": "https://...", "alt_text": "Phở Gia Truyền" },
      "latitude": 21.0333330,
      "longitude": 105.8500000,
      "google_maps_url": "https://maps.google.com/?q=...",
      "opening_hours": [ { "day_of_week": 2, "schedule_type": "regular", "opens_at": "06:00", "closes_at": "21:00" } ]
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

- Item shape **tái sử dụng** [`PlaceResource`](../hnaj-be/app/Http/Resources/PlaceResource.php:17) — giữ nguyên payload card đã có; không thêm field mới.
- `meta` tuân theo envelope [`docs/api-response-contract.md`](../docs/api-response-contract.md:16): `current_page`, `last_page`, `per_page`, `total` (dùng `LengthAwarePaginator`).
- Sort: `rating` DESC, `name` ASC. Place chưa có review giữ mặc định `rating = 5.0` (đã chốt PRD) nên vẫn nằm trên cùng — chấp nhận, nhất quán với discovery.

### 2.4. Lỗi

| Case | HTTP | code | errors |
|---|---|---|---|
| Thiếu `q`, `q` rỗng sau trim, `q` > 100 ký tự | 422 | `VALIDATION_ERROR` | `q: [...]` |
| `page`/`per_page` không hợp lệ | 422 | `VALIDATION_ERROR` | theo field |

Không có case 404 (search luôn trả 200 kèm list có thể rỗng).

### 2.5. Tài liệu

Tạo file mới [`docs/api-search.md`](../docs/api-search.md) (theo format [`docs/api-discovery.md`](../docs/api-discovery.md:1)) — trạng thái, request/response, ví dụ, quy tắc nghiệp vụ. Cập nhật liên kết từ [`docs/prd.md`](../docs/prd.md:635) nếu cần (chỉ ghi chú, không sửa nội dung nghiệp vụ).

---

## 3. Backend — phân lớp theo AGENTS.md mục 7

Luồng: `Route → Controller → Service/Action → Repository → Model`

### 3.1. File mới

| File | Nội dung |
|---|---|
| [`hnaj-be/app/Http/Requests/Place/SearchPlaceRequest.php`](../hnaj-be/app/Http/Requests/Place/SearchPlaceRequest.php) | Validation: `q` required, string, trim, max:100; `page` nullable int min:1; `per_page` nullable int min:1 max:50 (dùng `PlaceSearch::MAX_PER_PAGE`). Method `query(): string` trả query đã trim; `perPage(): int` mặc định 10 |
| [`hnaj-be/app/Repositories/PlaceSearchRepository.php`](../hnaj-be/app/Repositories/PlaceSearchRepository.php) | `search(string $query, int $perPage, int $page): LengthAwarePaginator`. Dựng query trên `Place::query()`: where status active, whereHas category/tags theo token, orderBy rating DESC + name ASC, `paginate($perPage, ['*'], 'page', $page)`. **Không** hydrate toàn bộ — paginate chạy SQL. Eager load `district`, `category`, `tags`, `thumbnail` cho resource |
| [`hnaj-be/app/Actions/Place/SearchPlaces.php`](../hnaj-be/app/Actions/Place/SearchPlaces.php) | Action use case: nhận query + pagination, gọi repository, trả `LengthAwarePaginator` (giữ pattern `SelectBestPlace`) |

> Cân nhắc: repo `PlaceSearchRepository` tách khỏi [`PlaceRepository`](../hnaj-be/app/Repositories/PlaceRepository.php:18) vì `PlaceRepository` hiện gắn chặt discovery scoring (PlaceScorer). Nếu code mode thấy query đơn giản, có thể thêm method `search()` vào `PlaceRepository` — quyết định cuối theo code mode, không tạo 2 lớp chỉ để chuyển tiếp dữ liệu (AGENTS.md 7.1).

### 3.2. File sửa

| File | Thay đổi |
|---|---|
| [`hnaj-be/routes/api.php`](../hnaj-be/routes/api.php:21) | Thêm group `Route::prefix('places')`: `Route::get('/search', SearchPlaceController::class)->middleware('throttle:60,1')`. Đặt trước bất kỳ route `places/{place}` tương lai (chưa có) |
| [`hnaj-be/app/Http/Controllers/Api/Place/SearchPlaceController.php`](../hnaj-be/app/Http/Controllers/Api/Place/SearchPlaceController.php) | `__invoke(SearchPlaceRequest $request, SearchPlaces $action)`: gọi action, trả `ApiResponse::success(data: PlaceResource::collection($paginator->items()), meta: ['current_page'=>..., 'last_page'=>..., 'per_page'=>..., 'total'=>...])` |

**Không thay đổi:** `PlaceResource`, `ApiResponse`, `PlaceRepository` hiện có, model, migration, seeder, Docker config. Không cần migration mới (không đổi schema).

---

## 4. Frontend — theo AGENTS.md mục 8

### 4.1. Service layer

| File | Thay đổi |
|---|---|
| [`hnaj-fe/src/services/placeSearchService.ts`](../hnaj-fe/src/services/placeSearchService.ts) (mới) | `searchPlaces(query: string, page = 1, perPage = 10): Promise<SearchResult>` — gọi `GET /places/search` qua [`apiRequest`](../hnaj-fe/src/services/httpClient.ts:38). Types: `SearchPlace` (tái sử dụng shape `DiscoveryPlace` — xem dưới), `SearchMeta { current_page, last_page, per_page, total }`, `SearchResult { places: SearchPlace[], meta: SearchMeta }`. Không token — public |

> Tái sử dụng type: `DiscoveryPlace` trong [`discoveryService.ts`](../hnaj-fe/src/services/discoveryService.ts:12) có đúng shape `PlaceResource`. Code mode quyết định: export/reuse type đó (đổi tên chung `Place` nếu gọn) hoặc định nghĩa type riêng — tránh trùng lặp không cần thiết, nhưng không refactor rộng.

### 4.2. Page + route

| File | Thay đổi |
|---|---|
| [`hnaj-fe/src/pages/SearchPage.tsx`](../hnaj-fe/src/pages/SearchPage.tsx) (mới) | Đọc `q`, `page` từ `useSearchParams`; state loading/error/empty; gọi service khi `q` thay đổi (debounce không bắt buộc — submit-driven); render: kết quả là danh sách thẻ (grid/cards), điều hướng pagination (link `?q=...&page=n`), empty state khi không có kết quả hoặc khi chưa gõ query |
| [`hnaj-fe/src/routes/AppRoutes.tsx`](../hnaj-fe/src/routes/AppRoutes.tsx:16) | Thêm `<Route path="/search" element={<SearchPage />} />` |
| [`hnaj-fe/src/pages/HomePage.tsx`](../hnaj-fe/src/pages/HomePage.tsx:135) | Wire ô search: form submit → `navigate('/search?q=' + encodeURIComponent(value))` (xử lý submit với query rỗng: không navigate hoặc navigate tới `/search` để page hiện empty state — chốt: navigate, page tự xử lý) |
| [`hnaj-fe/src/App.css`](../hnaj-fe/src/App.css:943) | Thêm style cho search page, result grid, pagination — theo token hiện có (không hard-code màu mới; tham chiếu [`tokens.css`](../hnaj-fe/tokens.css:1) và design system Hallmark đã có trong [`design-system/hnaj/MASTER.md`](../design-system/hnaj/MASTER.md:1)) |

### 4.3. Component

- Tái sử dụng [`PlaceCard`](../hnaj-fe/src/components/PlaceCard.tsx:26) nếu props phù hợp (hiện props gắn với random: `onRoll`, `isRolling`). Code mode quyết định: thêm props optional (`onRoll?: () => void` để bỏ roll) hoặc tạo card liệt kê nhẹ riêng cho search. Không refactor PlaceCard rộng.
- Loading: tái sử dụng [`Skeleton`](../hnaj-fe/src/components/Skeleton.tsx:1) + EmptyState.

### 4.4. Trạng thái UI (AGENTS.md 8.2)

- Loading: skeleton list.
- Error: `ApiRequestError` → thông báo + retry.
- Empty: hai loại — (a) chưa có query: hướng dẫn gõ từ khóa; (b) có query không khớp: "Không tìm thấy địa điểm phù hợp với 'X'".
- Success: danh sách + pagination + tổng số kết quả.
- A11y: semantic form/label, focus rõ, keyboard thao tác pagination, `aria-live` cho vùng kết quả.

---

## 5. Kiểm thử

### 5.1. Backend — Feature test mới

[`hnaj-be/tests/Feature/Place/PlaceSearchTest.php`](../hnaj-be/tests/Feature/Place/PlaceSearchTest.php) (theo mẫu [`DiscoveryRandomTest`](../hnaj-be/tests/Feature/Discovery/DiscoveryRandomTest.php:28), `RefreshDatabase`, MySQL test):

| Case | Assert |
|---|---|
| Search khớp name (case-insensitive) | Trả đúng place, 200, envelope đúng |
| Search khớp address_text | Trả đúng place |
| Search khớp tên tag | Trả place có tag đó |
| Search khớp tên category | Trả place thuộc category đó |
| Nhiều token: place khớp tất cả token được trả, place khớp 1 token bị loại | AND giữa token |
| Place `hidden`/soft-deleted không xuất hiện dù khớp | Filter status đúng |
| Sort rating DESC, name ASC | Thứ tự đúng, tie-break name |
| Pagination: per_page mặc định 10, max 50, page ngoài phạm vi | `meta` đúng, page quá lớn trả rỗng 200 |
| Validation: thiếu `q`, `q` toàn khoảng trắng, `q` > 100, `per_page` > 50 | 422 `VALIDATION_ERROR` |
| Query không khớp gì | 200, `data: []`, `total: 0` |

### 5.2. Frontend

- Không test framework mới (repo chưa có test runner FE riêng ngoài lint/build — kiểm tra lại trong code mode; theo AGENTS.md 12.1 UI nhỏ không bắt buộc test tự động mới).
- Bắt buộc chạy: `npm run lint`, `npm run build` trong container.

### 5.3. Kiểm chứng trong Docker (AGENTS.md 3.1)

```bash
cd hnaj-docker
docker compose --env-file .env ps   # kiểm tra container
docker compose --env-file .env exec backend php artisan test --filter=PlaceSearchTest
docker compose --env-file .env exec frontend npm run lint
docker compose --env-file .env exec frontend npm run build
```

---

## 6. Checklist hoàn thành (AGENTS.md 12.2)

- [ ] Contract `GET /api/places/search` triển khai đúng docs [`docs/api-search.md`](../docs/api-search.md)
- [ ] BE: Route → Controller → Action → Repository, không query trong controller
- [ ] FE: service layer + page + route, không gọi API rải rác
- [ ] Envelope + `meta` pagination đúng
- [ ] Test BE đạt, lint/build FE đạt trong Docker
- [ ] Không secret, không debug code, không file ngoài phạm vi
- [ ] Rà soát diff cuối

---

## 7. Rủi ro và lưu ý

| Rủi ro | Xử lý |
|---|---|
| `LIKE %token%` chậm khi dữ liệu lớn | Chấp nhận cho MVP; nâng cấp fulltext/index riêng là task mở rộng sau (`ponytail`) — cần duyệt riêng |
| Sort `name` ASC với tiếng Việt phụ thuộc collation | Dùng collation mặc định `utf8mb4_unicode_ci` của MySQL 8.4 — nhất quán toàn repo |
| `PlaceResource` thiếu eager load gây N+1 | Eager load trong repository: `district`, `category`, `tags`, `thumbnail`, `openingHours` |
| SQLite vs MySQL khác biệt LIKE case | Test chạy MySQL (phpunit.xml đã ép `hnaj_test`) — đúng production |
| Thêm route mới không ảnh hưởng route `places/{id}` tương lai | Đặt `/search` trước; chưa có route conflict hiện tại |
