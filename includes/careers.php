<?php
/**
 * Careers, job listings & applications (Task 7).
 *
 * Shared between the public pages (careers.php, career.php) and the admin
 * panel (cms/careers.php, cms/job-applications.php). Uses the existing
 * database connection ($m), the existing ep_site_settings helpers, and the
 * existing ep_h()/ep_url()/ep_format_date() helpers from includes/cms.php.
 *
 * Schema: ep_jobs and ep_job_applications — project-standard ep_ prefix,
 * Task 7 column names. See scripts/add-careers.sql.
 */

require_once __DIR__ . '/cms.php';

/* ---------------------------------------------------------------------
 * Schema
 * ------------------------------------------------------------------ */

/**
 * Creates the careers tables if they are missing. Mirrors the existing
 * cms_ensure_*_tables() pattern and is invoked from cms/includes/bootstrap.php,
 * so an admin login is enough to migrate a site where scripts/add-careers.sql
 * has not been run yet. Public pages never call this — they degrade to an
 * empty list instead (see ep_careers_tables_ready()).
 */
function ep_ensure_careers_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $m;
    $db = $m->mysqli();

    // A pre-spec build of this feature shipped different column names. It was
    // never live and never held real data, so an out-of-date table is simply
    // rebuilt rather than migrated column by column.
    ep_careers_drop_prespec_tables($db);

    $db->query("CREATE TABLE IF NOT EXISTS ep_jobs (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      title VARCHAR(150) NOT NULL,
      slug VARCHAR(180) NOT NULL,
      department VARCHAR(100) NOT NULL DEFAULT '',
      job_type VARCHAR(20) NOT NULL DEFAULT 'Full Time',
      work_mode VARCHAR(20) NOT NULL DEFAULT 'Onsite',
      location VARCHAR(150) NOT NULL DEFAULT '',
      salary_min INT UNSIGNED NULL DEFAULT NULL,
      salary_max INT UNSIGNED NULL DEFAULT NULL,
      experience VARCHAR(50) NOT NULL DEFAULT '',
      description TEXT NULL,
      requirements TEXT NULL,
      responsibilities TEXT NULL,
      benefits TEXT NULL,
      vacancies INT NOT NULL DEFAULT 1,
      deadline DATE NULL DEFAULT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'Draft',
      posted_at DATETIME NULL DEFAULT NULL,
      summary VARCHAR(500) NOT NULL DEFAULT '',
      skills VARCHAR(500) NOT NULL DEFAULT '',
      is_featured TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      meta_title VARCHAR(190) NOT NULL DEFAULT '',
      meta_description VARCHAR(320) NOT NULL DEFAULT '',
      apply_email VARCHAR(190) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY slug_uq (slug),
      KEY status_idx (status),
      KEY listing_idx (status, is_featured, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS ep_job_applications (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      job_id INT UNSIGNED NULL DEFAULT NULL,
      full_name VARCHAR(150) NOT NULL,
      email VARCHAR(150) NOT NULL,
      whatsapp VARCHAR(20) NOT NULL DEFAULT '',
      city VARCHAR(100) NOT NULL DEFAULT '',
      photo VARCHAR(300) NOT NULL DEFAULT '',
      resume VARCHAR(300) NOT NULL DEFAULT '',
      years_experience VARCHAR(20) NOT NULL DEFAULT '',
      relevant_experience TEXT NULL,
      expected_salary INT UNSIGNED NULL DEFAULT NULL,
      joining_time VARCHAR(50) NOT NULL DEFAULT '',
      linkedin VARCHAR(300) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'New',
      admin_notes TEXT NULL,
      applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      consent_at DATETIME NULL DEFAULT NULL,
      job_title_snapshot VARCHAR(190) NOT NULL DEFAULT '',
      resume_original_name VARCHAR(190) NOT NULL DEFAULT '',
      resume_mime VARCHAR(100) NOT NULL DEFAULT '',
      resume_size INT UNSIGNED NOT NULL DEFAULT 0,
      cover_letter TEXT NULL,
      source_page VARCHAR(190) NOT NULL DEFAULT '',
      ip_address VARCHAR(45) NOT NULL DEFAULT '',
      user_agent VARCHAR(255) NOT NULL DEFAULT '',
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY job_idx (job_id),
      KEY status_idx (status),
      KEY applied_idx (applied_at),
      KEY rate_limit_idx (ip_address, applied_at),
      KEY dedupe_idx (email, job_id),
      CONSTRAINT fk_ep_job_applications_job FOREIGN KEY (job_id) REFERENCES ep_jobs(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Careers copy lives in the existing ep_site_settings table, so it is
    // edited on the normal Settings page rather than through a second
    // settings mechanism. Seeded once; existing values are never overwritten.
    foreach ([
        'careers_intro_heading' => 'Build the future of school management',
        'careers_intro_text' => 'We are a product team building EduPortal, the school ERP trusted by hundreds of institutes. If you care about clean software and real-world impact in education, we would like to hear from you.',
        'careers_no_jobs_text' => 'We do not have any open positions right now. Send us your CV anyway — we review every general application and get in touch when a matching role opens.',
        'careers_notify_email' => '',
        'careers_hr_whatsapp' => '',
    ] as $key => $default) {
        $stmt = $db->prepare(
            'INSERT INTO ep_site_settings (setting_key, setting_value, setting_group)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_key = setting_key'
        );
        if ($stmt) {
            $group = 'careers';
            $stmt->bind_param('sss', $key, $default, $group);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Added in 7.4: existing installs get the column without a manual ALTER.
    $res = @$db->query("SHOW COLUMNS FROM ep_job_applications LIKE 'consent_at'");
    if ($res && $res->num_rows === 0) {
        $db->query('ALTER TABLE ep_job_applications ADD COLUMN consent_at DATETIME NULL DEFAULT NULL AFTER applied_at');
    }

    // Holds a JSON object of admin-added custom field values (see "Application
    // form field configuration" below) — the fixed columns above only cover
    // the built-in fields.
    $res = @$db->query("SHOW COLUMNS FROM ep_job_applications LIKE 'extra_fields'");
    if ($res && $res->num_rows === 0) {
        $db->query('ALTER TABLE ep_job_applications ADD COLUMN extra_fields TEXT NULL DEFAULT NULL AFTER cover_letter');
    }

    ep_careers_ensure_upload_dirs();
}

/**
 * Drops careers tables left over from the pre-specification build, identified
 * by a column that only existed there. Guarded by a row count so it can never
 * discard real data: if anything has been received, the tables are left alone
 * and the mismatch is logged for a manual migration instead.
 */
function ep_careers_drop_prespec_tables(mysqli $db): void
{
    $res = @$db->query("SHOW COLUMNS FROM ep_jobs LIKE 'employment_type'");
    if (!$res || $res->num_rows === 0) {
        return;
    }

    $rows = 0;
    foreach (['ep_jobs', 'ep_job_applications'] as $table) {
        $count = @$db->query("SELECT COUNT(*) FROM `{$table}`");
        if ($count && ($row = $count->fetch_row())) {
            $rows += (int) $row[0];
        }
    }
    if ($rows > 0) {
        error_log('[EduPortal] Careers tables use the pre-specification schema and hold ' . $rows . ' row(s); leaving them untouched. Migrate manually, then reload.');
        return;
    }

    $db->query('DROP TABLE IF EXISTS ep_job_applications');
    $db->query('DROP TABLE IF EXISTS ep_jobs');
}

/** True when the careers tables exist, so public pages never fatal pre-migration. */
function ep_careers_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    global $m;
    try {
        $res = $m->mysqli()->query("SHOW TABLES LIKE 'ep_jobs'");
        $ready = $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/* ---------------------------------------------------------------------
 * Vocabularies
 *
 * job_type, work_mode and the two status columns store their display text
 * verbatim ("Full Time", "Onsite", "Active"), as specified. Every write is
 * validated against these lists, so the columns stay a closed set.
 * ------------------------------------------------------------------ */

/** @return array<int, string> */
function ep_job_types(): array
{
    return ['Full Time', 'Part Time', 'Contract', 'Internship'];
}

/** @return array<int, string> */
function ep_job_work_modes(): array
{
    return ['Onsite', 'Remote', 'Hybrid'];
}

/** @return array<int, string> */
function ep_job_statuses(): array
{
    return ['Draft', 'Active', 'Closed'];
}

/** @return array<int, string> */
function ep_application_statuses(): array
{
    return ['New', 'Shortlisted', 'Interviewed', 'Rejected', 'Hired'];
}

/**
 * The only accepted answers to "How soon can you join?" (spec 7.4 field 10).
 *
 * @return array<int, string>
 */
function ep_careers_joining_times(): array
{
    return ['Immediately', 'Within 15 days', '1 month', '2 months', '3 months'];
}

/** Common departments offered in the admin form; free text is still accepted. */
function ep_job_departments(): array
{
    return ['Engineering', 'Sales', 'Support', 'Marketing'];
}

/** Normalises a posted value to one of $allowed, falling back to $default. */
function ep_careers_pick(string $value, array $allowed, string $default): string
{
    $value = trim($value);
    foreach ($allowed as $candidate) {
        if (strcasecmp($value, $candidate) === 0) {
            return $candidate;
        }
    }
    return $default;
}

/** schema.org employmentType value for a stored job_type. */
function ep_job_schema_employment_type(string $jobType): string
{
    static $map = [
        'Full Time' => 'FULL_TIME',
        'Part Time' => 'PART_TIME',
        'Contract' => 'CONTRACTOR',
        'Internship' => 'INTERN',
    ];
    return $map[$jobType] ?? 'OTHER';
}

/* ---------------------------------------------------------------------
 * Application form field configuration
 *
 * Which fields appear on the apply form, and which are compulsory, is set in
 * the admin panel (Careers -> Application Form) rather than hard-coded here.
 *
 * The important part is that ONE definition drives both the rendered form and
 * the server-side validation. Before this existed the two had drifted: the
 * form had stopped marking name, city, years of experience, expected salary
 * and consent as required, while the validator still rejected a submission
 * without them, so applicants were told to fill in fields the form had told
 * them were optional.
 *
 * Two kinds of field live side by side here:
 *  - "core" fields map to a real column on ep_job_applications. Their key and
 *    type are fixed in code — only their label/hint and required/optional/
 *    hidden state can be changed from the admin panel.
 *  - custom fields are entirely admin-defined (Careers -> Application Form ->
 *    Add a field) and have no dedicated column; their submitted values are
 *    stored together as JSON in ep_job_applications.extra_fields.
 * ------------------------------------------------------------------ */

/** @return array<string,string> machine type => label shown in the admin panel */
function ep_application_custom_field_types(): array
{
    return [
        'text' => 'Short text',
        'textarea' => 'Long text',
        'email' => 'Email address',
        'tel' => 'Phone number',
        'url' => 'Web link (URL)',
        'number' => 'Number',
        'select' => 'Dropdown',
        'checkbox' => 'Checkbox (yes/no)',
    ];
}

/**
 * Admin-added custom fields, keyed by field key.
 *
 * @return array<string, array{label: string, hint: string, type: string, options: array<int,string>}>
 */
function ep_application_custom_fields(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $allowedTypes = ep_application_custom_field_types();
    $raw = json_decode((string) ep_setting('application_custom_fields', ''), true);
    $out = [];
    if (is_array($raw)) {
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = (string) ($row['key'] ?? '');
            $label = trim((string) ($row['label'] ?? ''));
            $type = (string) ($row['type'] ?? 'text');
            if ($key === '' || $label === '' || !isset($allowedTypes[$type])) {
                continue;
            }
            $options = [];
            if ($type === 'select' && is_array($row['options'] ?? null)) {
                foreach ($row['options'] as $opt) {
                    $opt = trim((string) $opt);
                    if ($opt !== '') {
                        $options[] = $opt;
                    }
                }
            }
            $out[$key] = [
                'label' => $label,
                'hint' => trim((string) ($row['hint'] ?? '')),
                'type' => $type,
                'options' => $options,
            ];
        }
    }

    $cache = $out;
    return $cache;
}

/**
 * Every configurable field on the application form: the fixed core fields,
 * with any admin label/hint edits applied, plus every admin-added custom
 * field.
 *
 * 'lock' pins a field to a state the admin cannot change, where letting them
 * change it would break the form or the record it produces.
 *
 * @return array<string, array{label: string, hint: string, lock: ?string, type: string, core: bool, options: array<int,string>}>
 */
function ep_application_field_defs(): array
{
    $core = [
        'full_name'           => ['label' => 'Full name',            'hint' => '', 'lock' => null, 'type' => 'text'],
        'email'               => ['label' => 'Email',                'hint' => '', 'lock' => null, 'type' => 'email'],
        'whatsapp'            => ['label' => 'WhatsApp number',      'hint' => 'Include your country code — this is how our HR team will contact you.', 'lock' => null, 'type' => 'tel'],
        'city'                => ['label' => 'City',                 'hint' => '', 'lock' => null, 'type' => 'text'],
        'photo'               => ['label' => 'Profile photo',        'hint' => 'JPG or PNG · 2 MB maximum', 'lock' => null, 'type' => 'file'],
        'resume'              => ['label' => 'Resume / CV',          'hint' => 'PDF, DOC or DOCX · 5 MB maximum', 'lock' => null, 'type' => 'file'],
        'years_experience'    => ['label' => 'Years of experience',  'hint' => '', 'lock' => null, 'type' => 'text'],
        'relevant_experience' => ['label' => 'Relevant experience',  'hint' => '', 'lock' => null, 'type' => 'textarea'],
        'expected_salary'     => ['label' => 'Expected salary',      'hint' => '', 'lock' => null, 'type' => 'text'],
        'joining_time'        => ['label' => 'How soon can you join?', 'hint' => '', 'lock' => null, 'type' => 'select'],
        'linkedin'            => ['label' => 'LinkedIn profile',     'hint' => '', 'lock' => null, 'type' => 'url'],
        // An optional consent tick-box means nothing — either you are
        // recording consent or you are not. So it may be required or hidden,
        // never optional.
        'consent'             => ['label' => 'Recruitment-data consent', 'hint' => 'Required if you want a recorded consent for storing applicant data.', 'lock' => 'no-optional', 'type' => 'checkbox'],
    ];

    // Labels/hints of core fields can be edited from the admin panel; the
    // key, type and lock cannot — real code and a real database column
    // depend on those.
    $overrides = json_decode((string) ep_setting('application_field_labels', ''), true);
    if (is_array($overrides)) {
        foreach ($overrides as $key => $ov) {
            if (!isset($core[$key]) || !is_array($ov)) {
                continue;
            }
            if (isset($ov['label']) && trim((string) $ov['label']) !== '') {
                $core[$key]['label'] = trim((string) $ov['label']);
            }
            if (isset($ov['hint'])) {
                $core[$key]['hint'] = trim((string) $ov['hint']);
            }
        }
    }

    $defs = [];
    foreach ($core as $key => $def) {
        $def['core'] = true;
        $def['options'] = [];
        $defs[$key] = $def;
    }
    foreach (ep_application_custom_fields() as $key => $custom) {
        $defs[$key] = [
            'label' => $custom['label'],
            'hint' => $custom['hint'],
            'lock' => null,
            'type' => $custom['type'],
            'core' => false,
            'options' => $custom['options'],
        ];
    }

    return $defs;
}

/** @return array<int, string> */
function ep_application_field_states(): array
{
    return ['required', 'optional', 'hidden'];
}

/**
 * The configured state of every field, falling back to sensible defaults for
 * anything not yet saved.
 *
 * @return array<string, string>
 */
function ep_application_fields(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    // These defaults match what is currently live on the public form, so
    // introducing admin control does not silently change what applicants are
    // required to submit. From here on, the admin panel is authoritative.
    $defaults = [
        'full_name' => 'optional',   'email' => 'optional',
        'whatsapp' => 'required',    'city' => 'required',
        'photo' => 'required',       'resume' => 'required',
        'years_experience' => 'required', 'relevant_experience' => 'optional',
        'expected_salary' => 'required',  'joining_time' => 'required',
        'linkedin' => 'optional',    'consent' => 'required',
    ];

    // A newly-added custom field starts optional until the admin panel says
    // otherwise (its actual initial state is written to the same saved JSON
    // at creation time, so this is only the fallback if that is missing).
    foreach (ep_application_custom_fields() as $key => $custom) {
        $defaults[$key] = 'optional';
    }

    $saved = json_decode((string) ep_setting('application_form_fields', ''), true);
    if (is_array($saved)) {
        foreach ($saved as $key => $state) {
            if (isset($defaults[$key]) && in_array($state, ep_application_field_states(), true)) {
                $defaults[$key] = (string) $state;
            }
        }
    }

    // Consent is never merely optional — see ep_application_field_defs().
    if ($defaults['consent'] === 'optional') {
        $defaults['consent'] = 'required';
    }

    // HR has to be able to reply. If neither email nor WhatsApp is compulsory
    // the form can produce an application nobody can respond to, so WhatsApp
    // is forced back on rather than silently allowing that.
    if ($defaults['email'] !== 'required' && $defaults['whatsapp'] !== 'required') {
        $defaults['whatsapp'] = 'required';
    }

    $cache = $defaults;
    return $cache;
}

/** 'required' | 'optional' | 'hidden' for one field. */
function ep_application_field_state(string $field): string
{
    return ep_application_fields()[$field] ?? 'hidden';
}

function ep_application_field_visible(string $field): bool
{
    return ep_application_field_state($field) !== 'hidden';
}

function ep_application_field_required(string $field): bool
{
    return ep_application_field_state($field) === 'required';
}

/**
 * One saved application's custom-field answers as label => display-value
 * pairs, for the admin detail view. A field deleted from the admin panel
 * after applications were already received still shows, under its raw key,
 * rather than silently disappearing from a submitted applicant's record.
 *
 * @return array<string, string>
 */
function ep_careers_extra_fields_display(array $application): array
{
    $raw = json_decode((string) ($application['extra_fields'] ?? ''), true);
    if (!is_array($raw) || !$raw) {
        return [];
    }

    $defs = ep_application_field_defs();
    $out = [];
    foreach ($raw as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $label = $defs[$key]['label'] ?? ucwords(str_replace(['_', '-'], ' ', (string) $key));
        $type = $defs[$key]['type'] ?? 'text';
        $out[$label] = $type === 'checkbox' ? (($value === '1') ? 'Yes' : 'No') : (string) $value;
    }
    return $out;
}

/**
 * Insert-or-update one ep_site_settings row. Shared by every admin action
 * that writes application-form configuration (states, label overrides,
 * custom field definitions), so they do not each repeat the same
 * select-then-insert/update dance.
 */
function ep_careers_save_setting(string $key, string $value): void
{
    global $m;
    $m->where('setting_key', $key);
    if ($m->getOne('ep_site_settings')) {
        $m->where('setting_key', $key);
        $m->update('ep_site_settings', ['setting_value' => $value]);
    } else {
        $m->insert('ep_site_settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_group' => 'careers',
        ]);
    }
}

/* ---------------------------------------------------------------------
 * URLs
 * ------------------------------------------------------------------ */

function ep_careers_url(): string
{
    return ep_url('careers');
}

function ep_job_url(array $job): string
{
    return ep_url('careers/' . rawurlencode((string) ($job['slug'] ?? '')));
}

/* ---------------------------------------------------------------------
 * Bullet lists (requirements / responsibilities / benefits)
 * ------------------------------------------------------------------ */

/**
 * Decodes a JSON bullet array from the database.
 *
 * Falls back to newline splitting if the column ever holds plain text, so a
 * hand-edited row renders instead of blanking the section.
 *
 * @return array<int, string>
 */
function ep_job_bullets(?string $stored): array
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return [];
    }

    $decoded = json_decode($stored, true);
    if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $item) {
            if (is_scalar($item)) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $out[] = $item;
                }
            }
        }
        return $out;
    }

    return ep_job_bullets_from_lines($stored);
}

