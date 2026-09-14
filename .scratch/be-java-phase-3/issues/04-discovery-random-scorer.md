# 04: Discovery random + PlaceScorer (#3)

**What to build:** Bấm "gợi ý ngẫu nhiên" trên app gọi `POST /api/discovery/random` ở backend Java và nhận place được chọn theo đúng logic chấm điểm của Laravel (PlaceScorer + SelectBestPlace) — gợi ý nhất quán giữa 2 runtime.

**Blocked by:** 01 (Nền tảng đọc public + Discovery metadata)

**Status:** ready-for-agent
**Labels:** ready-for-agent

- [ ] Port test `DiscoveryRandomTest` + `PlaceScorerTest` (unit thuần cho scorer — seam unit có precedents Phase 2) trước
- [ ] Scorer replicate đúng trọng số và thứ tự chấm điểm Laravel; chọn place theo observable outcome Laravel
- [ ] Throttle 30/phút; envelope + payload khớp contract
- [ ] Edge case: không có place thỏa điều kiện → hành vi khớp Laravel (empty/422 theo contract KB 03§3)
- [ ] Suite đầy đủ xanh trong Docker; matrix row #3 `✅`
