# Spec: BE Java Phase 3 — Port các domain còn lại (24/39 endpoint)

Status: ready-for-agent
Labels: ready-for-agent

> Tổng hợp từ kế hoạch Phase 3 đã duyệt (trao đổi 2026-09-12) + knowledge base `docs/migration/knowledge-base/`. Phase 2 (auth, 15 endpoint) đã hoàn tất với 51/51 test PASS trên MySQL thật.

## Problem Statement

Backend Java (`hnaj-be-java`) mới port được nhóm auth (15/39 endpoint). 24 endpoint còn lại — taxonomy, places public, discovery, bookmarks, visits, manager applications, admin tags, admin places + managers — vẫn chỉ chạy trên Laravel. Backend Laravel đang là runtime duy nhất phục vụ các tính năng này; không thể cutover frontend sang Java (điều kiện Q15: matrix mục A phải 100% done), và hai runtime dần lệch nhau khi Laravel tiếp tục phát triển.

## Solution

Port toàn bộ 24 endpoint còn lại sang Spring Boot, giữ nguyên API contract (envelope, status code, payload shape, throttle) để frontend không phải đổi gì. Triển khai theo 5 sub-phase phụ thuộc: 3A nền tảng đọc public → 3B bookmarks/visits → 3C manager applications + tag → 3D admin places + managers → 3E chốt hạ (fixture-sharing parity Q14, rà matrix, checklist cutover). Mỗi endpoint chỉ được tính done khi test Spring pass VÀ parity với Laravel đạt theo traceability matrix.

## User Stories

1. As a guest, I want to fetch discovery metadata (active categories/districts/tags), so that filter UI shows correct options without hard-coded taxonomy.
2. As a guest, I want to search places with keyword, filters and pagination, so that I find relevant places quickly.
3. As a guest, I want to view place detail with hours, contact and media, so that I can decide where to go.
4. As a guest, I want a random discovery recommendation backed by the same scoring as Laravel, so that suggestions feel consistent across runtimes.
5. As a signed-in user, I want `is_bookmarked` included in place detail when I send my bearer token, so that I see my saved status on the page.
6. As a guest, I want place detail without bookmark field, so that the response stays consistent with the Laravel contract.
7. As a signed-in user, I want to list my bookmarks with pagination, so that I can revisit saved places.
8. As a signed-in user, I want to bookmark a place, so that I can save it for later.
9. As a signed-in user, I want to remove a bookmark, so that my list stays accurate.
10. As a signed-in user, I want recording a visit to be idempotent per place/date, so that double submissions do not duplicate history.
11. As a signed-in user, I want to list my visit history with unique places, so that I remember where I have been.
12. As a guest recording a visit, I want my identity stored as a SHA-256 hash without my IP, so that my privacy is protected.
13. As a signed-in user, I want to submit a manager application for an existing place, so that I can apply to manage it.
14. As an admin, I want to list manager applications, so that I can process them.
15. As an admin, I want to approve or reject an application with a reason, so that decisions are auditable and the applicant is notified.
16. As a user whose application was approved, I want the manager assignment and role grant to be atomic, so that I never end up half-assigned.
17. As an admin, I want to create a tag, so that taxonomy can grow.
18. As an admin, I want to list places with filters and status, so that I can moderate the inventory.
19. As an admin, I want to create a place, so that the inventory grows.
20. As an admin, I want a verification queue, so that I know which places need review.
21. As an admin, I want to view a place detail, so that I can inspect it before acting.
22. As an admin, I want to update a place including open hours with `day_of_week` restricted to 2..8, so that data stays correct.
23. As an admin, I want to soft-delete a place, so that content can be hidden reversibly with related data still reachable in a blurred state.
24. As an admin, I want to hard-delete a place with full cascade in one transaction, so that destructive cleanup cannot leave orphan data.
25. As an admin, I want to list and add managers of a place, so that delegation works.
26. As an admin, I want to resend a manager invite, so that an expired setup can be completed.
27. As an admin, I want to revoke a manager, so that access ends immediately on the next request (roles read from DB per request).
28. As an operator, I want per-endpoint rate limits mirrored from Laravel (throttle values in the endpoint inventory), so that abuse protection has parity.
29. As an operator, I want every success/error response to follow the common envelope, so that the frontend consumes one format.
30. As an operator, I want list responses to carry pagination meta identical to Laravel's paginator, so that frontend pagination does not break.
31. As a frontend developer, I want the API contract unchanged, so that no frontend change is needed.
32. As a maintainer, I want each traceability matrix row flipped to done only when Laravel/Knowledge/Spring/Test are all green, so that no endpoint is missed.
33. As a maintainer, I want a fixture-sharing parity test design for dual-runtime (Q14) delivered in Phase 3, so that cutover confidence is evidence-based.
34. As a maintainer, I want the 51 Phase 2 auth tests to stay green after every sub-phase, so that auth does not regress.
35. As a maintainer, I want progress recorded after each sub-phase, so that anyone can resume work from the ledger.