/**
 * Splits a one-per-line textarea value into clean bullets.
 *
 * @return array<int, string>
 */
function ep_job_bullets_from_lines(string $text): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
        $line = trim(ltrim($line, "-*• \t"));
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * Encodes the rows collected by the admin bullet builder into the JSON array
 * the column stores. Blank rows are dropped and each row is length-capped, so
 * a hand-crafted POST cannot bloat the column.
 *
 * @param mixed $rows Normally the posted array of bullet strings
 */
function ep_job_bullets_encode($rows): string
{
    if (!is_array($rows)) {
        // Tolerate a newline-separated string, e.g. from an older saved form.
        $rows = ep_job_bullets_from_lines((string) $rows);
    }

    $bullets = [];
    foreach ($rows as $row) {
        if (!is_scalar($row)) {
            continue;
        }
        $row = trim((string) $row);
        if ($row !== '') {
            $bullets[] = mb_substr($row, 0, 300);
        }
        if (count($bullets) >= 50) {
            break;
        }
    }

    return $bullets ? (string) json_encode($bullets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
}

/* ---------------------------------------------------------------------
 * Reads
 * ------------------------------------------------------------------ */

/**
 * Active jobs, most-featured first, optionally filtered.
 * A job is public only while status='Active' AND its deadline has not passed.
 *
 * @param array<string, string> $filters department|location|job_type|work_mode|q
 * @return array<int, array<string, mixed>>
 */
function ep_get_open_jobs(array $filters = []): array
{
    if (!ep_careers_tables_ready()) {
        return [];
    }
    global $m;

    $m->where('status', 'Active');
    $m->where('(deadline IS NULL OR deadline >= CURDATE())');

    foreach (['department', 'location', 'job_type', 'work_mode'] as $field) {
        $value = trim((string) ($filters[$field] ?? ''));
        if ($value !== '') {
            $m->where($field, $value);
        }
    }

    $search = trim((string) ($filters['q'] ?? ''));
    if ($search !== '') {
        $like = '%' . $search . '%';
        $m->where('(title LIKE ? OR summary LIKE ? OR skills LIKE ? OR department LIKE ?)', [$like, $like, $like, $like]);
    }

    $m->orderBy('is_featured', 'DESC');
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('posted_at', 'DESC');
    $m->orderBy('id', 'DESC');

    return $m->get('ep_jobs') ?: [];
}

/** Number of jobs currently open, ignoring any active filter. */
function ep_count_open_jobs(): int
{
    if (!ep_careers_tables_ready()) {
        return 0;
    }
    global $m;
    $row = $m->rawQueryOne(
        "SELECT COUNT(*) AS c FROM ep_jobs WHERE status = 'Active' AND (deadline IS NULL OR deadline >= CURDATE())"
    );
    return (int) ($row['c'] ?? 0);
}

/** A single active, in-date job by slug. */
function ep_get_job_by_slug(string $slug): ?array
{
    if (!ep_careers_tables_ready() || $slug === '') {
        return null;
    }
    global $m;
    $m->where('slug', $slug);
    $m->where('status', 'Active');
    $m->where('(deadline IS NULL OR deadline >= CURDATE())');
    $job = $m->getOne('ep_jobs');
    return $job ?: null;
}

/**
 * Distinct filter values across the currently open jobs, so the filter
 * dropdowns never offer a value that would return nothing.
 *
 * @return array<string, array<int, string>>
 */
function ep_careers_filter_options(): array
{
    $empty = ['department' => [], 'location' => [], 'job_type' => [], 'work_mode' => []];
    if (!ep_careers_tables_ready()) {
        return $empty;
    }
    global $m;
    $rows = $m->rawQuery(
        "SELECT DISTINCT department, location, job_type, work_mode
         FROM ep_jobs
         WHERE status = 'Active' AND (deadline IS NULL OR deadline >= CURDATE())"
    ) ?: [];

    $out = $empty;
    foreach ($rows as $row) {
        foreach (array_keys($out) as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '' && !in_array($value, $out[$field], true)) {
                $out[$field][] = $value;
            }
        }
    }
    foreach (array_keys($out) as $field) {
        sort($out[$field], SORT_NATURAL | SORT_FLAG_CASE);
    }
    return $out;
}

