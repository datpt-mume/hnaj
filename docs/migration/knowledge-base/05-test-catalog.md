# 05 — Test Catalog (239 tests → behavioral spec)

> Nguồn: [`hnaj-be/tests/`](../../../hnaj-be/tests/). Kết quả baseline gần nhất (project-progress): **239 passed / 953 assertions**.
> Mọi test Feature Laravel là **spec hành vi** — Phase 3 phải port 1-1 sang JUnit + MockMvc/TestRestTemplate cùng assertion, hoặc chứng minh không port được (kèm lý do trong matrix).

## Feature tests → endpoint/domain

| File Laravel | Số test (ước lượng) | Endpoint/hành vi phủ | Spring test class port |
|---|---|---|---|
| `tests/Feature/Auth/RegisterTest.php` | nhiều | POST /auth/register validation + success + duplicate | `RegisterTestIT` |
| `tests/Feature/Auth/LoginTest.php` | | POST /auth/login: đúng/sai, chưa verify, suspended | `LoginTestIT` |
| `tests/Feature/Auth/EmailVerificationTest.php` | | verify + resend: token hợp lệ/hết hạn/dùng lại, 409 | `EmailVerificationTestIT` |
| `tests/Feature/Auth/AccountSetupTest.php` | | POST /auth/account/setup one-time token | `AccountSetupTestIT` |
| `tests/Feature/Auth/GoogleAuthTest.php` | | redirect/callback/exchange, state reuse, link email | `GoogleAuthTestIT` (mock Google) |
| `tests/Feature/Auth/AdminLoginTest.php` | | POST /admin/auth/login role admin only | `AdminLoginTestIT` |
| `tests/Feature/Auth/CreateAdminAccountTest.php` | | bootstrap admin (command/one-time) | `CreateAdminAccountTestIT` |
| `tests/Feature/Auth/UpdateProfileTest.php` | 7/27 | PATCH /auth/me: chỉ full_name, avatar read-only | `UpdateProfileTestIT` |
| `tests/Feature/Auth/RoleMiddlewareTest.php` | | 401 vs 403, role user/sub_admin/admin | `RoleMiddlewareTestIT` |
| `tests/Feature/Admin/AdminPlaceCrudTest.php` | | list/create/show/update admin places | `AdminPlaceCrudTestIT` |
| `tests/Feature/Admin/AdminPlaceDeleteTest.php` | | soft-delete + hard-delete (không cần body) | `AdminPlaceDeleteTestIT` |
| `tests/Feature/Admin/AdminPlaceManagerTest.php` | | managers index/store/resend/revoke | `AdminPlaceManagerTestIT` |
| `tests/Feature/Admin/AdminPlaceOpeningHoursUpdateTest.php` | 2/8 | **regression day_of_week 2..8** | `AdminOpeningHoursTestIT` |
| `tests/Feature/Admin/AdminTagCreateTest.php` | 5/20 | POST /admin/tags | `AdminTagCreateTestIT` |
| `tests/Feature/Admin/AdminManagerApplicationTest.php` | | list/approve/reject | `AdminManagerApplicationTestIT` |
| `tests/Feature/Bookmark/BookmarkTest.php` | 15 | CRUD + 409 + guest 401 | `BookmarkTestIT` |
| `tests/Feature/Discovery/DiscoveryRandomTest.php` | | filter, open_now, scorer, excluded, optional auth | `DiscoveryRandomTestIT` |
| `tests/Feature/Discovery/DiscoveryMetadataTest.php` | 2/15 | GET /meta/discovery active only | `DiscoveryMetadataTestIT` |
| `tests/Feature/Place/PlaceSearchTest.php` | | q required, trim, sort rating/name, pagination | `PlaceSearchTestIT` |
| `tests/Feature/Place/PlaceDetailTest.php` | 8 | 200/404, is_bookmarked optional auth | `PlaceDetailTestIT` |
| `tests/Feature/Visit/VisitTest.php` | 15/50 | POST idempotent user+anon, GET history unique | `VisitTestIT` |
| `tests/Feature/ApiResponseTest.php` | | envelope success/error + exception mapping | `ApiContractTestIT` |
| `tests/Feature/ContentAndScheduleModelTest.php` | | schema models review/comment/hours (planned domain) | `SchemaModelTestIT` |
| `tests/Feature/ReferenceSeederTest.php` | | seeder roles/districts/categories/tags | `SeederTestIT` |
| `tests/Feature/PlaceCsvImportSeederTest.php` | | CSV import seeder (dev-only) | quyết định: port hay loại |
| `tests/Unit/PlaceScorerTest.php` | | scoring weights, proximity/rating normalize | `PlaceScorerTest` (unit thuần) |
| `tests/Unit/PlaceImportTest.php` | | CSV reader, duplicate detector, validator | dev-only, hỏi người dùng |

## Đơn vị đo "không miss"

1. **Endpoint coverage:** mỗi dòng trong `00-inventory-endpoints.md` phải trỏ ≥1 test Laravel nguồn + ≥1 test Spring port.
2. **Assertion parity:** số assertion Spring ≥ assertion Laravel (không giảm để cho pass).
3. **Không sửa test để che lỗi** (AGENTS.md §12.1).
4. Baseline chạy lại trƯỚC khi port: `cd hnaj-docker && docker compose --env-file .env exec backend php artisan test` → ghi kết quả vào matrix làm "golden baseline".

## Lưu ý fixture
- Factories: [`UserFactory`](../../../hnaj-be/database/factories/UserFactory.php), `PlaceFactory` (mặc định `is_verified=true`, có state `unverified()`), `CategoryFactory`, `DistrictFactory`, `TagFactory`, `PlaceOpeningHourFactory` → port sang Java test builders tương ứng.
- Test DB riêng `hnaj_test` (init sẵn trong docker) — Spring port dùng H2? **Không**: dùng MySQL `hnaj_java_test` để parity SQL behavior (lockForUpdate, unique index).
