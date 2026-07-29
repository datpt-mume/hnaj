# Migration checklist — HNAJ

- **Trạng thái:** Baseline đã triển khai, dùng để kiểm tra thay đổi migration tiếp theo
- **Phiên bản:** 0.2
- **Cập nhật:** 2026-07-28
- **Nguồn thiết kế:** [`docs/erd.md`](docs/erd.md:1)
- **Nguồn nghiệp vụ:** [`docs/prd.md`](docs/prd.md:1)
- **Database mục tiêu:** MySQL 8.4 theo cấu hình Docker hiện tại

> Tài liệu này ghi lại thứ tự, constraint và tiêu chí kiểm chứng migration. Migration xóa `category_tags` được bổ sung ngày 2026-07-28 nhưng chỉ được chạy trên từng môi trường sau khi có phê duyệt vận hành phù hợp.

## 1. Nguyên tắc triển khai

- Giữ package manager, framework và migration convention hiện tại của [`hnaj-be/composer.json`](../hnaj-be/composer.json:1).
- Dùng `BIGINT UNSIGNED` tự tăng cho khóa chính và foreign key theo convention Laravel hiện có.
- Dùng `VARCHAR` cho các cột `status`; giá trị hợp lệ được quản lý bằng constants hoặc PHP backed enum ở application, không dùng MySQL `ENUM`.
- Dùng transaction ở service/action cho nghiệp vụ nhiều bước; migration phải có `down()` rollback hợp lý.
- Không đưa password, token plaintext, secret hoặc dữ liệu production vào migration, seeder, factory hay fixture.
- Không tạo unique constraint theo tên hoặc tọa độ place vì application tự xử lý trùng.
- Không tạo bảng `menus`, `menu_sections`, `menu_items`, `menu_images` hoặc `place_request_images` trong MVP.
- Không tạo bảng `promotion_placements` trong MVP.
- Không sửa/xóa migration đã được dùng ở môi trường chia sẻ nếu chưa xác minh trạng thái triển khai; ưu tiên migration bổ sung.
- Không chạy lệnh destructive như `migrate:fresh`, `db:wipe`, truncate, drop schema hoặc xóa Docker volume.

## 2. Tiền điều kiện trước khi viết migration

- [ ] Xác nhận workspace không có thay đổi migration/domain ngoài phạm vi task.
- [ ] Đọc lại migration mặc định của Laravel tại [`hnaj-be/database/migrations/0001_01_01_000000_create_users_table.php`](../hnaj-be/database/migrations/0001_01_01_000000_create_users_table.php:1).
- [ ] Xác nhận cấu hình runtime dùng MySQL qua [`hnaj-docker/compose.yaml`](../hnaj-docker/compose.yaml:1), không dùng SQLite mặc định khi chạy Docker.
- [ ] Xác nhận phiên bản PHP/Laravel/MySQL từ manifest và Compose, không tự chọn version mới.
- [ ] Chốt tên bảng, tên cột, chiều dài `VARCHAR`, precision/scale và quy tắc nullability trước khi tạo file.
- [ ] Xác nhận không cần dependency/package mới.
- [ ] Xác nhận không cần thay đổi port, volume, network hoặc biến môi trường.

## 3. Thứ tự migration và dependency

Tên file migration thực tế sẽ dùng timestamp Laravel tại thời điểm triển khai. Các nhóm dưới đây là thứ tự logic, không phải tên file cố định.

### 3.1. Migration 01 — cập nhật `users`

**Mục tiêu:** chuẩn hóa tài khoản đã có trước khi tạo các bảng phụ thuộc `users`.

- [ ] Giữ các cột Laravel mặc định cần thiết: `id`, `name`, `email`, `password`, `remember_token`, timestamps.
- [ ] Bổ sung `status` dạng `VARCHAR`, mặc định theo quyết định application sau khi thống nhất state ban đầu.
- [ ] Giữ `email_verified_at` nếu luồng xác thực email sử dụng.
- [ ] Cân nhắc `deleted_at` theo policy account đã chốt; nếu dùng phải kiểm tra tác động tới unique email và authentication.
- [ ] Bảo đảm `email` có unique index theo ERD.
- [ ] Không thêm password vào bất kỳ application/request table nào.

