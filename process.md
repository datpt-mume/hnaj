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

- [ ] Hoàn thiện kết nối MySQL 8 và quy trình migration trong Docker.
- [ ] Thiết kế/triển khai migration domain place, tag, amenity, media và giờ mở cửa.
- [ ] Triển khai migration recommendation history với retention 30 ngày.
- [ ] Triển khai model, relation, factory và seed dữ liệu địa điểm.
- [ ] Triển khai trạng thái nội dung: draft, pending_review, published, rejected, archived.
- [ ] Đồng bộ ERD và data dictionary cho mọi thay đổi schema.
- [ ] Viết test migration và ràng buộc MySQL quan trọng.

## API public và recommendation

- [ ] Triển khai `GET /api/v1/tags` theo OpenAPI.
- [ ] Triển khai `GET /api/v1/places/{slug}` cho place published.
- [ ] Triển khai `POST /api/v1/recommendations`.
- [ ] Áp dụng lọc vị trí, radius, budget và tags.
- [ ] Áp dụng cascade radius/tag theo recommendation policy.
- [ ] Áp dụng weighted random và Bayesian rating theo cấu hình đã chốt.
- [ ] Áp dụng chống lặp recommendation 24 giờ cho guest và user.
- [ ] Lưu recommendation request/result và retention history.
- [ ] Viết feature/integration test cho các flow recommendation chính.

## Xác thực và tài khoản

- [ ] Cấu hình Sanctum SPA cookie/session và CSRF.
- [ ] Triển khai đăng ký, đăng nhập, đăng xuất và endpoint người dùng hiện tại.
- [ ] Triển khai quên/đặt lại mật khẩu sau khi contract được chốt.
- [ ] Triển khai xóa tài khoản, xóa PII và ẩn danh dữ liệu theo policy.
- [ ] Triển khai Google authentication sau khi contract được chốt.
- [ ] Viết test xác thực, phân quyền và privacy retention.

## Tính năng người dùng

- [ ] Triển khai favorites idempotent: thêm, xóa và danh sách.
- [ ] Triển khai review/rating, aggregate Bayesian rating và experience tags.
- [ ] Triển khai báo cáo review và owner response sau khi contract được chốt.
- [ ] Viết test favorites và review.

## Quản trị nội dung

- [ ] Chốt OpenAPI cho role, invitation và ownership assignment.
- [ ] Chốt OpenAPI cho CRUD place/tag/amenity/media/opening-hours.
- [ ] Chốt OpenAPI cho submit/approve/reject/archive/restore.
- [ ] Triển khai role, invitation và assignment owner.
- [ ] Triển khai CRUD và workflow cho editor/admin/owner.
- [ ] Chốt contract CSV import và idempotency trước khi triển khai import.
- [ ] Triển khai import sau khi contract được chốt.

## Analytics và quan sát hệ thống

- [ ] Chốt OpenAPI cho analytics event ingestion và báo cáo.
- [ ] Triển khai allowlist event, consent và anonymous functional cookie.
- [ ] Triển khai analytics cho recommendation, impression, click, favorite, empty/bounce.
- [ ] Triển khai báo cáo owner và toàn hệ thống sau khi contract được chốt.
- [ ] Áp dụng retention raw analytics 12 tháng.

## Frontend

- [ ] Thiết lập API client bám sát OpenAPI và chuẩn hóa error/message key.
- [ ] Xây dựng public flow mobile-first: vị trí, bộ lọc và recommendation loading/result.
- [ ] Xây dựng trang chi tiết place và tag discovery.
- [ ] Xây dựng auth, favorites và reviews cho user.
- [ ] Xây dựng admin flow desktop-oriented sau khi API quản trị được chốt.
- [ ] Gửi analytics khi có consent.
- [ ] Viết unit/component test và E2E cho các critical flow.

## Chất lượng, tài liệu và phát hành

- [ ] Cập nhật OpenAPI, recommendation policy và `docs/api/CHANGELOG.md` cùng mỗi thay đổi API quan sát được từ client.
- [ ] Cập nhật migration, ERD và data dictionary cùng mỗi thay đổi schema.
- [ ] Chạy backend test trong Docker.
- [ ] Chạy frontend lint và build trong Docker.
- [ ] Kiểm tra contract OpenAPI và các `$ref` liên quan.
- [ ] Thiết lập CI cho test, lint, build và contract validation.
- [ ] Hoàn thành kiểm thử acceptance cho public, user, owner, editor và admin flows.
- [ ] Chuẩn bị release checklist và tài liệu vận hành.

## Nhật ký cập nhật

| Ngày | Hạng mục | Cập nhật | Người thực hiện |
| --- | --- | --- | --- |
| 2026-07-18 | Khởi tạo theo dõi tiến độ | Tạo checklist từ baseline tài liệu; chưa đánh dấu hoàn thành hạng mục nghiệp vụ. | Copilot |
| 2026-07-18 | Làm mới giao diện public | Đổi nhận diện thành “Hôm nay ăn gì”, thiết kế lại màn recommendation theo phong cách ẩm thực Việt trẻ trung và mobile-first. | Copilot |