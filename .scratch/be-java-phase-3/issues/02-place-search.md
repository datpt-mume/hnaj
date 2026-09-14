# 02: Place search (#4)

**What to build:** Guest gõ từ khóa, lọc và phân trang trên `GET /api/places/search` và nhận kết quả + pagination meta giống hệt Laravel — FE không phải đổi cách đọc page/meta. Đóng cross-cutting **C5** (pagination meta format).

**Blocked by:** 01 (Nền tảng đọc public + Discovery metadata)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `PlaceSearchTest` (Laravel) thành test Spring ở HTTP seam trước (red), sau đó code đến green
- [ ] Keyword, filter, sort, pagination khớp contract Laravel; meta paginator giống hệt shape Laravel (C5)
- [ ] Throttle 60/phút; envelope success/error chuẩn; 422 validation khớp mapping hiện có
- [ ] Kết quả rỗng trả empty list + meta hợp lệ (không lỗi)
- [ ] Suite đầy đủ xanh trong Docker; matrix row #4 `✅` theo rule 4 cột
