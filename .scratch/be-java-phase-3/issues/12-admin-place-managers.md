# 12: Admin place managers (#36–39)

**What to build:** Admin xem, bổ sung, gửi lại lời mời và thu hồi manager của một place qua `GET/POST /api/admin/places/{place}/managers`, `POST .../{user}/resend`, `DELETE .../{user}`. Thu hồi có tác dụng ngay ở request kế (role đọc DB mỗi request); gửi lời mời đi mail setup như Laravel.

**Blocked by:** 11 (Admin places — show/update/soft-delete), 08 (Manager application — admin review — tái dùng flow mail + role grant)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port các test managers từ `AdminPlace*Test` Laravel trước (red → green)
- [ ] Add manager: user tồn tại; assignment + cấp role trong transaction; mail setup gửi qua mail log-mode
- [ ] Resend: lời mời mới cho user chưa hoàn tất setup
- [ ] Revoke: thu hồi assignment + role; request kế tiếp của user này mất quyền manager (assert qua endpoint auth có sẵn)
- [ ] Chỉ admin; place không tồn tại → 404; throttle theo inventory
- [ ] Suite đầy đủ xanh trong Docker; matrix rows #36–39 `✅`