**Rollback:** chỉ rollback các cột được migration này thêm; không xóa bảng `users` mặc định nếu migration đó đã tồn tại ở môi trường chia sẻ.

### 3.2. Migration 02 — `roles`

**Cột chính:**

- [ ] `id`
- [ ] `name` — unique; seed các role `user`, `sub_admin`, `admin` bằng seeder riêng nếu được duyệt.
- [ ] `description` — nullable.
- [ ] timestamps.

**Index/constraint:**

- [ ] Unique `roles.name`.

**Rollback:** drop `roles` chỉ khi không còn FK phụ thuộc; nếu role seed đã được dùng ở môi trường chia sẻ, rollback phải được đánh giá riêng.

### 3.3. Migration 03 — `user_roles`

**Cột chính:**

- [ ] `user_id` — FK tới `users.id`.
- [ ] `role_id` — FK tới `roles.id`.
- [ ] `assigned_by` — nullable FK tới `users.id`.
- [ ] `assigned_at`.
- [ ] timestamps.

**Index/constraint:**

- [ ] Unique `user_id + role_id`.
- [ ] Index `assigned_by` nếu truy vấn audit/cấp quyền cần thiết.
- [ ] Xác định `onDelete` cho các FK; không cascade tùy tiện với lịch sử cấp role.

**Rollback:** drop bảng sau khi các bảng phụ thuộc role/user đã được rollback.

### 3.4. Migration 04 — `account_setup_tokens`

**Cột chính:**

- [ ] `id`.
- [ ] `user_id` — FK tới `users.id`.
- [ ] `token_hash` — unique; không lưu token plaintext.
- [ ] `expires_at` — token mặc định hiệu lực 24 giờ do application thiết lập.
- [ ] `used_at` — nullable; dùng cho token đã dùng hoặc bị vô hiệu hóa.
- [ ] `created_at`.

**Index/constraint:**

- [ ] Unique `token_hash`.
- [ ] Index `user_id`.
- [ ] Index `expires_at`.
- [ ] Application phải vô hiệu hóa token cũ trước hoặc trong transaction khi tạo token mới.
- [ ] Application chỉ chấp nhận token chưa dùng và chưa hết hạn.

**Rollback:** drop bảng sau khi notification/account setup flow không còn FK phụ thuộc.

### 3.5. Migration 05 — `districts`

Danh sách phẳng các quận, huyện và thị xã thuộc Hà Nội; không tạo bảng tỉnh/thành phố riêng và không lưu phường/xã.

- [ ] `id`, `name`, `code` nullable nếu mã hành chính chưa bắt buộc, `status`, timestamps.
- [ ] Unique `name`.
- [ ] Index `status`.
- [ ] Seed allowlist district dùng cho filter và AI classification.
- [ ] Không cho xóa district trong MVP.

**Rollback:** không xóa dữ liệu district seed dùng chung nếu chưa được duyệt.

### 3.6. Migration 06 — `categories`, `tags`

#### `categories`

- [ ] `id`, `name`, `slug`, `status`, timestamps, `deleted_at` nếu dùng soft delete.
- [ ] Unique `slug` theo policy soft delete đã chọn.
- [ ] Index `status`.

#### `tags`

- [ ] `id`, `name`, `slug`, `status`, timestamps, `deleted_at` nếu dùng soft delete.
- [ ] Unique `slug` theo policy soft delete đã chọn.
- [ ] Index `status`.

Category và tag là hai taxonomy độc lập. Không tạo pivot `category_tags`; filter place có thể kết hợp `category_id` và `place_tags.tag_id` mà không áp ràng buộc tương thích giữa hai taxonomy.

**Rollback:** rollback bảng phụ thuộc trước `categories` và `tags`.

### 3.7. Migration 07 — `places`

**Cột chính:**

- [ ] `id`, `name`, `address_text`, `google_place_id`, `phone`, `website_url`, `google_maps_url`.
- [ ] `district_id` — FK tới `districts.id`.
- [ ] `category_id` — FK tới `categories.id`, mỗi place đúng một category.
- [ ] `latitude` và `longitude` dạng `DECIMAL(10,7)`.
- [ ] `min_price` và `max_price` dạng số nguyên không âm, nullable nếu place chưa có thông tin giá.
- [ ] `description` nullable.
- [ ] `thumbnail_image_id` nullable; chỉ thêm FK sau khi tạo `place_images`.
- [ ] `status` dạng `VARCHAR`; chỉ gồm `active`, `hidden`.
- [ ] `created_by` nullable FK tới `users.id`; null với seed.
- [ ] timestamps và `deleted_at`.

