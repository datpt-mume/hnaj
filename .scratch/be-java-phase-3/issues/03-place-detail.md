# 03: Place detail + is_bookmarked (#5)

**What to build:** Mở trang chi tiết place: guest xem được hours/contact/media/gallery qua `GET /api/places/{place}`; user đăng nhập gửi bearer token thì response có thêm `is_bookmarked`. `{place}` chỉ nhận số nguyên (whereNumber parity); guest không có field `is_bookmarked`.

**Blocked by:** 01 (Nền tảng đọc public + Discovery metadata)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `PlaceDetailTest` trước ở HTTP seam (red → green)
- [ ] Response shape khớp Laravel Resource: hours, contact, media, placeholder review; snake_case nhất quán
- [ ] Guest: không có `is_bookmarked`; user bearer hợp lệ: có `is_bookmarked` (dùng access-token infra Phase 2)
- [ ] `{place}` non-numeric → 404/404-envelope khớp Laravel; place không tồn tại → 404 chuẩn
- [ ] Optional-auth hoạt động đúng: token hỏng/không gửi vẫn xem được public detail
- [ ] Suite đầy đủ xanh trong Docker; matrix row #5 `✅`
