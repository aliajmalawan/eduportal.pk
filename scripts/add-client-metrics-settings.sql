-- Task 4: add client-metrics settings to ep_site_settings.
-- Safe to run once or multiple times (setting_key is the primary key,
-- so this updates in place rather than creating duplicates).
-- Run this against the PRODUCTION database via phpMyAdmin (or equivalent) --
-- the local dev database already has these values from development/testing.

-- If production still has the original pre-existing dormant setting under
-- its old name (trusted_schools_count), rename it to total_clients so
-- production ends up in the same state as dev, rather than having both
-- the old unused key and the new one. No-op if it doesn't exist.
UPDATE ep_site_settings
SET setting_key = 'total_clients', setting_group = 'site_content'
WHERE setting_key = 'trusted_schools_count';

INSERT INTO ep_site_settings (setting_key, setting_value, setting_group)
VALUES
  ('total_clients', '500+', 'site_content'),
  ('total_students', '300,000+', 'site_content'),
  ('total_cities', '', 'site_content'),
  ('metrics_updated_date', 'August 2026', 'site_content')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  setting_group = VALUES(setting_group);