**Index/constraint:**

- [ ] Index `status`, `district_id`, `category_id`.
- [ ] Unique nullable `google_place_id`; bắt buộc application với seed.
- [ ] Index giá phù hợp với query range; đánh giá composite index sau khi có query contract.
- [ ] Index tọa độ nếu query khoảng cách/không gian cần thiết.
- [ ] Không unique `name`, `google_maps_url`, latitude/longitude.
- [ ] Application bảo đảm `min_price <= max_price`.
- [ ] Application bảo đảm `district_id` thuộc allowlist Hà Nội.
- [ ] Public query chỉ lấy place `active` và chưa soft-deleted.
- [ ] Seed accepted được import thẳng `active`, không có bước pending/approval.

**Rollback:** trước khi rollback phải rollback mọi bảng có FK tới `places`; không dùng cascade để che giấu dữ liệu phụ thuộc.

### 3.8. Migration 08 — `place_tags`

`places.category_id` đã biểu diễn category duy nhất của place; không tạo bảng/pivot category trung gian.

#### `place_tags`

- [ ] `place_id` — FK tới `places.id`.
- [ ] `tag_id` — FK tới `tags.id`.
- [ ] timestamps.
- [ ] Unique `place_id + tag_id`.
- [ ] Index `place_id`, `tag_id`.
- [ ] Application kiểm tra `tag_id` tồn tại và đang active trong allowlist tag toàn cục.

**Rollback:** drop các pivot trước bảng cha.

### 3.9. Migration 09 — `place_opening_hours`

**Cột chính:**

- [ ] `id`, `place_id` — FK tới `places.id`.
- [ ] `day_of_week` dạng số theo quy ước application.
- [ ] `schedule_type`: `regular`, `all_day`, `closed`.
- [ ] `opens_at`, `closes_at` nullable; bắt buộc khi `schedule_type = regular`; ứng dụng chuẩn hóa `HH:MM`.
- [ ] Không hỗ trợ mở qua nửa đêm trong MVP; không có `crosses_midnight`.
- [ ] timestamps.

**Index/constraint:**

- [ ] Index `place_id + day_of_week`.
- [ ] Cho phép nhiều slot trong cùng ngày.
- [ ] Ngày thiếu hoặc lỗi không tạo row và được hiểu là `unknown`.
- [ ] Không tạo bảng holiday/exception trong MVP.

### 3.10. Migration 10 — `place_images`, `place_managers`

#### `place_images`

- [ ] `place_id` — FK tới `places.id`.
- [ ] `uploaded_by` nullable FK tới `users.id`; null với ảnh seed.
- [ ] `image_url`, `alt_text`, `is_visible`.
- [ ] timestamps và `deleted_at`.
- [ ] Index `place_id`, `is_visible`.
- [ ] Seed lưu CSV thumbnail dưới dạng URL, không tải ảnh vào storage.
- [ ] Không phân biệt menu/gallery và không tạo `menu_images`.

#### Thumbnail của place

- [ ] Thêm FK `places.thumbnail_image_id` tới `place_images.id` sau khi `place_images` tồn tại.
- [ ] Application bảo đảm thumbnail image thuộc cùng place.
- [ ] Khi xóa thumbnail hiện tại, transaction đặt `places.thumbnail_image_id = NULL` trước khi xóa/soft-delete ảnh.

#### `place_managers`

- [ ] `place_id` — FK tới `places.id`.
- [ ] `user_id` — FK tới `users.id`.
- [ ] `assigned_by` — FK tới `users.id`.
- [ ] `assigned_at`, `revoked_at`, timestamps.
- [ ] Unique `place_id + user_id` theo quyết định MVP.
- [ ] Index `user_id`, `place_id`, `revoked_at`.
- [ ] Application chỉ cho thao tác khi assignment chưa bị thu hồi.

**Rollback:** rollback `place_managers` trước các bảng media/user/place phụ thuộc.

