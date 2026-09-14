# 10: Admin places — list/create/queue (#30–32)

**What to build:** Admin xem danh sách place có filter + status, tạo place mới, và xem hàng đợi xác minh qua `GET/POST /api/admin/places` + `GET /api/admin/places/verification-queue` — nửa đọc/tạo của bộ quản trị place.

**Blocked by:** 01 (Nền tảng đọc public), 09 (Admin tag create — pattern admin CRUD + taxonomy sẵn)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port các test list/store/queue từ nhóm `AdminPlace*Test` Laravel trước (red → green)
- [ ] List: filter + status + phân trang meta chuẩn; queue: chỉ place cần verify
- [ ] Create: validation khớp Laravel Form Request; place mới ở status khớp Laravel
- [ ] Chỉ admin; 403 cho role khác; throttle 30–60/phút theo inventory
- [ ] Suite đầy đủ xanh trong Docker; matrix rows #30–32 `✅`
