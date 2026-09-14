# 06: Visits — record + history (#21–22)

**What to build:** User ghi nhận "đã đến place" và xem lịch sử unique-place qua `POST /api/visits` (public optional-auth, idempotent theo place/ngày) và `GET /api/visits` (auth). Guest ghi visit được lưu ẩn danh chỉ bằng SHA-256 hash, không lưu IP.

**Blocked by:** 01 (Nền tảng đọc public)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `VisitTest` trước ở HTTP seam (red → green)
- [ ] `visit_date` resolve theo Asia/Ho_Chi_Minh (C9) — test chạy qua midnight timezone
- [ ] Anonymous identity: SHA-256 hash, raw IP không bao giờ lưu (C10) — assert DB state
- [ ] Idempotent: ghi 2 lần cùng place/ngày không nhân đôi bản ghi
- [ ] History: unique place, phân trang meta chuẩn; user khác không thấy visit của nhau
- [ ] Throttle mirror inventory; suite đầy đủ xanh trong Docker; matrix rows #21–22 `✅`