### 3.11. Migration 11 — `bookmarks`, `visit_events`, `anonymous_visit_events`

#### `bookmarks`

- [ ] `user_id` — FK tới `users.id`.
- [ ] `place_id` — FK tới `places.id`.
- [ ] timestamps.
- [ ] Unique `user_id + place_id`.
- [ ] Index `user_id`, `place_id`.
- [ ] Không xóa bookmark khi place inactive/soft-deleted; query phải lọc place public.

#### `visit_events`

- [ ] `user_id` — FK tới `users.id`.
- [ ] `place_id` — FK tới `places.id`.
- [ ] `visit_date` dạng `DATE`.
- [ ] `visited_at` dạng `DATETIME`.
- [ ] `source` nullable.
- [ ] timestamps.
- [ ] Unique `user_id + place_id + visit_date`.
- [ ] Index `place_id + visit_date`, `user_id + visited_at`.
- [ ] Chỉ tạo từ thao tác “Đi tới đó”, không tạo từ roll.

#### `anonymous_visit_events`

- [ ] `place_id` — FK tới `places.id`.
- [ ] `anonymous_key_hash` — hash random ID từ cookie; không lưu IP thô.
- [ ] `visit_date`, `visited_at`, `source`, timestamps (`created_at`, `updated_at`).
- [ ] Unique `place_id + anonymous_key_hash + visit_date`.
- [ ] Index `place_id + visit_date`.
- [ ] Cookie bị xóa/hết hạn được xem như anonymous visitor mới.
- [ ] Retention dài hạn là policy vận hành, không thêm dữ liệu nhận diện khác vào schema.

**Rollback:** rollback review/comment sau nếu đã tạo; rollback activity tables trước `places` và `users`.

### 3.12. Migration 12 — `reviews` và `comments`

#### `reviews`

- [ ] `user_id` — FK tới `users.id`.
- [ ] `place_id` — FK tới `places.id`.
- [ ] `rating` dạng `DECIMAL(2,1)`.
- [ ] `body` nullable.
- [ ] `status` dạng `VARCHAR`.
- [ ] timestamps và `deleted_at`.
- [ ] Unique `user_id + place_id`.
- [ ] Index `place_id + status`, `user_id`.
- [ ] Application kiểm tra đã có visit event trước khi create/update.
- [ ] Application kiểm tra rating nằm trong `1.0`–`5.0` theo bước `0.5`.
- [ ] Review body nullable; review không có reply trực tiếp.
- [ ] `review_images`: `review_id`, `image_url`, `alt_text`, `sort_order`, timestamps và `deleted_at`.
- [ ] `review_images` có FK `review_id` cascade khi xóa review và index `review_id + sort_order`.

#### `comments`

- [ ] `place_id` — FK tới `places.id`.
- [ ] `user_id` — FK tới `users.id`.
- [ ] `parent_id` nullable self-FK tới `comments.id`.
- [ ] `body`, `status`, timestamps và `deleted_at`.
- [ ] Index `place_id + status`, `parent_id`, `user_id`.
- [ ] Application kiểm tra quyền reply của Sub-admin theo `place_managers`.
- [ ] `comment_images`: `comment_id`, `image_url`, `alt_text`, `sort_order`, timestamps và `deleted_at`.
- [ ] `comment_images` có FK `comment_id` cascade khi xóa comment và index `comment_id + sort_order`.

### 3.13. Migration 13 — `place_requests` và `manager_applications`

#### `place_requests`

- [ ] `submitted_by` — FK tới `users.id`.
- [ ] `place_id` nullable FK tới `places.id`; chỉ gắn sau khi Admin chuẩn hóa/duyệt place mới.
- [ ] `name_input`, `google_maps_url_input`, `address_text_input`.
- [ ] `category_id_input` — FK tới `categories.id`; một category duy nhất do User đề xuất.
- [ ] `source_image_path` nullable; MVP chỉ một ảnh.
- [ ] `normalized_data` nullable JSON.
- [ ] `status`, `reviewed_by`, `reviewed_at`, `review_reason`.
- [ ] timestamps.
- [ ] Index `status`, `submitted_by`, `place_id`.
- [ ] Không tạo `place_request_images`.

#### `manager_applications`

