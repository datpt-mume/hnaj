# 05: Bookmarks — list/create/delete (#18–20)

**What to build:** User đăng nhập lưu place yêu thích: xem danh sách bookmark có phân trang, thêm bookmark, xóa bookmark qua `GET/POST /api/bookmarks` và `DELETE /api/bookmarks/{place}` — danh sách bookmark của FE đọc từ backend Java vẫn y như Laravel.

**Blocked by:** 01 (Nền tảng đọc public), 03 (Place detail — tái dùng shape place + is_bookmarked)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `BookmarkTest` trước ở HTTP seam (red → green)
- [ ] List có pagination meta chuẩn (C5 đã chốt ở ticket 02); chỉ thấy bookmark của chính mình
- [ ] Thêm bookmark: place phải tồn tại; bookmark trùng xử lý khớp Laravel (idempotent/lỗi theo contract KB 03§5)
- [ ] Xóa bookmark place chưa lưu → hành vi khớp Laravel; xóa thành công thì list cập nhật
- [ ] Không bearer/token hỏng → 401 UNAUTHENTICATED như Phase 2
- [ ] Throttle từng endpoint mirror inventory; suite đầy đủ xanh trong Docker; matrix rows #18–20 `✅`
