# 08: Manager application — admin list/approve/reject (#27–29)

**What to build:** Admin mở hàng đợi đơn xin quản lý, duyệt hoặc từ chối kèm lý do qua `GET /api/admin/manager-applications`, `POST .../{id}/approve`, `POST .../{id}/reject`. Duyệt xong: assignment + cấp role manager diễn ra atomic trong một transaction; người nộp được thông báo qua mail (log-mode sẵn có) và notification flow Laravel parity.

**Blocked by:** 07 (Manager application — submit)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port phần admin của `AdminManagerApplicationTest` trước (red → green)
- [ ] List phân trang, filter theo trạng thái khớp Laravel
- [ ] Approve: assignment + role grant trong MỘT transaction — test rollback khi một nửa fail
- [ ] Approve/reject đều cần lý do; status transition pending → approved/rejected khớp Laravel
- [ ] Tác dụng thực tế: user được duyệt có role manager — role đọc DB mỗi request (C3) thấy ngay ở request kế
- [ ] Không phải admin → 403; suite đầy đủ xanh trong Docker; matrix rows #27–29 `✅`
