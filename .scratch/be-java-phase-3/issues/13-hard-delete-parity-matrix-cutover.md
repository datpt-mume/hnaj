# 13: Hard-delete cascade (#35 note) + fixture-sharing parity (Q14) + chốt matrix/cutover

**What to build:** Đóng nốt Phase 3: (1) hard-delete place xóa cascade bookmark/visit/review/comment/ảnh + lịch sử liên quan trong MỘT transaction (destructive — chỉ chạy trên DB test); (2) thiết kế fixture-sharing parity test dual-runtime (Q14): bộ fixture chung cho cả Laravel và Java asserting cùng input → cùng output; (3) rà toàn bộ matrix mục A/B/C — mọi row 39 endpoint về `✅`, đóng C5/C8/C9/C10 còn dở; (4) checklist cutover FE (Q15) cho Phase sau.

**Blocked by:** 11 (Admin places — show/update/soft-delete — hard-delete cùng cụm), 12 (Admin place managers), 02 (Place search — C5), 06 (Visits — C9/C10), 04 (Discovery random — scorer)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Hard-delete: cascade đủ bảng con trong 1 transaction; test rollback khi cascade fail giữa chừng; authorization mạnh nhất (chỉ admin)
- [ ] Hard-delete là thao tác destructive: chỉ chạy trên DB test; không đụng volume/data thật
- [ ] Thiết kế fixture-sharing Q14 thành văn bản: cấu trúc fixture, cách cả 2 runtime dùng chung, cách assert parity — chưa cần chạy dual-runtime thật
- [ ] Matrix mục A: 39/39 `✅` với cả 4 cột xanh; mục B; mục C: C5/C8/C9/C10 đóng, C11 đã có, multi-thread test được đánh giá
- [ ] Cập nhật KB (00/06/07) + docs API contract đồng bộ với implementation Spring
- [ ] Checklist cutover FE (Q15) soạn xong; 51 test Phase 2 + toàn bộ Phase 3 xanh trong Docker
- [ ] `plans/project-progress.md` cập nhật trạng thái cuối Phase 3 + rủi ro còn lại; dừng ở diff (commit manual)