/** @return array<int, string> */
function ep_job_skills(array $job): array
{
    $parts = array_map('trim', explode(',', (string) ($job['skills'] ?? '')));
    return array_values(array_filter($parts, static fn(string $s): bool => $s !== ''));
}

/**
 * Human-readable salary range, or '' when no salary was entered.
 * Salaries are stored as plain monthly PKR figures per the specification.
 */
function ep_job_salary_display(array $job): string
{
    $min = (int) ($job['salary_min'] ?? 0);
    $max = (int) ($job['salary_max'] ?? 0);
    if ($min <= 0 && $max <= 0) {
        return '';
    }
    $amount = $min > 0 && $max > 0 && $max !== $min
        ? number_format($min) . ' – ' . number_format($max)
        : number_format($min > 0 ? $min : $max);
    return ep_careers_salary_currency() . ' ' . $amount . ' / month';
}

/** Currency for job salaries, derived from the existing office_country setting. */
function ep_careers_salary_currency(): string
{
    static $map = ['PK' => 'PKR', 'AE' => 'AED', 'SA' => 'SAR', 'GB' => 'GBP', 'US' => 'USD'];
    $country = (string) ep_setting('office_country', 'PK');
    return $map[$country] ?? 'PKR';
}

/** Meta description for a job page, falling back to its summary/description. */
function ep_job_meta_description(array $job): string
{
    $explicit = trim((string) ($job['meta_description'] ?? ''));
    if ($explicit !== '') {
        return $explicit;
    }
    $summary = trim((string) ($job['summary'] ?? ''));
    if ($summary === '') {
        $summary = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($job['description'] ?? ''))));
    }
    if ($summary === '') {
        $summary = 'Apply for the ' . (string) ($job['title'] ?? '') . ' role at EduPortal.';
    }
    return mb_substr($summary, 0, 300);
}

