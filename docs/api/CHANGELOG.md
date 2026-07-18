# API Changelog

## Unreleased

### Added

- Đặt baseline contract `/api/v1` cho HNaj.
- Quy ước response envelope, error codes, Sanctum cookie/session và idempotency.
- Định nghĩa sơ bộ recommendation, tags, place detail, favorites, reviews và auth.
- Bổ sung CSRF bootstrap, password reset, account deletion, review report và owner response.

### Changed

- Đây là baseline tài liệu trước khi các endpoint nghiệp vụ được triển khai.
- Recommendation endpoint baseline đã được nối vào Laravel scaffold và FE demo gửi request theo contract.

Mọi thay đổi API client-observable phải được ghi trong file này cùng với thay đổi OpenAPI.