- [ ] `place_request_id` — bắt buộc FK tới `place_requests.id`; MVP chỉ place mới.
- [ ] `email`, `representative_name`, `proof_reference` nullable.
- [ ] `status`, `approved_user_id`, `reviewed_by`, `reviewed_at`, `review_reason`.
- [ ] timestamps.
- [ ] Index `status`, `email`, `place_request_id`.
- [ ] Không có `password`, `password_hash` hoặc credential plaintext.
- [ ] Chỉ sau khi duyệt mới tạo `users`, `user_roles`, `place_managers` và `account_setup_tokens` trong transaction nghiệp vụ.

**Rollback:** rollback `manager_applications` trước `place_requests`; kiểm tra notification FK trước khi drop request tables.

### 3.14. Migration 14 — `promotion_requests`

**Phạm vi MVP:** chỉ ghi nhận request và quyết định Admin.

- [ ] `place_id` — FK tới `places.id`.
- [ ] `submitted_by` — FK tới `users.id`.
- [ ] `status` dạng `VARCHAR`: `pending`, `approved`, `rejected`, `cancelled`.
- [ ] `reviewed_by`, `reviewed_at`, `review_reason`.
- [ ] timestamps.
- [ ] Index `status`, `place_id`, `submitted_by`.
- [ ] Application kiểm tra người gửi là Sub-admin đang quản lý place.
- [ ] Không tạo `promotion_placements`.
- [ ] Không thêm package, payment, fee, label, position hoặc scheduling fields trong MVP.

### 3.15. Migration 15 — `moderation_actions`

**Cột chính:**

- [ ] `performed_by` — FK tới `users.id`.
- [ ] `target_type`, `target_id` — polymorphic target.
- [ ] `action`, `reason`, `metadata` JSON nullable, `created_at`.

**Index/constraint:**

- [ ] Composite index `target_type + target_id`.
- [ ] Index `performed_by`.
- [ ] Index `created_at`.
- [ ] Application giới hạn `target_type` bằng class mapping cố định; không nhận class tùy ý từ client.
- [ ] Không dùng bảng này để audit các cập nhật thông thường của Sub-admin.

**Rollback:** drop sau các workflow moderation và notification không còn phụ thuộc.

### 3.16. Migration 16 — `notification_deliveries`

**Cột chính:**

- [ ] `user_id` nullable FK tới `users.id`.
- [ ] `recipient_email`.
- [ ] `notifiable_type`, `notifiable_id`.
- [ ] `notification_type`: tối thiểu request approved/rejected và account setup.
- [ ] `status`: `pending`, `sent`, `failed`.
- [ ] `sent_at`, `failure_reason`, timestamps.

**Index/constraint:**

- [ ] Index `user_id`.
- [ ] Composite index `notifiable_type + notifiable_id`.
- [ ] Index `status`, `created_at`.
- [ ] Không lưu token plaintext, password hoặc nội dung credential.
- [ ] Email được enqueue sau transaction nghiệp vụ thành công; không gửi email khi transaction rollback.

**Rollback:** drop sau các bảng notifiable nếu cần giữ FK; đánh giá việc giữ lịch sử gửi email trước khi rollback.

## 4. Bảng dependency tóm tắt

| Thứ tự | Bảng | Phụ thuộc chính |
|---:|---|---|
| 01 | `users` | Laravel baseline |
| 02 | `roles` | Không |
| 03 | `user_roles` | `users`, `roles` |
| 04 | `account_setup_tokens` | `users` |
| 05 | `districts` | Không |
| 06 | `categories` | Không |
| 07 | `tags` | Không |
| 08 | `places` | `users`, `districts`, `categories` |
| 09 | `place_tags` | `places`, `tags` |
| 10 | `place_opening_hours` | `places` |
| 11 | `place_images` | `places`, `users` |
| 12 | `place_managers` | `places`, `users` |
| 13 | `bookmarks` | `users`, `places` |
| 14 | `visit_events` | `users`, `places` |
| 15 | `anonymous_visit_events` | `places` |
| 16 | `reviews` | `users`, `places` |
| 17 | `comments` | `users`, `places`, `comments` |
| 18 | `place_requests` | `users`, `places`, `categories` |
| 19 | `manager_applications` | `place_requests`, `users` |
| 20 | `promotion_requests` | `places`, `users` |
| 21 | `moderation_actions` | `users` |
| 22 | `notification_deliveries` | `users` và các notifiable records theo quy ước polymorphic |