/* ---------------------------------------------------------------------
 * JobPosting structured data
 * ------------------------------------------------------------------ */

/**
 * Google JobPosting JSON-LD for one job. Only fields we actually hold are
 * emitted — no invented salaries, locations, or dates (the same rule the
 * existing ep_organization_schema() follows).
 *
 * @return array<string, mixed>
 */
function ep_job_posting_schema(array $job): array
{
    $base = 'https://eduportal.pk/';
    $siteName = ep_setting('site_name', 'EduPortal');

    $descriptionParts = [];
    $description = trim((string) ($job['description'] ?? ''));
    if ($description !== '') {
        $descriptionParts[] = '<p>' . nl2br(ep_h($description)) . '</p>';
    }
    foreach ([
        'Responsibilities' => ep_job_bullets($job['responsibilities'] ?? ''),
        'Requirements' => ep_job_bullets($job['requirements'] ?? ''),
        'Benefits' => ep_job_bullets($job['benefits'] ?? ''),
    ] as $heading => $bullets) {
        if (!$bullets) {
            continue;
        }
        $descriptionParts[] = '<h3>' . $heading . '</h3><ul><li>'
            . implode('</li><li>', array_map('ep_h', $bullets)) . '</li></ul>';
    }
    if (!$descriptionParts) {
        $descriptionParts[] = '<p>' . ep_h((string) ($job['summary'] ?? $job['title'] ?? '')) . '</p>';
    }

    $posted = $job['posted_at'] ?? $job['created_at'] ?? null;

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => (string) $job['title'],
        'description' => implode('', $descriptionParts),
        'identifier' => [
            '@type' => 'PropertyValue',
            'name' => $siteName,
            'value' => (string) $job['slug'],
        ],
        'hiringOrganization' => [
            '@type' => 'Organization',
            '@id' => $base . '#organization',
            'name' => $siteName,
            'sameAs' => rtrim($base, '/'),
            'logo' => $base . 'assets/logo_icon.jpg',
        ],
        'employmentType' => ep_job_schema_employment_type((string) $job['job_type']),
        'url' => ep_job_url($job),
        'directApply' => true,
    ];

    if ($posted) {
        $schema['datePosted'] = date('Y-m-d', strtotime((string) $posted));
    }
    if (!empty($job['deadline'])) {
        $schema['validThrough'] = date('Y-m-d\TH:i:s', strtotime((string) $job['deadline'] . ' 23:59:59'));
    }
    if (!empty($job['department'])) {
        $schema['occupationalCategory'] = (string) $job['department'];
    }
    if ((int) ($job['vacancies'] ?? 0) > 0) {
        $schema['totalJobOpenings'] = (int) $job['vacancies'];
    }
    // "experience" is free text ("2-4 years"); publish the leading number of
    // years when there is one, and never guess when there is not.
    if (!empty($job['experience']) && preg_match('/(\d+)/', (string) $job['experience'], $match)) {
        $schema['experienceRequirements'] = [
            '@type' => 'OccupationalExperienceRequirements',
            'monthsOfExperience' => (int) $match[1] * 12,
        ];
    }
    $skills = ep_job_skills($job);
    if ($skills) {
        $schema['skills'] = implode(', ', $skills);
    }

    $workMode = (string) ($job['work_mode'] ?? 'Onsite');
    $location = trim((string) ($job['location'] ?? ''));
    $country = (string) ep_setting('office_country', 'PK');

    if ($workMode === 'Remote') {
        $schema['jobLocationType'] = 'TELECOMMUTE';
        if ($country !== '') {
            $schema['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => $country];
        }
    }
    if ($workMode !== 'Remote' && $location !== '') {
        $address = ['@type' => 'PostalAddress', 'addressLocality' => $location];
        $region = (string) ep_setting('office_region', '');
        if ($region !== '') {
            $address['addressRegion'] = $region;
        }
        if ($country !== '') {
            $address['addressCountry'] = $country;
        }
        $schema['jobLocation'] = ['@type' => 'Place', 'address' => $address];
    }

    $min = (int) ($job['salary_min'] ?? 0);
    $max = (int) ($job['salary_max'] ?? 0);
    if ($min > 0 || $max > 0) {
        $value = ['@type' => 'QuantitativeValue', 'unitText' => 'MONTH'];
        if ($min > 0 && $max > 0 && $max !== $min) {
            $value['minValue'] = $min;
            $value['maxValue'] = $max;
        } else {
            $value['value'] = $min > 0 ? $min : $max;
        }
        $schema['baseSalary'] = [
            '@type' => 'MonetaryAmount',
            'currency' => ep_careers_salary_currency(),
            'value' => $value,
        ];
    }

    return $schema;
}

