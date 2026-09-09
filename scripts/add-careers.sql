-- Careers, Job Listings & Applications (Task 7)
-- Run once on production via phpMyAdmin.
--
-- Table names keep the project-wide ep_ prefix used by all other tables
-- (ep_blogs, ep_demo_leads, ep_video_testimonials, ...); the columns follow
-- the Task 7 specification.
--
-- This feature has never gone live, so there is no real job or application
-- data to preserve — DROP+CREATE is used rather than an ALTER migration for
-- a schema that never shipped (the same approach scripts/add-google-reviews.sql
-- took). The admin panel applies the identical schema automatically via
-- ep_ensure_careers_schema(), so running this file is optional but is the
-- recommended way to migrate production before the first login.

DROP TABLE IF EXISTS ep_job_applications;
DROP TABLE IF EXISTS ep_jobs;

CREATE TABLE ep_jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL COMMENT 'e.g. Senior PHP Developer',
  slug VARCHAR(180) NOT NULL COMMENT 'Auto-made from title, unique — URL segment for /careers/{slug}',
  department VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Engineering / Sales / Support / Marketing',
  job_type VARCHAR(20) NOT NULL DEFAULT 'Full Time' COMMENT 'Full Time / Part Time / Contract / Internship',
  work_mode VARCHAR(20) NOT NULL DEFAULT 'Onsite' COMMENT 'Onsite / Remote / Hybrid',
  location VARCHAR(150) NOT NULL DEFAULT '' COMMENT 'e.g. Gujranwala, Punjab',
  salary_min INT UNSIGNED NULL DEFAULT NULL COMMENT 'e.g. 50000 — NULL hides the salary publicly',
  salary_max INT UNSIGNED NULL DEFAULT NULL COMMENT 'e.g. 100000',
  experience VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'e.g. 2-4 years',
  description TEXT NULL COMMENT 'Main job overview',
  requirements TEXT NULL COMMENT 'JSON array of bullet points, built by the admin bullet-point builder',
  responsibilities TEXT NULL COMMENT 'JSON array of bullet points',
  benefits TEXT NULL COMMENT 'JSON array of bullet points',
  vacancies INT NOT NULL DEFAULT 1,
  deadline DATE NULL DEFAULT NULL COMMENT 'Can be empty — job auto-hides after this date',
  status VARCHAR(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft / Active / Closed',
  posted_at DATETIME NULL DEFAULT NULL COMMENT 'Set the first time the job goes Active',

  -- Additions beyond the spec, powering existing public-page behaviour.
  summary VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Short blurb on the job card',
  skills VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Comma separated, rendered as chips',
  is_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Featured badge + sorts first',
  sort_order INT NOT NULL DEFAULT 0,
  meta_title VARCHAR(190) NOT NULL DEFAULT '' COMMENT 'SEO override for the detail page',
  meta_description VARCHAR(320) NOT NULL DEFAULT '' COMMENT 'SEO override for the detail page',
  apply_email VARCHAR(190) NOT NULL DEFAULT '' COMMENT 'Per-job notification override',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY slug_uq (slug),
  KEY status_idx (status),
  KEY listing_idx (status, is_featured, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ep_job_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = general CV sent when no role was open',
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  whatsapp VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Digits only, used for wa.me click-to-chat',
  city VARCHAR(100) NOT NULL DEFAULT '',
  photo VARCHAR(300) NOT NULL DEFAULT '' COMMENT 'Path under uploads/careers/photos — not web-servable',
  resume VARCHAR(300) NOT NULL DEFAULT '' COMMENT 'Path under uploads/careers/cv — not web-servable',
  years_experience VARCHAR(20) NOT NULL DEFAULT '',
  relevant_experience TEXT NULL,
  expected_salary INT UNSIGNED NULL DEFAULT NULL,
  joining_time VARCHAR(50) NOT NULL DEFAULT '',
  linkedin VARCHAR(300) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'New' COMMENT 'New / Shortlisted / Interviewed / Rejected / Hired',
  admin_notes TEXT NULL COMMENT 'Internal HR notes — never rendered publicly',
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  consent_at DATETIME NULL DEFAULT NULL COMMENT 'When the applicant ticked the recruitment-data consent box (spec 7.4 field 12)',

  -- Additions beyond the spec.
  job_title_snapshot VARCHAR(190) NOT NULL DEFAULT '' COMMENT 'Role title as applied for; survives job edits and deletion (job_id is ON DELETE SET NULL)',
  resume_original_name VARCHAR(190) NOT NULL DEFAULT '' COMMENT 'Sanitised original filename, used only for the download filename',
  resume_mime VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Verified MIME type, so downloads send the correct Content-Type',
  resume_size INT UNSIGNED NOT NULL DEFAULT 0,
  cover_letter TEXT NULL,
  source_page VARCHAR(190) NOT NULL DEFAULT '',
  ip_address VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Required by the rate limiter (ep_careers_rate_limited)',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY job_idx (job_id),
  KEY status_idx (status),
  KEY applied_idx (applied_at),
  KEY rate_limit_idx (ip_address, applied_at),
  KEY dedupe_idx (email, job_id),
  CONSTRAINT fk_ep_job_applications_job FOREIGN KEY (job_id) REFERENCES ep_jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Careers copy lives in the existing ep_site_settings table (same mechanism as
-- every other admin-editable setting) rather than a second settings system.
INSERT INTO ep_site_settings (setting_key, setting_value, setting_group) VALUES
  ('careers_intro_heading', 'Build the future of school management', 'careers'),
  ('careers_intro_text', 'We are a Pakistan-based product team building EduPortal, the school ERP trusted by hundreds of institutes. If you care about clean software and real-world impact in education, we would like to hear from you.', 'careers'),
  ('careers_no_jobs_text', 'We do not have any open positions right now. Send us your CV anyway — we review every general application and get in touch when a matching role opens.', 'careers'),
  ('careers_notify_email', '', 'careers'),
  ('careers_hr_whatsapp', '', 'careers')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