> Có thể gộp các bảng cùng dependency vào một migration nếu vẫn giữ được `up()`/`down()` rõ ràng; không gộp để che giấu quan hệ hoặc làm rollback khó kiểm soát.

## 5. Constraint cần xử lý ở application

Các quy tắc sau không nên chỉ dựa vào database:

- [ ] `places.min_price <= places.max_price`.
- [ ] `places.district_id` thuộc allowlist quận/huyện/thị xã Hà Nội.
- [ ] `places.category_id` luôn có đúng một category hợp lệ.
- [ ] Tag phải tồn tại và active; không kiểm tra quan hệ với category.
- [ ] Chỉ place `active` mới được random/public; `hidden` không xuất hiện.
- [ ] Seed record thiếu Google `place_id` hoặc ngoài Hà Nội bị loại trước AI.
- [ ] Dedupe theo Google `place_id` trước AI; chỉ gửi dữ liệu đã làm sạch và có nghĩa.
- [ ] AI nhận toàn bộ `address_text`, Google Maps URL, tọa độ và dữ liệu mô tả; chỉ trả category/district/tag ID trong allowlist cùng địa chỉ chuẩn hóa.
- [ ] Google rating, review count, review content và source status không được import.
- [ ] Seed accepted được import thẳng với `status = active`, `created_by = NULL`, không pending/approval.
- [ ] Không tạo bảng import lâu dài; log seed nằm ngoài database và chỉ `google_place_id` được giữ làm identity.
- [ ] Seed rerun idempotent nhờ unique nullable `google_place_id`.
- [ ] Chỉ click “Đi tới đó” mới tạo visit event.
- [ ] Anonymous cookie random ID được hash trước khi lưu.
- [ ] Review chỉ được tạo/cập nhật khi User có visit event.
- [ ] Rating chỉ nhận giá trị 1.0–5.0 theo bước 0.5.
- [ ] Sub-admin chỉ thao tác trên place có assignment chưa bị thu hồi.
- [ ] Manager application MVP chỉ liên quan place mới.
- [ ] Không tạo `users` trước khi place và role Sub-admin được duyệt.
- [ ] Token setup có hiệu lực 24 giờ, dùng một lần; token cũ bị vô hiệu hóa khi tạo token mới.
- [ ] `moderation_actions.target_type` phải thuộc class mapping cố định.
- [ ] Promotion request chỉ do Sub-admin quản lý place gửi; MVP không có placement.
- [ ] Notification chỉ được enqueue sau transaction nghiệp vụ thành công.

## 6. Test cần có trước khi chạy migration

### 6.1. Schema/migration tests

- [ ] Migration `up()` chạy được trên MySQL 8.4 sạch.
- [ ] Migration `down()` rollback đúng thứ tự trên database test.
- [ ] Mọi FK trỏ tới bảng/cột tồn tại và dùng kiểu tương thích.
- [ ] Unique `users.email` hoạt động.
- [ ] Unique các pivot và activity event hoạt động.
- [ ] Visit user trùng `user + place + date` bị ngăn.
- [ ] Anonymous visit trùng `place + cookie hash + date` bị ngăn.
- [ ] Review trùng `user + place` bị ngăn.
- [ ] Index chính tồn tại theo checklist.
- [ ] JSON và `DECIMAL(2,1)` tương thích MySQL 8.4.

### 6.2. Business constraint tests