/* ---------------------------------------------------------------------
 * Secure upload storage
 * ------------------------------------------------------------------ */

function ep_careers_upload_root(): string
{
    return dirname(__DIR__) . '/uploads/careers';
}

function ep_careers_cv_dir(): string
{
    return ep_careers_upload_root() . '/cv';
}

function ep_careers_photo_dir(): string
{
    return ep_careers_upload_root() . '/photos';
}

/**
 * Hardened .htaccess dropped into uploads/careers: denies direct HTTP
 * access outright and, independently, strips every script handler so an
 * uploaded file can never be executed even if the deny rule is bypassed
 * or AllowOverride is loosened. Résumés and photos are only ever reachable
 * through cms/application-file.php, which requires an admin session.
 */
function ep_careers_upload_htaccess(): string
{
    return <<<HTACCESS
    # Applicant résumés and photos: private data, never web-servable.
    # Served only via cms/application-file.php, which requires an admin session.
    <IfModule mod_authz_core.c>
      Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
      Order allow,deny
      Deny from all
    </IfModule>

    # Independent second layer: never execute anything in this directory,
    # whatever the file is named or however it got here.
    <IfModule mod_php.c>
      php_flag engine off
    </IfModule>
    <IfModule mod_php7.c>
      php_flag engine off
    </IfModule>
    <IfModule mod_php8.c>
      php_flag engine off
    </IfModule>
    RemoveHandler .php .phtml .php3 .php4 .php5 .php6 .php7 .php8 .phps .pht .phar .cgi .pl .py .sh .shtml
    RemoveType .php .phtml .php3 .php4 .php5 .php6 .php7 .php8 .phps .pht .phar
    AddType text/plain .php .phtml .php3 .php4 .php5 .php6 .php7 .php8 .phps .pht .phar
    Options -ExecCGI -Indexes
    HTACCESS;
}

function ep_careers_ensure_upload_dirs(): void
{
    foreach ([ep_careers_upload_root(), ep_careers_cv_dir(), ep_careers_photo_dir()] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
    $htaccess = ep_careers_upload_root() . '/.htaccess';
    $expected = ep_careers_upload_htaccess();
    if (!is_file($htaccess) || @file_get_contents($htaccess) !== $expected) {
        @file_put_contents($htaccess, $expected);
    }
    // Empty index.html stubs, so a mis-configured server still lists nothing.
    foreach ([ep_careers_cv_dir(), ep_careers_photo_dir()] as $dir) {
        $index = $dir . '/index.html';
        if (!is_file($index)) {
            @file_put_contents($index, '');
        }
    }
}

/**
 * Absolute path for a stored relative upload path, or '' when the path is
 * not really inside the careers upload root — this is what keeps the file
 * streamer safe against traversal from a tampered database value.
 */
function ep_careers_file_abs_path(string $relPath): string
{
    $relPath = trim(str_replace('\\', '/', $relPath));
    if ($relPath === '' || str_contains($relPath, '..')) {
        return '';
    }
    if (!str_starts_with($relPath, 'uploads/careers/')) {
        return '';
    }
    $real = realpath(dirname(__DIR__) . '/' . $relPath);
    $root = realpath(ep_careers_upload_root());
    if ($real === false || $root === false) {
        return '';
    }
    $real = str_replace('\\', '/', $real);
    $root = str_replace('\\', '/', $root);
    return str_starts_with($real, $root . '/') ? $real : '';
}

function ep_careers_delete_file(string $relPath): void
{
    $abs = ep_careers_file_abs_path($relPath);
    if ($abs !== '' && is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * Detects the real MIME type by inspecting the file's contents with
 * finfo_file(). The browser-supplied type and the filename extension are
 * never trusted on their own — that is exactly how a PHP script gets
 * uploaded wearing a .jpg extension.
 */
function ep_careers_detect_mime(string $tmpPath): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }
    }
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return strtolower($mime);
        }
    }
    return '';
}

/** Random, non-guessable storage filename — the original name is never used on disk. */
function ep_careers_random_filename(string $ext): string
{
    return date('Ymd') . '-' . bin2hex(random_bytes(16)) . '.' . $ext;
}

/** Safe display/download filename derived from the applicant's original name. */
function ep_careers_safe_display_name(string $original, string $ext): string
{
    $base = (string) preg_replace('/[^A-Za-z0-9 _.-]+/', '', pathinfo($original, PATHINFO_FILENAME));
    $base = trim((string) preg_replace('/\s+/', ' ', $base));
    if ($base === '') {
        $base = 'resume';
    }
    return mb_substr($base, 0, 80) . '.' . $ext;
}

/**
 * Validates and stores an uploaded résumé. The declared extension, the MIME
 * type detected from the file's contents, and its magic bytes must all agree —
 * no single one of them is trusted on its own.
 *
 * @param array<string, mixed>|null $file
 * @return array{ok: bool, error: string, path?: string, name?: string, mime?: string, size?: int}
 */
