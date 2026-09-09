-- Comparison pages: /compare/eduportal-vs-{competitor}  (Task 8, step 4)
-- Run once on production via phpMyAdmin. Idempotent.
--
-- Claims about a named competitor are factual assertions about another
-- business, so every comparison carries a source_url and verified_at. A row
-- whose facts have not been checked recently should be re-checked or pulled,
-- not left to quietly go stale — competitors change their products and their
-- pricing, and an out-of-date comparison is both unfair and a liability.

CREATE TABLE IF NOT EXISTS ep_comparisons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(160) NOT NULL COMMENT 'URL segment, e.g. eduportal-vs-edusuite',
  competitor_name VARCHAR(120) NOT NULL COMMENT 'e.g. EduSuite',
  competitor_summary TEXT NULL COMMENT 'Neutral one-paragraph description of what they are',
  intro_html TEXT NULL COMMENT 'Opening context for the comparison',
  verdict_html TEXT NULL COMMENT 'Honest summary: who should pick which',
  where_they_win_html TEXT NULL COMMENT 'Where the competitor is genuinely the better choice — required',
  source_url VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Where the competitor facts came from',
  verified_at DATE NULL DEFAULT NULL COMMENT 'When those facts were last checked',
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft / Active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug_uq (slug),
  KEY status_idx (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per line of the side-by-side table.
CREATE TABLE IF NOT EXISTS ep_comparison_rows (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  comparison_id INT UNSIGNED NOT NULL,
  section VARCHAR(80) NOT NULL DEFAULT '' COMMENT 'Optional grouping, e.g. Pricing, Modules, Support',
  feature_label VARCHAR(190) NOT NULL,
  eduportal_value TEXT NULL,
  competitor_value TEXT NULL,
  advantage VARCHAR(20) NOT NULL DEFAULT 'tie' COMMENT 'eduportal / competitor / tie',
  note VARCHAR(400) NOT NULL DEFAULT '' COMMENT 'Optional qualifier shown under the row',
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY comparison_idx (comparison_id, sort_order),
  CONSTRAINT fk_ep_comparison_rows FOREIGN KEY (comparison_id) REFERENCES ep_comparisons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The five competitors named in the brief. Created as Draft with no
-- competitor facts: a comparison page must not go live until someone has
-- verified what it says about the other company.
INSERT INTO ep_comparisons (slug, competitor_name, status, sort_order) VALUES
  ('eduportal-vs-edusuite',  'EduSuite',  'Draft', 1),
  ('eduportal-vs-capobrain', 'Capobrain', 'Draft', 2),
  ('eduportal-vs-skoolzoom', 'SkoolZoom', 'Draft', 3),
  ('eduportal-vs-glowsims',  'Glowsims',  'Draft', 4),
  ('eduportal-vs-prodesk',   'ProDesk',   'Draft', 5)
ON DUPLICATE KEY UPDATE slug = slug;
