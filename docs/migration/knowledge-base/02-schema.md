# 02 — Database Schema (nền cho Flyway)

> **Phase 1 (2026-09-05):** Toàn bộ schema đã được port thành 1 file Flyway baseline
> [`hnaj-be-java/src/main/resources/db/migration/V1__baseline_schema.sql`](../../../hnaj-be-java/src/main/resources/db/migration/V1__baseline_schema.sql) (603 dòng, 27 bảng).
> Tham số kỹ thuật: MySQL 8.x, charset `utf8mb4`, engine InnoDB, FK `ON DELETE RESTRICT`
> (ngoại trừ `review_images`/`comment_images` dùng `CASCADE` đúng theo migration 000027/000028).
> DB mặc định: `hnaj_java` (app) / `hnaj_java_test` (test profile).

> Xem [`07-open-questions.md`](./07-open-questions.md) mục Q1–Q13 cho quyết định.

> Nguồn: 38 migration trong [`hnaj-be/database/migrations/`](../../../hnaj-be/database/migrations/).
> Spring Boot dùng Flyway tái tạo schema này trên **database riêng** (`hnaj_java`), không đụng `hnaj` hiện tại.
> Kiểu Laravel → MySQL mapping chuẩn giữ nguyên.

## users

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| id | BIGINT PK AUTO | |
| name | VARCHAR(255) | NOT NULL |
| username | VARCHAR(50) | UNIQUE NOT NULL — chỉ `[a-z0-9._]`, không bắt đầu/kết thúc bằng `.`/`_` |
| email | VARCHAR(255) | UNIQUE NOT NULL |
| email_verified_at | DATETIME | NULL |
| google_id | VARCHAR(64) | UNIQUE NULL |
| avatar_url | VARCHAR(255) | NULL |
| password | VARCHAR(255) | NOT NULL (bcrypt rounds=12) |
| status | VARCHAR(255) | DEFAULT 'active', INDEX (enum: active/suspended/disabled) |
| remember_token | VARCHAR(100) | NULL |
| created_at / updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | NULL (soft delete) |

`password_reset_tokens`: email PK, token, created_at. `sessions`: id PK, user_id, ip_address, user_agent, payload, last_activity.

## roles & user_roles

- `roles`: id, name (UNIQUE: `user`,`sub_admin`,`admin`), description, timestamps.
- `user_roles`: id, user_id FK, role_id FK, assigned_by FK NULL, assigned_at DATETIME, timestamps. UNIQUE(user_id, role_id). Tất cả FK `restrictOnDelete`.

## place_opening_hours

id, place_id FK, `day_of_week` TINYINT UNSIGNED (**quy ước 2=T2…7=T7, 8=CN — KHÔNG phải 0..6**), `schedule_type` (enum: always_open/closed/hours), opens_at TIME NULL, closes_at TIME NULL, timestamps, INDEX(place_id, day_of_week).
> Lưu ý: cột `crosses_midnight` đã bị DROP ở migration 000026. Không tạo lại.

## places

id, name, address_text TEXT, google_place_id VARCHAR UNIQUE NULL, phone NULL, website_url TEXT NULL, google_maps_url TEXT NOT NULL, district_id FK, category_id FK (cả 2 `restrictOnDelete`), latitude/longitude DECIMAL(10,7), min_price/max_price BIGINT UNSIGNED NULL, `rating` DECIMAL(2,1) DEFAULT 5.0 (INDEX), description TEXT NULL, thumbnail_image_id NULL (FK sau này), status DEFAULT 'active' (INDEX: active/hidden), **is_verified BOOLEAN DEFAULT FALSE** (INDEX, INDEX(is_verified,id)), created_by FK NULL, timestamps, deleted_at. Indexes: district_id, category_id, min_price, max_price, (latitude,longitude).
> Check constraint `rating` (migration 000033): xem file migration gốc khi viết Flyway.

## Taxonomy: districts / categories / tags

