# HNaj - Theo dõi tiến độ dự án

> Cập nhật checkbox ngay khi một hạng mục đã hoàn thành và được kiểm tra ở phạm vi phù hợp.
> Nguồn yêu cầu: `docs/PRD_TECH_SPEC.md`, `docs/api/openapi.yaml`, `docs/api/recommendation-policy.md` và `docs/database/`.

## Baseline và quyết định cần chốt

- [x] PRD và technical specification baseline đã được QA.
- [x] OpenAPI v1 baseline và recommendation policy đã có.
- [x] ERD, data dictionary và traceability matrix đã có.
- [x] Laravel, React/Vite và Docker Compose scaffold đã có.
- [ ] Chốt trọng số recommendation và cấu hình vận hành.
- [ ] Chốt geocoding provider cho production.
- [ ] Chốt media vendor, giới hạn upload và rate limit.
- [ ] Chốt TTL cho invite/idempotency và mốc strict API compatibility.
- [ ] Chốt Docker topology với MySQL 8 riêng.

## Nền tảng backend và cơ sở dữ liệu

- [x] Hoàn thiện kết nối MySQL và quy trình migration trong Docker hiện tại.
- [x] Triển khai migration category, normalized tag/pivot và external place identity.
- [ ] Triển khai migration domain media và giờ mở cửa đầy đủ.
- [ ] Triển khai migration recommendation history với retention 30 ngày.
- [ ] Triển khai model, relation, factory và seed dữ liệu địa điểm.
- [x] Seed category và tag taxonomy cố định cho AI classification.
- [ ] Triển khai trạng thái nội dung: draft, pending_review, published, rejected, archived.
- [x] Đồng bộ ERD và data dictionary cho mọi thay đổi schema.

## API public và recommendation

- [x] Triển khai `GET /api/v1/tags` theo OpenAPI.
- [x] Triển khai `GET /api/v1/places/{slug}` cho place published, tính khoảng cách từ tọa độ người dùng khi FE gửi context.
- [ ] Triển khai `POST /api/v1/recommendations`.
- [ ] Áp dụng lọc vị trí, radius, budget và tags.
- [ ] Áp dụng cascade radius/tag theo recommendation policy.
- [ ] Áp dụng weighted random và Bayesian rating theo cấu hình đã chốt.
- [ ] Áp dụng chống lặp recommendation 24 giờ cho guest và user.
- [ ] Lưu recommendation request/result và retention history.

## Xác thực và tài khoản

- [x] Cấu hình Sanctum SPA cookie/session và CSRF.
- [x] Triển khai đăng ký, đăng nhập, đăng xuất và endpoint người dùng hiện tại.
- [ ] Triển khai quên/đặt lại mật khẩu sau khi contract được chốt.
- [ ] Triển khai xóa tài khoản, xóa PII và ẩn danh dữ liệu theo policy.
- [ ] Triển khai Google authentication sau khi contract được chốt.

## Tính năng người dùng

- [ ] Triển khai favorites idempotent: thêm, xóa và danh sách.
- [ ] Triển khai review/rating, aggregate Bayesian rating và experience tags.
- [ ] Triển khai báo cáo review và owner response sau khi contract được chốt.

## Quản trị nội dung

- [ ] Chốt OpenAPI cho role, invitation và ownership assignment.
- [ ] Chốt OpenAPI cho CRUD place/tag/amenity/media/opening-hours.
- [ ] Chốt OpenAPI cho submit/approve/reject/archive/restore.
- [x] Triển khai role catalog, user_roles và middleware kiểm tra admin/editor.
- [ ] Triển khai invitation và assignment owner.
- [x] Triển khai Admin import preview/confirm/status backend theo OpenAPI.
- [x] Xây Admin UI import preview/confirm cơ bản cho editor/admin.
- [ ] Chốt contract CSV import và idempotency trước khi triển khai import.
- [x] Xây parser CSV streaming và bước preview staging.
- [x] Dedup bằng external ID/fingerprint trước AI.
- [x] Thêm AI classifier batch tối đa 10 dòng, retry và strict taxonomy IDs.
- [x] Triển khai commit import atomic sau khi AI thành công; hủy batch khi AI thất bại.
- [x] Ghi contract OpenAPI cho preview, confirm và status import.

