# 01: Nền tảng đọc public + Discovery metadata (#2)

**What to build:** Người dùng mở app, filter UI hiển thị đúng taxonomy active (category/district/tag) lấy từ `GET /api/meta/discovery` của backend Java — giống hệt response Laravel, không hard-code phía FE. Ticket này đồng thời dựng nền tảng chung mà mọi ticket sau dùng: entity + repository đọc cho place, category, district, tag, open hours, media từ schema Flyway V1 hiện có (không migration mới).

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Entity + repository đọc cho place, category, district, tag, open hours, media có sẵn cho các ticket sau
- [ ] `GET /api/meta/discovery` trả chỉ taxonomy active, envelope + snake_case khớp Laravel (port test `DiscoveryMetadataTest` trước — TDD red → green)
- [ ] Throttle 60/phút mirror Laravel; 429 kèm envelope lỗi + Retry-After như Phase 2
- [ ] Toàn bộ suite (gồm 51 test Phase 2 auth) xanh khi chạy trong Docker Compose trên MySQL thật
- [ ] Matrix row #2 chuyển `✅` chỉ khi 4 cột Laravel/Knowledge/Spring/Test đều xanh
- [ ] Không thêm migration, không sửa Laravel/FE/`compose.yaml`; dừng ở diff + gợi ý commit message (commit policy manual)
