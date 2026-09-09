-- Google Reviews integration — run once on production via phpMyAdmin.
--
-- Safe to re-run, but note what each statement does, because they differ:
--   ep_google_reviews           DROP + CREATE — any cached reviews are lost
--                               and simply re-fetched on the next sync.
--   ep_google_review_moderation CREATE TABLE IF NOT EXISTS — preserved,
--                               because it holds the admin's hide/show
--                               decisions, which cannot be re-derived.
--   ep_site_settings rows       INSERT ... ON DUPLICATE KEY UPDATE — existing
--                               values (OAuth tokens, Place ID) are kept.
--
-- So re-running never destroys anything that cannot be recovered, but it
-- does clear the review cache until the next sync runs.
--
-- The Places API key must never live in the database — it belongs only in
-- the server's .env file as GOOGLE_PLACES_API_KEY (see .env.example). This
-- script does not insert a google_places_api_key row; if you ran an earlier
-- version of this script that did, the DELETE below removes it.

-- This feature has never gone live in production, so there is no real
-- review data to preserve — DROP+CREATE is used instead of an ALTER/rename
-- migration for a schema that never shipped.
DROP TABLE IF EXISTS ep_google_reviews;
CREATE TABLE ep_google_reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  google_review_id VARCHAR(500) NOT NULL COMMENT 'Google resource name, e.g. accounts/x/locations/y/reviews/z — stable dedup key for upserts; required beyond the requested field list since author_name+rating+date alone cannot be trusted as unique',
  author_name VARCHAR(150) NOT NULL,
  author_photo VARCHAR(500) DEFAULT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review_text TEXT DEFAULT NULL,
  review_date DATETIME DEFAULT NULL COMMENT 'When the review was originally posted on Google',
  is_hidden TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Admin curation: hide from public display without deleting/modifying',
  fetched_at DATETIME NOT NULL COMMENT 'When this row was last synced from Google — drives the 30-day cache-retention purge',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY google_review_id_uq (google_review_id(255)),
  -- Indexes chosen by measuring the actual queries against 2,000 rows, not
  -- by guesswork. These two are the whole set that earned their place:
  --
  --   visible_recent_idx (is_hidden, review_date)
  --     Serves the homepage query (WHERE is_hidden=0 AND rating IN (4,5)
  --     ORDER BY review_date DESC LIMIT 6), which runs on every homepage
  --     load. Took it from 50.4ms to ~2ms and removed the filesort.
  --
  --   fetched_at_idx (fetched_at)
  --     Serves the 30-day retention purge (WHERE fetched_at < cutoff),
  --     which previously full-scanned the table on every sync. ~9x faster.
  --
  -- Deliberately NOT added, having been measured and rejected:
  --   (is_hidden, rating, review_date) — made the homepage query SLOWER
  --     (16ms vs 2ms). Because rating IN (4,5) is a range on the middle
  --     column, the index cannot supply sorted order, so the filesort came
  --     back; adding it alongside the index above was worse still (225ms
  --     on /reviews) as the optimizer picked badly between them.
  --   A standalone is_hidden index — replaced by visible_recent_idx, whose
  --     leading column it duplicates. On its own MySQL ignored it anyway,
  --     is_hidden having only two distinct values.
  --   Anything for /reviews' unfiltered listing — it returns ~95% of rows,
  --     so a full scan is genuinely the optimal plan there.
  KEY visible_recent_idx (is_hidden, review_date),
  KEY fetched_at_idx (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remembers admin hide/show decisions so they survive the 30-day content
-- purge and any later re-sync. Stores ONLY the opaque Google review
-- identifier plus our own boolean — never author name, text, or rating —
-- so it is moderation metadata, not cached Google review content, and is
-- therefore not governed by the 30-day content-retention cap.
-- NOT dropped/recreated: this is the one table whose data must persist.
CREATE TABLE IF NOT EXISTS ep_google_review_moderation (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  google_review_id VARCHAR(500) NOT NULL COMMENT 'Opaque Google review identifier only — no review content stored here',
  is_hidden TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Admin hide/show decision, remembered across purges and re-syncs',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY google_review_id_uq (google_review_id(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ep_site_settings (setting_key, setting_value, setting_group) VALUES
  ('google_oauth_client_id', '', 'google_reviews'),
  ('google_oauth_client_secret', '', 'google_reviews'),
  ('google_oauth_refresh_token', '', 'google_reviews'),
  ('google_oauth_access_token', '', 'google_reviews'),
  ('google_oauth_token_expires_at', '', 'google_reviews'),
  ('google_business_location_id', '', 'google_reviews'),
  ('google_reviews_last_synced_at', '', 'google_reviews'),
  ('google_reviews_last_sync_status', '', 'google_reviews'),
  ('google_reviews_last_sync_error', '', 'google_reviews'),
  ('google_reviews_last_sync_error_type', '', 'google_reviews'),
  ('google_reviews_last_manual_refresh_at', '', 'google_reviews'),
  ('google_rating', '', 'google_reviews'),
  ('google_review_count', '', 'google_reviews'),
  ('google_place_id', '', 'google_reviews')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Cleanup for anyone who already ran a prior version of this script.
DELETE FROM ep_site_settings WHERE setting_key = 'google_places_api_key';
UPDATE ep_site_settings SET setting_key = 'google_rating' WHERE setting_key = 'google_reviews_average_rating';
UPDATE ep_site_settings SET setting_key = 'google_review_count' WHERE setting_key = 'google_reviews_total_count';

-- AI/LLM crawler logging (Task 9.6). Records GPTBot / ClaudeBot /
-- PerplexityBot / Google-Extended visits server-side, because the normal
-- visitor tracking runs from JavaScript and crawlers do not execute it.
CREATE TABLE IF NOT EXISTS ep_ai_crawler_hits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bot VARCHAR(40) NOT NULL COMMENT 'Normalised bot name',
  page_path VARCHAR(255) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  hit_date DATE NOT NULL COMMENT 'Denormalised for cheap per-day grouping',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY bot_date_idx (bot, hit_date),
  KEY hit_date_idx (hit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ep_site_settings (setting_key, setting_value, setting_group) VALUES
  ('google_site_verification', '', 'general'),
  ('bing_site_verification', '', 'general')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