function ep_careers_store_resume(?array $file): array
{
    $maxBytes = 5 * 1024 * 1024;

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please attach your résumé (PDF, DOC or DOCX).'];
    }
    $err = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Your résumé is too large. The maximum size is 5 MB.'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Your résumé could not be uploaded. Please try again.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Your résumé could not be uploaded. Please try again.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size < 1) {
        return ['ok' => false, 'error' => 'Your résumé file appears to be empty.'];
    }
    if ($size > $maxBytes) {
        return ['ok' => false, 'error' => 'Your résumé is too large. The maximum size is 5 MB.'];
    }

    $originalName = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        return ['ok' => false, 'error' => 'Résumés must be a PDF, DOC or DOCX file.'];
    }

    // Server-side MIME check against the real contents. Each extension has an
    // explicit allow-list of the types servers actually report for it.
    $mime = ep_careers_detect_mime($tmp);
    $allowedByExt = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
    ];
    if (!in_array($mime, $allowedByExt[$ext], true)) {
        return ['ok' => false, 'error' => 'That file does not look like a valid ' . strtoupper($ext) . ' document.'];
    }

    // Magic-byte check on top of finfo — this is what actually rules out a
    // script or image renamed to .pdf/.docx.
    $head = (string) @file_get_contents($tmp, false, null, 0, 8);
    $magicOk = ($ext === 'pdf' && str_starts_with($head, '%PDF-'))
        || ($ext === 'docx' && str_starts_with($head, "PK\x03\x04"))
        || ($ext === 'doc' && str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"));
    if (!$magicOk) {
        return ['ok' => false, 'error' => 'That file does not look like a valid ' . strtoupper($ext) . ' document.'];
    }

    ep_careers_ensure_upload_dirs();
    $storedName = ep_careers_random_filename($ext);
    $absPath = ep_careers_cv_dir() . '/' . $storedName;
    if (!move_uploaded_file($tmp, $absPath)) {
        return ['ok' => false, 'error' => 'Your résumé could not be saved. Please try again.'];
    }
    @chmod($absPath, 0644);

    static $canonicalMime = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    return [
        'ok' => true,
        'error' => '',
        'path' => 'uploads/careers/cv/' . $storedName,
        'name' => ep_careers_safe_display_name($originalName, $ext),
        'mime' => $canonicalMime[$ext],
        'size' => $size,
    ];
}

/**
 * Validates and stores the applicant's profile photo (required, spec 7.4).
 *
 * JPG or PNG only, 2 MB maximum. The image is re-encoded through GD to a
 * 400x400 JPEG, so anything the original file carried besides pixels — an
 * appended script, a polyglot header, EXIF payload — is discarded rather
 * than merely rejected.
 *
 * @param array<string, mixed>|null $file
 * @return array{ok: bool, error: string, path?: string}
 */
function ep_careers_store_photo(?array $file): array
{
    $maxBytes = 2 * 1024 * 1024;

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please attach a profile photo (JPG or PNG).'];
    }
    $err = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Your photo is too large. The maximum size is 2 MB.'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Your photo could not be uploaded. Please try again.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Your photo could not be uploaded. Please try again.'];
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1) {
        return ['ok' => false, 'error' => 'Your photo file appears to be empty.'];
    }
    if ($size > $maxBytes) {
        return ['ok' => false, 'error' => 'Your photo is too large. The maximum size is 2 MB.'];
    }

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        return ['ok' => false, 'error' => 'Photos must be a JPG or PNG image.'];
    }

    // The type detected from the contents must agree with the extension.
    $mime = ep_careers_detect_mime($tmp);
    $allowedByExt = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];
    if (!in_array($mime, $allowedByExt[$ext], true)) {
        return ['ok' => false, 'error' => 'That file is not a valid JPG or PNG image.'];
    }
    $info = @getimagesize($tmp);
    if ($info === false || (int) $info[0] < 1 || (int) $info[1] < 1) {
        return ['ok' => false, 'error' => 'That file is not a valid image.'];
    }
    if (!in_array((int) $info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        return ['ok' => false, 'error' => 'That file is not a valid JPG or PNG image.'];
    }

    ep_careers_ensure_upload_dirs();
    $storedName = ep_careers_random_filename('jpg');
    $absPath = ep_careers_photo_dir() . '/' . $storedName;
    if (!ep_careers_reencode_photo($tmp, $absPath)) {
        return ['ok' => false, 'error' => 'Your photo could not be processed. Please try a different image.'];
    }
    @chmod($absPath, 0644);

    return ['ok' => true, 'error' => '', 'path' => 'uploads/careers/photos/' . $storedName];
}

/**
 * Re-encodes an uploaded image to exactly 400x400 JPEG, centre-cropping the
 * longer side first so faces stay centred and no photo is stretched.
 */
function ep_careers_reencode_photo(string $sourceTmp, string $destAbs): bool
{
    if (!extension_loaded('gd')) {
        // Without GD the piggy-backed-payload risk cannot be removed by
        // re-encoding, so the photo is rejected rather than stored unverified.
        return false;
    }
    $raw = @file_get_contents($sourceTmp);
    if ($raw === false) {
        return false;
    }
    $src = @imagecreatefromstring($raw);
    if ($src === false) {
        return false;
    }
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW < 1 || $srcH < 1) {
        imagedestroy($src);
        return false;
    }

    // Largest centred square available in the source.
    $side = min($srcW, $srcH);
    $srcX = (int) round(($srcW - $side) / 2);
    $srcY = (int) round(($srcH - $side) / 2);

    $work = imagecreatetruecolor(400, 400);
    imagefill($work, 0, 0, imagecolorallocate($work, 255, 255, 255));
    imagecopyresampled($work, $src, 0, 0, $srcX, $srcY, 400, 400, $side, $side);
    imagedestroy($src);

    ob_start();
    imagejpeg($work, null, 85);
    $blob = ob_get_clean();
    imagedestroy($work);

    return is_string($blob) && $blob !== '' && file_put_contents($destAbs, $blob) !== false;
}

/* ---------------------------------------------------------------------
 * Public form protection: CSRF, honeypot, rate limiting
 * ------------------------------------------------------------------ */