## Implementation Decisions

- **Sub-phase ordering (dependency-ordered):** 3A taxonomy/places-public/discovery read paths (entities + repositories for place, category, district, tag, open hours, media); 3B bookmarks + visits (closes cross-cutting C5 pagination meta, C9 visit timezone, C10 anonymous hash); 3C manager applications + admin tag; 3D admin places CRUD + verification queue + soft/hard delete + place managers (closes C8 day_of_week); 3E parity fixtures + matrix review + cutover checklist.
- **Layering:** mirror Phase 2 — Controller → Service/Action → Repository → Entity, with response serialization matching Laravel API Resources (snake_case fields, e.g. `full_name`, `email_verified`, `avatar_url` precedent).
- **Cross-cutting reuse:** common envelope advice, bearer-token filter, role interceptor reading roles from DB per request, and the existing rate-limit service are reused as-is; only new throttle entries are added per the endpoint inventory. No new auth surfaces — opaque access token infra from Phase 2 (Q7/Q17) is shared.
- **Schema:** no new Flyway migrations expected — V1 baseline (Q2, Q6 `ddl-auto=none`) already covers these domains. If a schema gap is discovered, stop and get user approval before adding a migration.
- **Discovery random:** the scorer must replicate Laravel's scoring weights and ordering exactly; randomized selection behavior must match observable Laravel outcomes.
- **Pagination:** list endpoints replicate Laravel paginator meta shape (C5).
- **Visits:** `visit_date` resolved in Asia/Ho_Chi_Minh; anonymous identity is a SHA-256 hash, raw IP never stored (C9/C10).
- **Admin places:** soft-delete keeps related data reachable in blurred state; hard-delete cascades bookmark, visit, review, comment, image and related history in a single transaction. Destructive operations touch only the test database.
- **Manager applications:** approval performs assignment + role grant in one transaction; rejection requires a reason; mail goes through the existing log-mode mail setup (same as Phase 2).
- **Tags/categories/districts:** no hard-delete; only create/update/status paths that exist among the 39 endpoints.
- **Explicit non-ports:** PlaceImport stays Laravel-only (Q10); domains without Laravel endpoints (planned schemas, notifications, review/comment, reports, requests, promotions) are not invented here (Q11, Q18, Q19).

## Testing Decisions

- **A good test asserts external behavior only:** HTTP method/path, status code, envelope, payload shape, rate-limit headers, and observable side effects (response of follow-up calls or DB state) — never internal class wiring.
- **Primary seam (existing, reused):** MockMvc at the HTTP boundary against real MySQL 8.4 in Docker (test profile, test database from Phase 2), exactly the seam `AuthFlowIntegrationTest` uses. Every ticket cuts through this one seam.
- **Narrow unit seam (existing precedent):** pure deterministic logic only (scorer, validators, date handling) — same rationale as the Phase 2 unit tests for input/rate-limit/username/token logic.
- **Prior art to port:** Laravel test catalog entries for these domains — discovery metadata, discovery random, place scorer, place search, place detail, bookmarks, visits, manager applications, admin tags, admin places — each ported as Spring tests at the HTTP seam (TDD: port the failing test first).
- **Contract checks:** error mapping (401/403/404/409/422/429/500) and envelope shape asserted at the boundary, same as the Phase 2 security contract tests; rate-limit boundary tests follow the login-throttle precedent.
- **Regression guard:** the full suite (including 51 Phase 2 tests) must pass via Docker Compose verification after each sub-phase; verification follows the repo's mandatory in-Docker testing rule.

## Out of Scope

- Any change to the Laravel backend, the frontend, or the main Compose file.
- FE cutover itself (Q15) — only a cutover checklist is produced in 3E.
- PlaceImport tooling (Q10).
- Domains with no Laravel endpoints: review/comment, reports, place requests, notifications (Q18), promotions, image storage (Q19).
- New dependencies, new migrations, or contract changes without explicit user approval.
- Git commits/push — commit policy is manual; skills stop at the diff with a suggested commit message.

## Further Notes

- Tickets will be derived via `/to-tickets` as tracer-bullet vertical slices with blocking edges matching the sub-phase dependency order; each ticket names the Laravel tests it ports.
- Traceability matrix rule remains: a row is `done` only when Laravel source, knowledge doc, Spring implementation, and Spring test are all verified.
- Known environment quirks: run Compose from the docker directory with its env file; buildx fallback flag may be needed as in Phase 2.
- Progress ledger is updated when starting, after each sub-phase, and before handover.
