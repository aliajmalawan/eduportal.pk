-- Admin-editable homepage demo video (Task 6)
-- Run once on production via phpMyAdmin. Idempotent — safe to re-run.
--
-- The demo video played from the homepage hero was previously hard-coded in
-- the JavaScript on index.php. These two keys move it into the existing
-- ep_site_settings table, so it is changed by pasting a YouTube link into
-- Settings -> Home Page.

INSERT INTO ep_site_settings (setting_key, setting_value, setting_group) VALUES
  ('demo_video_url', '', 'home_page'),
  ('demo_video_title', 'EduPortal product demo', 'home_page')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Settings -> Home Page. (An earlier revision of this script filed them
-- under 'media'; this moves them if you already ran it.)
UPDATE ep_site_settings SET setting_group = 'home_page'
  WHERE setting_key IN ('demo_video_url', 'demo_video_title');

-- Carry over the id from the orphaned key this replaces, so an existing
-- value is not lost. default_youtube_demo_id held a bare video id and was
-- referenced by no code at all — it never reached the page.
UPDATE ep_site_settings dest
  JOIN ep_site_settings src ON src.setting_key = 'default_youtube_demo_id'
  SET dest.setting_value = CONCAT('https://www.youtube.com/watch?v=', src.setting_value)
  WHERE dest.setting_key = 'demo_video_url'
    AND dest.setting_value = ''
    AND src.setting_value <> '';

DELETE FROM ep_site_settings WHERE setting_key = 'default_youtube_demo_id';
