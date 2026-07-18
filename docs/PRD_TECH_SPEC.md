# HNaj Product and Technical Specification

> **Dự án:** HNaj - gợi ý địa điểm theo ngữ cảnh
> **Stack mục tiêu:** Laravel 13 + PHP 8.3, React 19 + Vite 8 + TypeScript, MySQL 8
> **Trạng thái tài liệu:** Baseline đã QA, implementation nghiệp vụ chưa bắt đầu
> **Ngôn ngữ sản phẩm:** UI tiếng Việt; API dùng error code/message key ổn định

## 1. Nguồn sự thật và phạm vi tài liệu

HNaj dùng các nguồn sự thật theo phạm vi:

- **Nghiệp vụ và UX:** tài liệu này.
- **HTTP contract:** [`api/openapi.yaml`](api/openapi.yaml).
- **Recommendation policy:** [`api/recommendation-policy.md`](api/recommendation-policy.md).
- **Database schema thực thi:** Laravel migrations; thiết kế và semantics được giải thích tại [`database/ERD.md`](database/ERD.md) và [`database/DATA_DICTIONARY.md`](database/DATA_DICTIONARY.md).

Khi có xung đột, phải dừng và báo cáo; không tự đoán. Mọi API client-observable change phải cập nhật OpenAPI, policy nếu liên quan và API changelog trong cùng change. Mọi schema change phải cập nhật migration, ERD và data dictionary.

## 2. Hiện trạng và kiến trúc mục tiêu

### 2.1 Hiện trạng scaffold

- `hnaj-be/` là Laravel skeleton 13.8 trên PHP 8.3, hiện chủ yếu có route web/health và migration mặc định.
- `hnaj-fe/` là React + Vite + TypeScript scaffold, hiện chưa có màn hình/domain recommendation.
- Docker Compose hiện chạy BE và FE; database dev đang được khởi tạo cùng container BE bằng MariaDB.
- Các operation trong OpenAPI là target contract, chưa phải bằng chứng route/code đã tồn tại.

### 2.2 Mục tiêu kiến trúc

- Backend Laravel phục vụ REST API `/api/v1`.
- Frontend React/Vite dùng OpenAPI làm contract cho request, response, error và message key.
- Database mục tiêu là MySQL 8 service riêng. Việc chuyển Docker topology là phase triển khai riêng, không thuộc baseline tài liệu này.
- Public flow mobile-first; admin flow desktop-oriented trong cùng React app.
- Authentication dùng Laravel Sanctum cookie/session và CSRF.

## 3. Actors và quyền

| Actor | Quyền chính |
|---|---|
| Guest | Chọn vị trí, lọc, recommendation, xem place/tag/review public; anonymous ID cookie hỗ trợ chống lặp 24 giờ. |
| User | Quyền guest; email/password hoặc Google auth; favorite; review/rating; xem/xóa account theo policy. |
| Owner | Do admin invite; quản lý một hoặc nhiều place được assignment; gửi nội dung duyệt; analytics riêng; phản hồi review của place. |
| Editor | CRUD place/tag/content; import; duyệt hoặc từ chối nội dung owner; không quản lý user/role. |
| Admin | Quản lý user/role/invite/assignment, nội dung, workflow và analytics toàn hệ thống. |

Một user có thể có nhiều role. Owner không tự đăng ký; admin tạo invite. Một place có thể có nhiều owner/manager.

## 4. Content workflow và domain model

Mỗi chi nhánh là một `Place`, có ULID public ID và slug unique. Place có tên, mô tả, địa chỉ, WGS 84 latitude/longitude, khoảng giá VND, giờ mở cửa, liên hệ, tiện ích, gallery/cover, tags và rating aggregate.

Target content status: `draft` -> `pending_review` -> `published` hoặc `rejected` -> `archived`. Soft delete được dùng cùng archive cho dữ liệu cần phục hồi/audit. Public recommendation chỉ dùng place published, chưa bị xóa mềm và đang mở.

Domain target gồm place/tag/group/amenity/media/opening-hours, ownership, users/roles/invites, favorites, recommendation history, reviews/review media/experience tags/reports/owner responses, analytics events và idempotency records. Chi tiết target schema nằm trong database docs.

## 5. Recommendation

Request chính là `POST /api/v1/recommendations`; contract đầy đủ nằm trong OpenAPI.

- Vị trí bắt buộc; hỗ trợ browser GPS và address search qua geocoding adapter. Provider production là TBD.
- Radius input 0.5-20 km; fallback không vượt 20 km.
- Budget là hard constraint. Place phù hợp khi giá trung bình của `price_min`/`price_max` không vượt `price_max` request.
- Tags match OR và scoring theo số tag khớp.
- Weighted random ưu tiên tag > distance > Bayesian rating. Hệ số cụ thể là configurable/TBD.
- Cascade: radius 1.5x -> giảm yêu cầu tag score -> bỏ tag -> radius 2x, dừng khi đủ `limit`; hết cấp trả tập tốt nhất có được.
- Không nới budget.
- Mọi place được gợi ý trong 24 giờ bị loại khỏi pool ưu tiên. Guest dùng anonymous functional cookie; user dùng user ID. Khi hết pool, place cũ hơn 24 giờ được phép xuất hiện.
- Backend xử lý cascade trong một request. FE hiển thị loading chung và chỉ hiển thị message/meta cuối.
- Test/local có thể dùng seed random nội bộ; production không nhận seed từ client.

## 6. Public user features

- `GET /api/v1/tags`: tag active/published có group, icon/emoji, sort order và search.
- `GET /api/v1/places/{slug}`: detail place published.
- Favorites: user authenticated thêm/xóa/list; PUT/DELETE idempotent.
- Reviews: user authenticated được một review/place, gồm rating 1-5, text, ảnh và experience tags; auto-publish, có report/moderation. Owner được một response/review.
- Rating aggregate dùng Bayesian/weighted rating để giảm ảnh hưởng của sample nhỏ.
- User không xem recommendation history và không có reset history trong MVP.

## 7. Authentication, privacy và analytics

User thường có thể đăng ký email/password hoặc Google. Owner/editor/admin chỉ được tạo qua admin invite. Auth SPA dùng Sanctum cookie/session.

Backend tự ghi recommendation request/result. Frontend chỉ gửi các event allowlisted như impression, click, favorite, empty/bounce; analytics cần consent. Anonymous ID là functional cookie cần cho chống lặp. History giữ 30 ngày; raw analytics giữ 12 tháng. Khi xóa account, xóa PII/token/profile/favorite và ẩn danh nội dung/analytics hợp lệ.

## 8. Compatibility và quality rules

API dùng URL `/api/v1`. Trước release, contract có thể điều chỉnh trong v1 nhưng phải cập nhật tất cả tài liệu liên quan. Sau release, breaking change phải deprecate có thời hạn hoặc tạo version mới.

Create quan trọng và import hỗ trợ `Idempotency-Key`; TTL cụ thể TBD. Contract validation là OpenAPI-driven. Backend integration test mục tiêu chạy MySQL 8; frontend cần unit/component và E2E critical flows. Mọi dependency mới phải nêu package, lý do, alternative và chờ duyệt.

## 9. Non-goals và TBD

Baseline này chưa triển khai route, migration, model, service, component, admin UI, Docker topology MySQL 8, Sanctum, Zustand, Tailwind, router, test framework, CI hay provider geocoding/media.

Các điểm TBD cần quyết định trước implementation tương ứng: score weights, geocoding provider, production media vendor, rate limits, upload limits, CSV limits, invite/idempotency TTL và ngày bắt đầu strict compatibility sau release.
