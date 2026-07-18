# HNaj Requirements Traceability

Tài liệu này khóa quan hệ giữa yêu cầu nghiệp vụ, HTTP contract và database target. `Target` không có nghĩa là đã triển khai trong scaffold.

## Status legend

- `Defined`: đã có nguồn chuẩn đủ để triển khai.
- `Partial`: có baseline nhưng còn operation/field/decision cần chốt.
- `TBD`: chưa được quyết định; không được tự phát minh khi code.
- `Not started`: chưa có code implementation.

## Traceability matrix

| Requirement | Business source | HTTP contract | Database target | Status |
|---|---|---|---|---|
| Recommendation by location/radius/budget/tags | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#5-recommendation) + [`recommendation-policy.md`](api/recommendation-policy.md) | [`POST /recommendations`](api/openapi.yaml) | `places`, `tags`, `recommendation_history` in [`ERD.md`](database/ERD.md) | Defined / Not started |
| 24-hour dedup for user and guest | [`recommendation-policy.md`](api/recommendation-policy.md) | Recommendation metadata and anonymous session behavior | `recommendation_history` + retention in [`DATA_DICTIONARY.md`](database/DATA_DICTIONARY.md) | Defined / Not started |
| Public tags and place detail | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#6-public-user-features) | `GET /tags`, `GET /places/{slug}` | `places`, `tags`, `place_tag` | Defined / Not started |
| Email auth and Sanctum session | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#7-authentication-privacy-và-analytics) | CSRF, register, login, logout, me | `users` and auth package tables when implementation is approved | Partial / Not started |
| Password reset | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#7-authentication-privacy-và-analytics) | Password reset request/confirm | Laravel auth reset storage, exact implementation TBD | Partial / TBD |
| Google authentication | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#7-authentication-privacy-và-analytics) | No operation defined yet | Provider identity mapping TBD | TBD |
| Favorites | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#6-public-user-features) | Favorite PUT/DELETE/list | `favorites` | Defined / Not started |
| Reviews, reports and owner response | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#6-public-user-features) | Create/list/report/owner response | `reviews`, `review_reports`, `owner_responses`, media relations | Partial / Not started |
| Roles and invitations | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#3-actors-và-quyền) | Admin invite lifecycle not defined | `roles`, `user_roles`, `invites` | Partial / TBD |
| Place/tag/content administration | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#4-content-workflow-và-domain-model) | Admin/editor CRUD not defined | `places`, `tags`, workflow fields | Partial / TBD |
| CSV import | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#9-non-goals-và-tbd) | No operation defined | Import job/error tables TBD | TBD |
| Analytics | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#7-authentication-privacy-và-analytics) | No operation defined | `analytics_events` | Partial / Not started |
| Account deletion/privacy retention | [`PRD_TECH_SPEC.md`](PRD_TECH_SPEC.md#7-authentication-privacy-và-analytics) | `DELETE /auth/account` | User anonymization and retention rules | Partial / Not started |

## Implementation gate

Không bắt đầu một slice backend nếu bất kỳ điều kiện nào sau đây chưa đạt:

1. Path, method, auth, permission, request, response, error và idempotency đã có trong OpenAPI.
2. Nếu slice chạm database, migration target và database docs đã thống nhất.
3. Business behavior, fallback, status transition hoặc retention đã có trong PRD/policy.
4. Test expectation và MySQL-specific behavior đã được xác định.
5. API changelog được cập nhật cùng change.

## Known contract gaps

Các nhóm sau cần được chốt contract trước implementation tương ứng:

- Google OAuth callback/redirect và identity mapping.
- Admin invitation, activation, role assignment và place ownership assignment.
- Admin/editor/owner place, tag, amenity, media, opening-hours CRUD.
- Submit/approve/reject/archive/restore workflow.
- CSV import validation, commit, status và `Idempotency-Key` behavior.
- Analytics event ingestion và owner/system summary queries.
- Review update/delete, media upload và exact report reason catalog.

Các điểm này được giữ TBD có chủ ý. Không thêm route, field, table hoặc environment variable chỉ để làm ma trận trông đầy đủ.