## Analytics và quan sát hệ thống

- [ ] Chốt OpenAPI cho analytics event ingestion và báo cáo.
- [ ] Triển khai allowlist event, consent và anonymous functional cookie.
- [ ] Triển khai analytics cho recommendation, impression, click, favorite, empty/bounce.
- [ ] Triển khai báo cáo owner và toàn hệ thống sau khi contract được chốt.
- [ ] Áp dụng retention raw analytics 12 tháng.

## Frontend

- [x] Thiết lập API client bám sát OpenAPI và chuẩn hóa error/message key.
- [ ] Xây dựng public flow mobile-first: vị trí, bộ lọc và recommendation loading/result.
- [x] Xây dựng trang chi tiết place từ recommendation; tag discovery còn trong backlog.
- [x] Xây dựng auth session UI cho user.
- [x] Xây dựng admin import flow desktop-oriented sau khi API quản trị được chốt.
- [x] Tách giao diện `/admin` khỏi trải nghiệm public và mở rộng nhận diện cho ăn uống, cà phê, vui chơi.
- [ ] Gửi analytics khi có consent.

## Chất lượng, tài liệu và phát hành

- [ ] Cập nhật OpenAPI, recommendation policy và `docs/api/CHANGELOG.md` cùng mỗi thay đổi API quan sát được từ client.
- [ ] Cập nhật migration, ERD và data dictionary cùng mỗi thay đổi schema.
- [x] Chạy frontend lint và build trong Docker.
- [ ] Kiểm tra contract OpenAPI và các `$ref` liên quan.
- [ ] Thiết lập CI cho syntax, lint, build và contract validation.
- [ ] Hoàn thành kiểm thử acceptance cho public, user, owner, editor và admin flows.
- [ ] Chuẩn bị release checklist và tài liệu vận hành.

## Nhật ký cập nhật

| Ngày | Hạng mục | Cập nhật | Người thực hiện |
| --- | --- | --- | --- |
| 2026-07-18 | Khởi tạo theo dõi tiến độ | Tạo checklist từ baseline tài liệu; chưa đánh dấu hoàn thành hạng mục nghiệp vụ. | Copilot |
| 2026-07-18 | Làm mới giao diện public | Đổi nhận diện thành “Hôm nay ăn gì”, thiết kế lại màn recommendation theo phong cách ẩm thực Việt trẻ trung và mobile-first. | Copilot |
| 2026-07-19 | Hoàn thiện empty state | Sửa hướng dẫn trạng thái chờ và bỏ mũi tên trang trí gây hiểu nhầm. | Copilot |
| 2026-07-19 | Auth, Admin và định vị sản phẩm | Sửa runtime session MySQL/CORS, tách trang Admin, mở rộng public discovery và đặt tọa độ mặc định tại Hà Nội. | Copilot |
| 2026-07-20 | Hoàn thiện API public tags | Khóa category taxonomy, active filtering, related-tag ranking và validation theo OpenAPI. | Copilot |
| 2026-07-20 | Hoàn thiện API public place detail | Thêm endpoint place published, nhận tọa độ người dùng tùy chọn và tính khoảng cách bằng Haversine. | Copilot |
| 2026-07-20 | Đơn giản hóa quy trình | Tập trung rules vào một file, bỏ test suite/test tooling trực tiếp và chuyển sang syntax, lint, build, contract cùng runtime checks. | Copilot |
| 2026-07-20 | Refactor ứng dụng | Tách API client/types khỏi React root và tách serializer place detail khỏi controller Laravel. | Copilot |
| 2026-07-20 | Tách trang place detail | Tách route detail khỏi React root để không khởi tạo auth/discovery/admin ngoài phạm vi; xác nhận dữ liệu thật và layout 390px không overflow. | Copilot |