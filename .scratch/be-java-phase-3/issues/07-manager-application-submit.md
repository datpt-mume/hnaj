# 07: Manager application — submit (#17)

**What to build:** User đăng nhập nộp đơn xin làm manager cho một place hiện hữu qua `POST /api/manager-applications` — đơn được lưu và admin sau đó thấy được trong hàng đợi (flow đầy đủ nối tiếp ở ticket 08).

**Blocked by:** 01 (Nền tảng đọc public)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port phần submit của `AdminManagerApplicationTest` trước ở HTTP seam (red → green)
- [ ] Chỉ user đăng nhập nộp được; place phải tồn tại và hiện hữu; 401/404/422 khớp mapping
- [ ] Trạng thái khởi tạo khớp Laravel (pending); một đơn đang mở/user/place theo rule KB 03§7
- [ ] Payload + envelope khớp contract; throttle mirror inventory
- [ ] Suite đầy đủ xanh trong Docker; matrix row #17 `✅`
