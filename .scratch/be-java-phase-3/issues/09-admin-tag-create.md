# 09: Admin tag create (#26)

**What to build:** Admin tạo tag mới qua `POST /api/admin/tags` để taxonomy phát triển — endpoint đơn giản nhất nhóm admin, dùng làm warm-up kiểm tra pattern admin CRUD trước khi vào nhóm admin places lớn.

**Blocked by:** 01 (Nền tảng đọc public)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `AdminTagCreateTest` trước ở HTTP seam (red → green)
- [ ] Chỉ admin được tạo (role từ DB mỗi request); user/sub_admin → 403
- [ ] Validation (tên rỗng/trùng) → 422 khớp Laravel; tag mới xuất hiện trong discovery metadata
- [ ] Tag không được hard-delete qua endpoint này (chỉ path có trong 39 endpoint)
- [ ] Suite đầy đủ xanh trong Docker; matrix row #26 `✅`
