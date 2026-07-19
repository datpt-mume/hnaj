# API Changelog

## Unreleased

### Added

- Đặt baseline contract `/api/v1` cho HNaj.
- Quy ước response envelope, error codes, Sanctum cookie/session và idempotency.
- Định nghĩa sơ bộ recommendation, tags, place detail, favorites, reviews và auth.
- Bổ sung CSRF bootstrap, password reset, account deletion, review report và owner response.
- Thêm contract Admin CSV import cho preview, confirm và theo dõi trạng thái batch.
- Triển khai Sanctum SPA cookie/session, CSRF bootstrap và protected Admin import routes.
- Hiện thực hóa `/auth/register`, `/auth/login`, `/auth/me` và `/auth/logout` bằng session auth.
- Thêm public discovery options `/categories`, `/districts` và `/tags` với related-tag ranking.

### Changed

- Đây là baseline tài liệu trước khi các endpoint nghiệp vụ được triển khai.
- Recommendation endpoint baseline đã được nối vào Laravel scaffold và FE demo gửi request theo contract.
- Recommendation hỗ trợ hard filter theo quận, category, khoảng giá; match tag bằng slug, giới hạn đúng số kết quả và trả fallback metadata thực tế.

Mọi thay đổi API client-observable phải được ghi trong file này cùng với thay đổi OpenAPI.
