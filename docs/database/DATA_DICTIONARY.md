# Database Data Dictionary (Target)

Đây là data dictionary cho thiết kế mục tiêu. Tên bảng/cột là proposed cho phase schema; migration Laravel sẽ là nguồn chuẩn khi implementation bắt đầu.

## Identity and access

| Entity | Purpose | Required rules |
|---|---|---|
| `users` | User, owner, editor, admin identity | Email unique; status; PII deletion/anonymization policy. |
| `roles` | RBAC role catalog | Unique role name. |
| `user_roles` | Many-to-many role assignment | Unique `(user_id, role_id)`; internal roles assigned through admin invite. |

A user may hold multiple roles. Owner/editor/admin accounts are invited by an admin. A place may have multiple owner/manager assignments.

## Places and discovery

| Entity | Purpose | Required rules |
|---|---|---|
| `administrative_areas` | Hà Nội district/ward taxonomy used by CSV AI classification | `type` is `district` or `ward`; wards reference a district through `parent_id`; active IDs only are accepted from AI. |

| Entity | Purpose | Required rules |
|---|---|---|
| `places` | One branch/location per record | ULID public ID; unique slug; WGS 84 coordinates; nullable `district_id` and `ward_id` FKs; price integer VND; publication status; soft delete. |
| `categories` | High-level place classification | One active primary category per place; unique slug; seeded system taxonomy. |
| `tags` | Public filter taxonomy | Unique slug; group; icon/emoji; sort order; active/published status. |
| `place_tag` | Place/tag relation | Unique `(place_id, tag_id)`. |
| `place_external_ids` | Provider identifiers used by import deduplication | Unique `(provider, external_id)` and one identifier per provider/place; optional normalized fingerprint. |
| `media` | Place/review images | Storage abstraction path/URL metadata; cover and sort order; no binary in DB. |
| `opening_hours` | Weekly opening schedule | Weekday and local times; recommendation can filter currently open. |

The recommendation price rule uses the average of `price_min` and `price_max`. Coordinates and radius are measured in kilometers. `places.category_id` is the primary category; tags are many-to-many. The current import taxonomy is seeded by `TaxonomySeeder` and AI may only return active IDs from these tables.

## User behavior

| Entity | Purpose | Required rules |
|---|---|---|
| `favorites` | User saved places | Unique `(user_id, place_id)`; PUT/DELETE API is idempotent. |
| `recommendation_history` | 24-hour recommendation dedup | Supports authenticated `user_id` or anonymous functional `anonymous_id`; retain 30 days. |
| `reviews` | User rating and text | Authenticated users only; one review/place; rating 1-5; auto-publish plus report/moderation. |
| `review_media` | Review image relation | References storage media; upload limits remain TBD. |
| `review_tags` | Experience tags on reviews | Unique review/tag relation. |
| `review_reports` | Abuse/moderation report | Reporter, reason, status and moderation timestamps. |
| `owner_responses` | Place owner response | At most one response per review; owner must be assigned to the place. |

Ratings displayed and used for ranking should use Bayesian/weighted aggregation rather than an unadjusted average. Formula and configurable values belong in the recommendation policy.

## Analytics and operations

| Entity | Purpose | Required rules |
|---|---|---|
| `analytics_events` | Allowlisted impression/click/favorite/empty/bounce and recommendation events | Anonymous ID or user ID; consent-aware; raw retention 12 months. |
| `invites` | Admin-created internal account activation | Single-use, expiring token; exact TTL TBD. |
| `idempotency_keys` | De-duplicate important create/import operations | Scope by actor/endpoint/key; TTL TBD. |

## Status values

Target content workflow: `draft`, `pending_review`, `published`, `rejected`, `archived`. Public recommendation only considers `published`. Exact enum implementation is deferred to migrations.

## Index intent

At minimum, implementation should evaluate indexes for place status/soft delete, coordinates, price bounds, slug, tag slug/status, favorite uniqueness, review uniqueness, history lookup by actor/place/time, analytics event/time/name and invitation expiry. Exact composite indexes require MySQL 8 query plans.
