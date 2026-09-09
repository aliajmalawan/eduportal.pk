-- Public documentation at /docs  (Task 8, step 6)
-- Run once on production via phpMyAdmin. Idempotent.
--
-- One help article per feature module. Articles are seeded as Draft with a
-- title and a link to their feature page; the actual step-by-step content has
-- to be written by someone who can see the product. Documentation that
-- describes screens which do not exist increases support calls rather than
-- reducing them, which is the opposite of the point.

CREATE TABLE IF NOT EXISTS ep_docs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(160) NOT NULL COMMENT 'URL segment under /docs/',
  title VARCHAR(190) NOT NULL,
  feature_script VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'The module page this documents, e.g. fee-management.php',
  category VARCHAR(80) NOT NULL DEFAULT '' COMMENT 'Grouping on the /docs index',
  summary VARCHAR(400) NOT NULL DEFAULT '' COMMENT 'One line: what this guide covers',
  intro_html TEXT NULL COMMENT 'Context before the steps',
  steps_json TEXT NULL COMMENT 'JSON array of {title, body} — the numbered steps',
  tips_html TEXT NULL COMMENT 'Notes, gotchas, things support gets asked about',
  faqs_json TEXT NULL COMMENT 'JSON array of {q, a} rendered as an accordion + FAQPage schema',
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft / Active',
  sort_order INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug_uq (slug),
  KEY status_idx (status, category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
