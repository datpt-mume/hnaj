-- =======================================================================
-- V1__baseline_schema.sql
-- Generated from 38 Laravel migrations in hnaj-be/database/migrations/
-- See docs/migration/knowledge-base/02-schema.md for mapping.
-- Target: MySQL 8.x  |  Charset: utf8mb4  |  Engine: InnoDB
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- users (migrations 000000 + 000001 + 000030)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` DATETIME NULL,
  `google_id` VARCHAR(64) NULL,
  `avatar_url` VARCHAR(255) NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_status_index` (`status`),
  KEY `users_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_reset_tokens + sessions (Laravel infra, 000000)
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cache + cache_locks (000001) — cho DatabaseCache driver, giữ nếu cần
CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- jobs + job_batches + failed_jobs (000002) — cho QUEUE_CONNECTION=database
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- roles (000003) + user_roles (000004)
-- ---------------------------------------------------------------------
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by` BIGINT UNSIGNED NULL,
  `assigned_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`, `role_id`),
  KEY `user_roles_assigned_by_index` (`assigned_by`),
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- account_setup_tokens (000005)
-- ---------------------------------------------------------------------
CREATE TABLE `account_setup_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_setup_tokens_token_hash_unique` (`token_hash`),
  KEY `account_setup_tokens_expires_at_index` (`expires_at`),
  KEY `account_setup_tokens_user_id_index` (`user_id`),
  CONSTRAINT `account_setup_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- districts (000006) + categories (000007) + tags (000008)
-- Note: category_tags table is dropped by 000029 — do NOT create.
-- ---------------------------------------------------------------------
CREATE TABLE `districts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(255) NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `districts_name_unique` (`name`),
  KEY `districts_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_status_index` (`status`),
  KEY `categories_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`),
  KEY `tags_status_index` (`status`),
  KEY `tags_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- places (000010) + extensions (000025, 000032, 000033, 000034)
-- ---------------------------------------------------------------------
CREATE TABLE `places` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `address_text` TEXT NOT NULL,
  `google_place_id` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NULL,
  `website_url` TEXT NULL,
  `google_maps_url` TEXT NOT NULL,
  `district_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `min_price` BIGINT UNSIGNED NULL,
  `max_price` BIGINT UNSIGNED NULL,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 5.0,
  `description` TEXT NULL,
  `thumbnail_image_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `places_google_place_id_unique` (`google_place_id`),
  KEY `places_district_id_index` (`district_id`),
  KEY `places_category_id_index` (`category_id`),
  KEY `places_min_price_index` (`min_price`),
  KEY `places_max_price_index` (`max_price`),
  KEY `places_rating_index` (`rating`),
  KEY `places_status_index` (`status`),
  KEY `places_is_verified_index` (`is_verified`),
  KEY `places_is_verified_id_index` (`is_verified`, `id`),
  KEY `places_lat_lng_index` (`latitude`, `longitude`),
  KEY `places_deleted_at_index` (`deleted_at`),
  CONSTRAINT `places_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `places_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `places_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `places_rating_check` CHECK (`rating` >= 0.0 AND `rating` <= 5.0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- thumbnail_image_id FK added later (after place_images exists) — see ALTER below

-- place_tags (000011)
CREATE TABLE `place_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `place_tags_place_id_tag_id_unique` (`place_id`, `tag_id`),
  KEY `place_tags_tag_id_index` (`tag_id`),
  CONSTRAINT `place_tags_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- place_opening_hours (000012) - day_of_week 2..8 (T2..CN)
-- Note: crosses_midnight dropped in 000026
CREATE TABLE `place_opening_hours` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL,
  `schedule_type` VARCHAR(255) NOT NULL,
  `opens_at` TIME NULL,
  `closes_at` TIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `place_opening_hours_place_day_index` (`place_id`, `day_of_week`),
  CONSTRAINT `place_opening_hours_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- place_images (000013) — created BEFORE places.thumbnail_image_id FK (000025)
CREATE TABLE `place_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `uploaded_by` BIGINT UNSIGNED NULL,
  `image_url` TEXT NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `place_images_is_visible_index` (`is_visible`),
  KEY `place_images_place_id_index` (`place_id`),
  KEY `place_images_deleted_at_index` (`deleted_at`),
  CONSTRAINT `place_images_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_images_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Now add thumbnail_image_id FK (000025)
ALTER TABLE `places` ADD CONSTRAINT `places_thumbnail_image_id_foreign`
  FOREIGN KEY (`thumbnail_image_id`) REFERENCES `place_images`(`id`) ON DELETE RESTRICT;

-- place_managers (000014)
CREATE TABLE `place_managers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by` BIGINT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL,
  `revoked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `place_managers_place_id_user_id_unique` (`place_id`, `user_id`),
  KEY `place_managers_user_id_place_id_index` (`user_id`, `place_id`),
  KEY `place_managers_revoked_at_index` (`revoked_at`),
  CONSTRAINT `place_managers_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_managers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_managers_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- bookmarks (000015)
CREATE TABLE `bookmarks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookmarks_user_id_place_id_unique` (`user_id`, `place_id`),
  KEY `bookmarks_user_id_index` (`user_id`),
  KEY `bookmarks_place_id_index` (`place_id`),
  CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookmarks_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- visit_events (000016) - authenticated user visits
-- Idempotent via UNIQUE(user_id, place_id, visit_date)
CREATE TABLE `visit_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `visit_date` DATE NOT NULL,
  `visited_at` DATETIME NOT NULL,
  `source` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `visit_events_user_place_date_unique` (`user_id`, `place_id`, `visit_date`),
  KEY `visit_events_place_date_index` (`place_id`, `visit_date`),
  KEY `visit_events_user_visited_index` (`user_id`, `visited_at`),
  CONSTRAINT `visit_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `visit_events_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- anonymous_visit_events (000017) - guest visits via X-Anonymous-Id
-- Stores SHA-256 hash, NOT plaintext
CREATE TABLE `anonymous_visit_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `anonymous_key_hash` VARCHAR(128) NOT NULL,
  `visit_date` DATE NOT NULL,
  `visited_at` DATETIME NOT NULL,
  `source` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `anon_visit_unique` (`place_id`, `anonymous_key_hash`, `visit_date`),
  KEY `anon_visit_date_index` (`place_id`, `visit_date`),
  CONSTRAINT `anonymous_visit_events_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- reviews (000018) + review_images (000027)
-- review_images uses CASCADE on delete (different from most tables)
-- ---------------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `rating` DECIMAL(2,1) NOT NULL,
  `body` TEXT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_user_id_place_id_unique` (`user_id`, `place_id`),
  KEY `reviews_status_index` (`status`),
  KEY `reviews_place_id_status_index` (`place_id`, `status`),
  KEY `reviews_user_id_index` (`user_id`),
  KEY `reviews_deleted_at_index` (`deleted_at`),
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `reviews_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `review_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` BIGINT UNSIGNED NOT NULL,
  `image_url` TEXT NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `review_images_review_id_sort_order_index` (`review_id`, `sort_order`),
  CONSTRAINT `review_images_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- comments (000019) + comment_images (000028)
CREATE TABLE `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `body` TEXT NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `comments_place_id_status_index` (`place_id`, `status`),
  KEY `comments_user_id_index` (`user_id`),
  KEY `comments_deleted_at_index` (`deleted_at`),
  CONSTRAINT `comments_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `comment_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` BIGINT UNSIGNED NOT NULL,
  `image_url` TEXT NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `comment_images_comment_id_sort_order_index` (`comment_id`, `sort_order`),
  CONSTRAINT `comment_images_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- place_requests (000020) - public-suggested places (planned feature)
-- ---------------------------------------------------------------------
CREATE TABLE `place_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submitted_by` BIGINT UNSIGNED NOT NULL,
  `place_id` BIGINT UNSIGNED NULL,
  `name_input` VARCHAR(255) NOT NULL,
  `google_maps_url_input` TEXT NOT NULL,
  `address_text_input` TEXT NOT NULL,
  `category_id_input` BIGINT UNSIGNED NOT NULL,
  `source_image_path` VARCHAR(255) NULL,
  `normalized_data` JSON NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `review_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `place_requests_status_index` (`status`),
  KEY `place_requests_submitted_by_index` (`submitted_by`),
  KEY `place_requests_place_id_index` (`place_id`),
  CONSTRAINT `place_requests_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_requests_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_requests_category_id_input_foreign` FOREIGN KEY (`category_id_input`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `place_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- manager_applications (000021 + 2026_08_12_000001)
-- Allows existing user to apply to manage existing place (place_id NULL-able)
-- ---------------------------------------------------------------------
CREATE TABLE `manager_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_request_id` BIGINT UNSIGNED NULL,
  `place_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(255) NOT NULL,
  `representative_name` VARCHAR(255) NOT NULL,
  `proof_reference` TEXT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approved_user_id` BIGINT UNSIGNED NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `review_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `manager_applications_email_index` (`email`),
  KEY `manager_applications_status_index` (`status`),
  KEY `manager_applications_place_request_id_index` (`place_request_id`),
  KEY `manager_applications_place_id_index` (`place_id`),
  KEY `manager_applications_user_id_index` (`user_id`),
  CONSTRAINT `manager_applications_place_request_id_foreign` FOREIGN KEY (`place_request_id`) REFERENCES `place_requests`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `manager_applications_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `manager_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `manager_applications_approved_user_id_foreign` FOREIGN KEY (`approved_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `manager_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- promotion_requests (000022) - planned feature
CREATE TABLE `promotion_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `submitted_by` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `review_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_requests_status_index` (`status`),
  KEY `promotion_requests_place_id_index` (`place_id`),
  KEY `promotion_requests_submitted_by_index` (`submitted_by`),
  CONSTRAINT `promotion_requests_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `promotion_requests_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `promotion_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- moderation_actions (000023) - audit log
CREATE TABLE `moderation_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `performed_by` BIGINT UNSIGNED NOT NULL,
  `target_type` VARCHAR(255) NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `reason` TEXT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `moderation_actions_target_type_target_id_index` (`target_type`, `target_id`),
  KEY `moderation_actions_performed_by_index` (`performed_by`),
  KEY `moderation_actions_created_at_index` (`created_at`),
  CONSTRAINT `moderation_actions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- notification_deliveries (000024) - email delivery log
CREATE TABLE `notification_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `notification_type` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `sent_at` DATETIME NULL,
  `failure_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `notification_deliveries_status_index` (`status`),
  KEY `notification_deliveries_user_id_index` (`user_id`),
  KEY `notification_deliveries_notifiable_index` (`notifiable_type`, `notifiable_id`),
  KEY `notification_deliveries_created_at_index` (`created_at`),
  CONSTRAINT `notification_deliveries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- email_verification_tokens (000031) + personal_access_tokens (000091108)
-- ---------------------------------------------------------------------
CREATE TABLE `email_verification_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_verification_tokens_token_hash_unique` (`token_hash`),
  KEY `email_verification_tokens_expires_at_index` (`expires_at`),
  KEY `email_verification_tokens_user_id_index` (`user_id`),
  CONSTRAINT `email_verification_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sanctum personal_access_tokens (note: token stores SHA-256 hash of plaintext)
-- Note: We do NOT commit to Sanctum token format compatibility (Q7 → API-contract only)
-- We keep this table structure for future migration parity, but Spring will issue
-- its own token format in Phase 2.
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` TEXT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_index` (`tokenable_type`, `tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- category_tags (000009) was DROPPED by 000029 - do NOT create.
-- place_opening_hours.crosses_midnight column DROPPED by 000026 - do NOT create.
-- ---------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
