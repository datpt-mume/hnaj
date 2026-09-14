# 11: Admin places — show/update/soft-delete (#33–35) — đóng C8

**What to build:** Admin xem chi tiết place, sửa place (gồm open hours với `day_of_week` giới hạn 2..8), và soft-delete place. Soft-delete: dữ liệu liên quan vẫn truy cập được ở trạng thái làm mờ; không chỉnh sửa/xóa/thêm mới trên place đã xóa mềm.

**Blocked by:** 10 (Admin places — list/create/queue)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test show/update/destroy từ `AdminPlace*Test` Laravel trước (red → green)
- [ ] Update: open hours validate `day_of_week` 2..8 (C8) — regression test riêng cho biên 1/9
- [ ] PATCH từng phần không phá field không gửi; validation 422 khớp Laravel
- [ ] Soft-delete: place biến khỏi list public/admin mặc định; dữ liệu liên quan vẫn đọc được làm mờ; thao tác trên place đã xóa bị chặn khớp Laravel
- [ ] Soft-delete KHÔNG xóa dữ liệu con (khác hard-delete); transaction cho update nhiều bảng
- [ ] Suite đầy đủ xanh trong Docker; matrix rows #33–35 `✅`