- [ ] Không tạo place public khi status chưa `active`.
- [ ] Không lưu place có min price lớn hơn max price.
- [ ] Không gắn district ngoài allowlist Hà Nội.
- [ ] Không publish place thiếu `category_id` hợp lệ.
- [ ] Chỉ gắn tag active thuộc allowlist tag toàn cục; tag không phụ thuộc category.
- [ ] Seed loại record thiếu Google `place_id` hoặc ngoài Hà Nội trước AI.
- [ ] Seed loại duplicate Google `place_id` trước AI và xử lý rerun idempotent.
- [ ] Seed không nhập Google rating/review/source status.
- [ ] AI nhận full address text, Google Maps URL và tọa độ; output sai allowlist được log file ngoài database và không import.
- [ ] AI không nhận hoặc sử dụng category nguồn từ CSV; phải chọn một category hệ thống dựa trên dữ liệu place.
- [ ] Địa chỉ AI chuẩn hóa được validate trước khi lưu; không cho phép địa chỉ rỗng.
- [ ] Seed accepted được import trực tiếp `active`, không qua pending/approve.
- [ ] Seed thumbnail lưu `image_url` từ CSV; không tải ảnh vào storage.
- [ ] Gán thumbnail chỉ khi image thuộc cùng place.
- [ ] Xóa thumbnail hiện tại đặt `places.thumbnail_image_id = NULL`.
- [ ] Roll không tạo visit.
- [ ] Click “Đi tới đó” của User tạo tối đa một visit/ngày.
- [ ] Anonymous cookie khác nhau có thể tạo event riêng; cùng cookie trong cùng ngày không tạo duplicate.
- [ ] Xóa/hết hạn cookie cho phép định danh anonymous mới.
- [ ] User không thể rating trước khi có visit.
- [ ] User có thể sửa/xóa review, rating và comment của chính mình.
- [ ] Sub-admin không thể sửa place ngoài assignment.
- [ ] Admin có thể hide/remove nội dung qua moderation flow.
- [ ] Manager application pending không tạo `users`.
- [ ] Approval tạo user, role, manager assignment và setup token trong luồng nguyên tử.
- [ ] Setup token hết hạn sau 24 giờ không dùng được.
- [ ] Token đã dùng hoặc token cũ bị vô hiệu hóa không dùng được.
- [ ] Promotion MVP chỉ tạo request, không tạo placement.

### 6.3. Security/data handling tests

- [ ] Không có password trong `manager_applications`.
- [ ] Không có token plaintext trong database.
- [ ] Không lưu IP thô cho anonymous visit.
- [ ] Không trả secret, password hoặc token plaintext trong API/log.
- [ ] `target_type` moderation không chấp nhận giá trị ngoài mapping cố định.
- [ ] Authorization được kiểm tra ở backend, không dựa vào frontend guard.

## 7. Rollback và vận hành an toàn

- [ ] Chụp backup phù hợp trước khi thay đổi schema ở môi trường có dữ liệu; không thực hiện nếu chưa có phê duyệt vận hành.
- [ ] Chạy migration trước trên database test MySQL 8.4.
- [ ] Kiểm tra foreign key checks và index sau migration.
- [ ] Không chạy `migrate:fresh`, `db:wipe`, `drop`, `truncate` hoặc xóa volume để xử lý lỗi migration.
- [ ] Nếu migration thất bại giữa chừng, xác định migration đã chạy tới đâu trước khi retry.
- [ ] Rollback chỉ migration thuộc task; không rollback migration không liên quan.
- [ ] Kiểm tra kế hoạch tương thích dữ liệu hiện có trước khi đổi bảng `users` mặc định.
- [ ] Ghi rõ migration nào đã chạy và môi trường nào đã kiểm chứng.

## 8. Điều kiện chuyển sang Code mode

Chỉ chuyển sang Code mode khi tất cả điều kiện sau được duyệt:

- [ ] Người dùng duyệt checklist này.
- [ ] Phạm vi MVP không bao gồm `promotion_placements`, `place_request_images` hoặc manager application cho place đã tồn tại.
- [ ] Không còn thay đổi chưa duyệt về API/database contract.
- [ ] Đã xác nhận cách xử lý migration `users` mặc định.
- [ ] Đã xác nhận migration timestamp và naming convention theo repository.
- [ ] Đã thống nhất test runner và lệnh kiểm chứng từ script thực tế của repository.
- [ ] Đã cho phép tạo migration/model trong phạm vi checklist.

## 9. Phạm vi chưa triển khai

- Chưa tạo migration.
- Chưa tạo model, relation, cast, factory hoặc seeder.
- Chưa chạy `migrate` hoặc rollback trên database.
- Chưa thay đổi API, route, service, controller, frontend hoặc Docker.
- Chưa triển khai email provider, queue worker hoặc account setup endpoint.
- Chưa triển khai promotion placement, payment, package, label, position hoặc scheduling.