function ep_careers_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['ep_careers_csrf'])) {
        $_SESSION['ep_careers_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['ep_careers_csrf'];
}

function ep_careers_verify_csrf(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $expected = (string) ($_SESSION['ep_careers_csrf'] ?? '');
    return $expected !== '' && is_string($token) && $token !== '' && hash_equals($expected, $token);
}


function ep_careers_client_ip(): string
{
    return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/**
 * Rate limits by client IP using the applications already stored — no
 * separate counter table to keep in sync. Returns the message to show when
 * the caller should be blocked, or '' when the submission may proceed.
 */
function ep_careers_rate_limited(string $email, ?int $jobId): string
{
    global $m;
    $ip = ep_careers_client_ip();
    if ($ip === '') {
        return '';
    }

    $row = $m->rawQueryOne(
        'SELECT COUNT(*) AS c FROM ep_job_applications WHERE ip_address = ? AND applied_at >= (NOW() - INTERVAL 1 HOUR)',
        [$ip]
    );
    if ((int) ($row['c'] ?? 0) >= 3) {
        return 'You have already submitted three applications in the past hour. Please try again later.';
    }

    $row = $m->rawQueryOne(
        'SELECT COUNT(*) AS c FROM ep_job_applications WHERE ip_address = ? AND applied_at >= (NOW() - INTERVAL 1 DAY)',
        [$ip]
    );
    if ((int) ($row['c'] ?? 0) >= 15) {
        return 'The daily application limit has been reached from this connection. Please try again tomorrow.';
    }

    if ($email !== '') {
        $sql = 'SELECT COUNT(*) AS c FROM ep_job_applications WHERE email = ? AND applied_at >= (NOW() - INTERVAL 1 DAY) AND '
            . ($jobId ? 'job_id = ?' : 'job_id IS NULL');
        $row = $m->rawQueryOne($sql, $jobId ? [$email, $jobId] : [$email]);
        if ((int) ($row['c'] ?? 0) >= 1) {
            return 'We already have an application from this email address for this role. Our team will be in touch.';
        }
    }

    return '';
}

/* ---------------------------------------------------------------------
 * Submission
 * ------------------------------------------------------------------ */

/**
 * Validates and saves a public application (job-specific, or a general CV
 * when $job is null).
 *
 * @param array<string, mixed> $post   Raw $_POST
 * @param array<string, mixed> $files  Raw $_FILES
 * @param array<string, mixed>|null $job
 * @return array{ok: bool, errors: array<int, string>, values: array<string, string>, id?: int}
 */
function ep_careers_handle_submission(array $post, array $files, ?array $job): array
{
    global $m;

    $values = [];
    foreach ([
        'full_name', 'email', 'whatsapp', 'city', 'years_experience',
        'relevant_experience', 'expected_salary', 'joining_time', 'linkedin',
    ] as $field) {
        $values[$field] = trim((string) ($post[$field] ?? ''));
    }
    // Checkbox state has to survive a failed submission too.
    $values['consent'] = isset($post['consent']) ? '1' : '';

    // Admin-added custom fields — collected the same way as the fixed ones,
    // keyed separately so they can never collide with a real column name.
    $customDefs = ep_application_custom_fields();
    $values['custom'] = [];
    foreach ($customDefs as $key => $def) {
        $values['custom'][$key] = $def['type'] === 'checkbox'
            ? (isset($post['custom'][$key]) ? '1' : '')
            : trim((string) ($post['custom'][$key] ?? ''));
    }

    $errors = [];

    // --- Abuse checks first: never touch uploads on behalf of an obvious bot.
    if (!ep_careers_verify_csrf($post['_csrf'] ?? null)) {
        return ['ok' => false, 'errors' => ['Your session expired. Please reload the page and try again.'], 'values' => $values];
    }
    // Honeypot — a hidden field real applicants never see, let alone fill in.
    if (trim((string) ($post['website'] ?? '')) !== '') {
        return ['ok' => false, 'errors' => ['Your application could not be submitted.'], 'values' => $values];
    }
    
    // A form completed in under three seconds was not filled in by a person.
    $startedAt = (int) ($post['form_started'] ?? 0);
    if ($startedAt > 0 && (time() - $startedAt) < 3) {
        $errors[] = 'Your application was submitted too quickly. Please try again.';
    }

    // --- Field validation
    if ($values['full_name'] === '') {
        if (ep_application_field_required('full_name')) {
            $errors[] = 'Please enter your full name.';
        }
    } elseif (mb_strlen($values['full_name']) > 150) {
        $errors[] = 'Your name is too long.';
    }

    if ($values['email'] === '') {
        if (ep_application_field_required('email')) {
            $errors[] = 'Please enter your email address.';
        }
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($values['email']) > 150) {
        $errors[] = 'Your email address is too long.';
    }
    // Stored as digits only, so wa.me links work without further cleaning.
    $whatsappDigits = (string) preg_replace('/\D+/', '', $values['whatsapp']);
    if ($whatsappDigits === '') {
        if (ep_application_field_required('whatsapp')) {
            $errors[] = 'Please enter your WhatsApp number including the country code.';
        }
    } elseif (strlen($whatsappDigits) < 9 || strlen($whatsappDigits) > 20) {
        $errors[] = 'Please enter a valid WhatsApp number including the country code.';
    }

    if ($values['linkedin'] === '') {
        if (ep_application_field_required('linkedin')) {
            $errors[] = 'Please enter your LinkedIn profile URL.';
        }
    } elseif (!filter_var($values['linkedin'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $values['linkedin'])) {
        $errors[] = 'Please enter a valid LinkedIn URL (starting with http:// or https://).';
    } elseif (mb_strlen($values['linkedin']) > 300) {
        $errors[] = 'Your LinkedIn URL is too long.';
    }

    if ($values['city'] === '') {
        if (ep_application_field_required('city')) {
            $errors[] = 'Please enter your city.';
        }
    } elseif (mb_strlen($values['city']) > 100) {
        $errors[] = 'Your city name is too long.';
    }

    if ($values['years_experience'] === '') {
        if (ep_application_field_required('years_experience')) {
            $errors[] = 'Please enter your years of experience.';
        }
    } elseif (mb_strlen($values['years_experience']) > 20) {
        $errors[] = 'The value entered for years of experience is too long.';
    }

    if ($values['relevant_experience'] === '') {
        if (ep_application_field_required('relevant_experience')) {
            $errors[] = 'Please describe your relevant experience.';
        }
    } elseif (mb_strlen($values['relevant_experience']) > 5000) {
        $errors[] = 'Your relevant experience summary is too long (5,000 characters maximum).';
    }

    $expectedSalary = null;
    if ($values['expected_salary'] === '') {
        if (ep_application_field_required('expected_salary')) {
            $errors[] = 'Please enter your expected salary.';
        }
    } else {
        $digits = (string) preg_replace('/\D+/', '', $values['expected_salary']);
        if ($digits === '' || (int) $digits < 1) {
            $errors[] = 'Please enter your expected salary as a number.';
        } elseif ((int) $digits > 100000000) {
            $errors[] = 'Please enter a realistic expected salary.';
        } else {
            $expectedSalary = (int) $digits;
        }
    }

    // Joining time is a fixed dropdown — anything else is rejected.
    if ($values['joining_time'] === '') {
        if (ep_application_field_required('joining_time')) {
            $errors[] = 'Please choose how soon you can join.';
        }
    } elseif (!in_array($values['joining_time'], ep_careers_joining_times(), true)) {
        $errors[] = 'Please choose a valid option for how soon you can join.';
        $values['joining_time'] = '';
    }

    if (ep_application_field_required('consent') && $values['consent'] !== '1') {
        $errors[] = 'Please agree to EduPortal storing your information for recruitment purposes.';
    }

    // Custom fields, in definition order — same "supplied is always checked,
    // only the demand is optional" rule as every field above.
    $customClean = [];
    foreach ($customDefs as $key => $def) {
        $val = $values['custom'][$key];
        if ($val === '') {
            if (ep_application_field_required($key)) {
                $errors[] = 'Please fill in "' . $def['label'] . '".';
            }
            continue;
        }
        switch ($def['type']) {
            case 'email':
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please enter a valid value for "' . $def['label'] . '".';
                    continue 2;
                }
                break;
            case 'url':
                if (!filter_var($val, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $val)) {
                    $errors[] = 'Please enter a valid link for "' . $def['label'] . '" (starting with http:// or https://).';
                    continue 2;
                }
                break;
            case 'number':
                if (!preg_match('/^-?\d+(\.\d+)?$/', $val)) {
                    $errors[] = '"' . $def['label'] . '" must be a number.';
                    continue 2;
                }
                break;
            case 'select':
                if (!in_array($val, $def['options'], true)) {
                    $errors[] = 'Please choose a valid option for "' . $def['label'] . '".';
                    continue 2;
                }
                break;
            case 'textarea':
                if (mb_strlen($val) > 3000) {
                    $errors[] = '"' . $def['label'] . '" is too long (3,000 characters maximum).';
                    continue 2;
                }
                break;
            default: // text, tel, checkbox
                if (mb_strlen($val) > 190) {
                    $errors[] = '"' . $def['label'] . '" is too long.';
                    continue 2;
                }
        }
        $customClean[$key] = $val;
    }

    $jobId = $job ? (int) $job['id'] : null;

    if (!$errors) {
        $limitError = ep_careers_rate_limited(mb_strtolower($values['email']), $jobId);
        if ($limitError !== '') {
            $errors[] = $limitError;
        }
    }

    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'values' => $values];
    }

    // --- Files last, once everything else is known good.
    //
    // A file that was actually sent is always validated for real content
    // (a genuine PDF, a genuine image) whatever the config says — "optional"
    // means the applicant may skip it, never that a bad file is let through.
    $resumeSent = !empty($files['resume'])
        && (int) ($files['resume']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $photoSent = !empty($files['photo'])
        && (int) ($files['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $resume = null;
    if ($resumeSent) {
        $resume = ep_careers_store_resume($files['resume']);
        if (!$resume['ok']) {
            return ['ok' => false, 'errors' => [$resume['error']], 'values' => $values];
        }
    } elseif (ep_application_field_required('resume')) {
        return ['ok' => false, 'errors' => ['Please attach your résumé (PDF, DOC or DOCX).'], 'values' => $values];
    }

    $photo = null;
    if ($photoSent) {
        $photo = ep_careers_store_photo($files['photo']);
        if (!$photo['ok']) {
            ep_careers_delete_file((string) ($resume['path'] ?? ''));
            return ['ok' => false, 'errors' => [$photo['error']], 'values' => $values];
        }
    } elseif (ep_application_field_required('photo')) {
        ep_careers_delete_file((string) ($resume['path'] ?? ''));
        return ['ok' => false, 'errors' => ['Please attach a profile photo (JPG or PNG).'], 'values' => $values];
    }

    $data = [
        'job_id' => $jobId,
        'job_title_snapshot' => $job ? (string) $job['title'] : 'General Application',
        'full_name' => $values['full_name'],
        'email' => mb_strtolower($values['email']),
        'whatsapp' => $whatsappDigits,
        'city' => $values['city'],
        'photo' => (string) ($photo['path'] ?? ''),
        'resume' => (string) ($resume['path'] ?? ''),
        'years_experience' => $values['years_experience'],
        'relevant_experience' => $values['relevant_experience'],
        'expected_salary' => $expectedSalary,
        'joining_time' => $values['joining_time'],
        'linkedin' => $values['linkedin'],
        'extra_fields' => $customClean ? json_encode($customClean, JSON_UNESCAPED_UNICODE) : null,
        'status' => 'New',
        // When they ticked the consent box — a consent record with no
        // timestamp is not worth much if it is ever questioned.
        'consent_at' => date('Y-m-d H:i:s'),
        'resume_original_name' => (string) ($resume['name'] ?? ''),
        'resume_mime' => (string) ($resume['mime'] ?? ''),
        'resume_size' => (int) ($resume['size'] ?? 0),
        'source_page' => mb_substr((string) ($post['source_page'] ?? ''), 0, 190),
        'ip_address' => ep_careers_client_ip(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ];

    $id = $m->insert('ep_job_applications', $data);
    if (!$id) {
        if ($resume) {
            ep_careers_delete_file((string) ($resume['path'] ?? ''));
        }
        ep_careers_delete_file((string) ($photo['path'] ?? ''));
        return ['ok' => false, 'errors' => ['We could not save your application. Please try again.'], 'values' => $values];
    }

    // Invalidate the challenge and the token so neither can be replayed.
    unset($_SESSION['ep_careers_csrf']);

    ep_careers_notify_hr((int) $id, $data, $job);
    ep_careers_confirm_to_applicant($data, $job);

    return ['ok' => true, 'errors' => [], 'values' => [], 'id' => (int) $id];
}

/**
 * Confirms receipt to the applicant, using the same MysqliDb::htmlmail helper
 * as the HR notification. Silent on failure — a mail problem must never make
 * an already-saved application look like it failed.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed>|null $job
 */
function ep_careers_confirm_to_applicant(array $data, ?array $job): void
{
    global $m;

    $to = trim((string) ($data['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $siteName = ep_setting('site_name', 'EduPortal');
    $role = (string) $data['job_title_snapshot'];
    $isGeneral = $job === null;
    $firstName = trim((string) $data['full_name']);
    $firstName = $firstName !== '' ? explode(' ', $firstName)[0] : 'there';

    $intro = $isGeneral
        ? 'Thank you for sending us your CV. We have received it and it is now on file with our HR team.'
        : 'Thank you for applying for the <strong>' . ep_h($role) . '</strong> role. We have received your application.';

    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1a1a1a">'
        . '<h2 style="margin:0 0 12px">We have received your application</h2>'
        . '<p>Hello ' . ep_h($firstName) . ',</p>'
        . '<p>' . $intro . '</p>'
        . '<p>Our HR team reviews every application. If your profile matches what we are looking for, '
        . 'we will contact you on the email address or WhatsApp number you provided.</p>'
        . '<p style="color:#666;font-size:13px">Please do not reply to this message — it was sent automatically.</p>'
        . '<p style="margin-top:20px">— ' . ep_h($siteName) . ' HR</p>'
        . '</div>';

    // Reply-To points at whichever careers address is configured, so a reply
    // still reaches a person even though this is an automated send.
    $headers = '';
    foreach ([
        (string) ($job['apply_email'] ?? ''),
        (string) ep_setting('careers_notify_email', ''),
        (string) ep_setting('support_email', ''),
    ] as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            $headers = 'From: ' . $candidate . "\r\nReply-To: " . $candidate;
            break;
        }
    }

    try {
        $m->htmlmail($to, $siteName . ' — we have received your application', $html, $headers);
    } catch (Throwable $e) {
        error_log('[EduPortal] Careers applicant confirmation email failed: ' . $e->getMessage());
    }
}

/**
 * Notifies HR of a new application using the project's existing mail helper
 * (MysqliDb::htmlmail). Silent on failure — a mail problem must never lose
 * an application that has already been saved.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed>|null $job
 */
function ep_careers_notify_hr(int $applicationId, array $data, ?array $job): void
{
    global $m;

    $to = '';
    foreach ([
        (string) ($job['apply_email'] ?? ''),
        (string) ep_setting('careers_notify_email', ''),
        (string) ep_setting('support_email', ''),
    ] as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            $to = $candidate;
            break;
        }
    }
    if ($to === '') {
        return;
    }

    $role = (string) $data['job_title_snapshot'];
    $rows = [
        'Role' => $role,
        'Name' => $data['full_name'],
        'Email' => $data['email'],
        'WhatsApp' => $data['whatsapp'],
        'City' => $data['city'],
        'Experience' => $data['years_experience'],
        'Expected salary' => $data['expected_salary'] !== null ? number_format((int) $data['expected_salary']) : '',
        'Can join' => $data['joining_time'],
    ];

    $html = '<h2 style="font-family:Arial,sans-serif">New job application</h2>'
        . '<table cellpadding="6" style="font-family:Arial,sans-serif;font-size:14px">';
    foreach ($rows as $label => $value) {
        if (trim((string) $value) === '') {
            continue;
        }
        $html .= '<tr><td style="color:#666">' . ep_h($label) . '</td><td><strong>' . ep_h((string) $value) . '</strong></td></tr>';
    }
    $html .= '</table><p style="font-family:Arial,sans-serif;font-size:14px">Résumé and photo are available in the admin panel: '
        . '<a href="' . ep_h(rtrim((string) (defined('ADMIN') ? ADMIN : ''), '/') . '/job-applications.php?view=' . $applicationId) . '">Application #' . $applicationId . '</a></p>';

    try {
        $m->htmlmail($to, 'New application: ' . $role . ' — ' . $data['full_name'], $html, 'From: ' . $to);
    } catch (Throwable $e) {
        error_log('[EduPortal] Careers notification email failed: ' . $e->getMessage());
    }
}