- `districts`: id, name UNIQUE, code NULL, status DEFAULT 'active' INDEX, timestamps.
- `categories`: id, name, slug UNIQUE, status DEFAULT 'active' INDEX, timestamps, deleted_at.
- `tags`: id, name, slug UNIQUE, status DEFAULT 'active' INDEX, timestamps, deleted_at.
- Bảng `category_tags` đã bị DROP (migration 000029). Không tạo.

## place_images

id, place_id FK, uploaded_by FK NULL, image_url TEXT, alt_text NULL, is_visible BOOL DEFAULT TRUE INDEX, timestamps, deleted_at, INDEX(place_id).
- FK: `places.thumbnail_image_id` → `place_images.id` (migration 000025).

## place_tags

id, place_id FK, tag_id FK, timestamps, UNIQUE(place_id,tag_id), INDEX(tag_id).

## place_managers

id, place_id FK, user_id FK, assigned_by FK, assigned_at DATETIME, revoked_at NULL, timestamps. UNIQUE(place_id,user_id), INDEX(user_id,place_id), INDEX(revoked_at).

## bookmarks

id, user_id FK, place_id FK, timestamps. UNIQUE(user_id,place_id), INDEX(user_id), INDEX(place_id).

## visit_events (auth user "Đi tới đó")

id, user_id FK, place_id FK, `visit_date` DATE (theo Asia/Ho_Chi_Minh), visited_at DATETIME (UTC), source VARCHAR NULL (in: discovery/detail/search/bookmarks/history), timestamps. UNIQUE(user_id,place_id,visit_date) — **idempotent**, INDEX(place_id,visit_date), INDEX(user_id,visited_at).

## anonymous_visit_events (guest)

id, place_id FK, `anonymous_key_hash` VARCHAR(128) (SHA-256 của X-Anonymous-Id — KHÔNG lưu IP/plaintext), visit_date DATE, visited_at DATETIME, source NULL, timestamps. UNIQUE(place_id,anonymous_key_hash,visit_date), INDEX(place_id,visit_date).

## Token tables

- `email_verification_tokens`: id, user_id FK, token_hash UNIQUE (SHA-256), expires_at DATETIME INDEX, used_at NULL, created_at, INDEX(user_id).
- `account_setup_tokens`: cấu trúc y hệt email_verification_tokens.
- `personal_access_tokens` (Sanctum): id, tokenable_type+tokenable_id (morphs), name TEXT, token VARCHAR(64) UNIQUE (SHA-256), abilities TEXT NULL, last_used_at NULL, expires_at NULL INDEX, timestamps.

## manager_applications

id, place_request_id FK NULL, **place_id FK NULL** (thêm migration 000001 — cho phép xin quản lý place hiện hữu), **user_id FK NULL** (người xin đã là user), email INDEX, representative_name, proof_reference TEXT NULL, status DEFAULT 'pending' INDEX, approved_user_id FK NULL, reviewed_by FK NULL, reviewed_at NULL, review_reason TEXT NULL, timestamps. INDEX(place_id), INDEX(user_id).

## Các bảng có model nhưng CHƯA có endpoint (planned)

`place_requests`, `reviews`, `review_images`, `comments`, `comment_images`, `promotion_requests`, `moderation_actions`, `notification_deliveries`.
- **Vẫn tạo schema Flyway đầy đủ** (models đã tồn tại), nhưng chưa port business logic — các domain này trạng thái `planned` trong [`project-progress.md`](../../../plans/project-progress.md:31).
- `ContentAndScheduleModelTest` là regression cho nhóm này — port test để bảo vệ schema.

## Lưu ý Flyway

1. Mỗi file migration Laravel → 1 hoặc nhiều `V{n}__*.sql` Flyway, giữ thứ tự.
2. Có thể gộp thành 1 baseline `V1__schema.sql` vì DB mới — quy định ở Phase 1.
3. Enum Laravel (status) lưu MySQL dạng VARCHAR, không phải DB enum — giữ nguyên.
4. Review/Comment FK cascade: kiểm tra `ContentAndScheduleModelTest` trước khi quyết định onDelete.
