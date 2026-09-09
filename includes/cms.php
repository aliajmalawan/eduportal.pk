<?php
/**
 * EduPortal website CMS helpers — read content from MySQL.
 * Requires includes/callMe.php ($m = MysqliDb instance).
 */

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!isset($m) || !($m instanceof MysqliDb)) {
    require_once __DIR__ . '/callMe.php';
}

/** @var array<string, string|null> */
$GLOBALS['_ep_settings_cache'] = null;

function ep_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Returns ready-to-print ` width="…" height="…"` attributes for a local
 * image, or '' if the file cannot be measured.
 *
 * Intrinsic dimensions let the browser reserve the right space before the
 * image downloads, which is what stops the page jumping as it loads (the
 * Cumulative Layout Shift metric). CSS still controls the displayed size.
 *
 * Dimensions are read once per file per request and memoised — blog and
 * case-study listings render the same thumbnails repeatedly, and getimagesize()
 * opens the file each time otherwise.
 */
function ep_img_dimension_attrs(?string $relPath): string
{
    static $cache = [];
    $rel = trim((string) $relPath);
    if ($rel === '' || preg_match('#^https?://#i', $rel)) {
        return '';
    }
    if (array_key_exists($rel, $cache)) {
        return $cache[$rel];
    }
    $full = dirname(__DIR__) . '/' . ltrim($rel, '/');
    $attrs = '';
    if (is_file($full)) {
        $size = @getimagesize($full);
        if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
            $attrs = ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"';
        }
    }
    return $cache[$rel] = $attrs;
}

function ep_asset(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return $path;
}

function ep_url(string $path = ''): string
{
    $base = defined('DOMAIN') ? rtrim(DOMAIN, '/') . '/' : '/';
    return $base . ltrim($path, '/');
}

/**
 * Canonical URL for the current request: production base URL + current page path.
 * Always uses the production domain (never the local/dev DOMAIN value) since a
 * canonical tag must reference the real, indexable URL regardless of environment.
 */
function ep_canonical_url(): string
{
    $base = 'https://eduportal.pk/';

    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    $siteRoot = str_replace('\\', '/', rtrim(dirname(__DIR__), '/\\'));
    $basePath = ($docRoot !== '' && str_starts_with($siteRoot, $docRoot))
        ? substr($siteRoot, strlen($docRoot))
        : '';
    if ($basePath !== '' && str_starts_with($scriptPath, $basePath)) {
        $scriptPath = substr($scriptPath, strlen($basePath));
    }
    $path = ltrim($scriptPath, '/');

    if ($path === '' || $path === 'index.php') {
        return $base;
    }

    $query = isset($_GET['slug']) ? '?slug=' . urlencode((string) $_GET['slug']) : '';

    return $base . $path . $query;
}

/** Cache-busted asset URL relative to site root (e.g. js/foo.js). */
function ep_versioned_asset(string $relativeFromSiteRoot): string
{
    $relativeFromSiteRoot = ltrim($relativeFromSiteRoot, '/');
    $abs = dirname(__DIR__) . '/' . $relativeFromSiteRoot;
    $v = is_file($abs) ? (string) filemtime($abs) : (string) time();
    return $relativeFromSiteRoot . '?v=' . $v;
}

function ep_normalize_public_path(string $path, string $defaultDir): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if (!str_contains($path, '/')) {
        $path = rtrim($defaultDir, '/') . '/' . $path;
    }
    return $path;
}

function ep_format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function ep_settings(): array
{
    if ($GLOBALS['_ep_settings_cache'] !== null) {
        return $GLOBALS['_ep_settings_cache'];
    }
    global $m;
    $rows = $m->get('ep_site_settings', null, 'setting_key, setting_value');
    $out = [];
    foreach ($rows as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    $GLOBALS['_ep_settings_cache'] = $out;
    return $out;
}

function ep_setting(string $key, ?string $default = null): ?string
{
    $all = ep_settings();
    return $all[$key] ?? $default;
}

/**
 * Client-metric settings (total_clients, total_students, ...), with their
 * known fallback baked in once here instead of repeated at every call site.
 * Reuses ep_setting()/ep_settings()'s existing request-level cache — no
 * extra database query per call.
 */
function ep_site_metric(string $key): string
{
    static $defaults = [
        'total_clients' => '500+',
        'total_students' => '300,000+',
        'total_cities' => '',
        'metrics_updated_date' => '',
    ];
    return (string) ep_setting($key, $defaults[$key] ?? '');
}

/* ---------------------------------------------------------------------
 * Company facts (Task 8)
 *
 * The About page states these as plain labelled text rather than as graphics
 * or marketing prose, because that is the form a search engine or an AI
 * assistant can actually quote back. Every value is admin-editable, and a
 * fact that has not been filled in is omitted rather than guessed at —
 * an invented registration number or team size is worse than a missing one.
 * ------------------------------------------------------------------ */

/**
 * The company facts to publish, in display order, skipping any that are unset.
 *
 * @return array<int, array{label: string, value: string}>
 */
function ep_company_facts(): array
{
    $city = trim((string) ep_setting('office_city', ''));
    $region = trim((string) ep_setting('office_region', ''));
    $headOffice = $city;
    if ($city !== '' && $region !== '') {
        $headOffice = $city . ', ' . $region;
    }

    $candidates = [
        ['label' => 'Founded',            'value' => trim((string) ep_setting('founding_year', ''))],
        ['label' => 'Founder',            'value' => trim((string) ep_setting('founder_name', ''))],
        ['label' => 'Head office',        'value' => $headOffice],
        ['label' => 'Team size',          'value' => trim((string) ep_setting('team_size', ''))],
        ['label' => 'Schools served',     'value' => trim((string) ep_site_metric('total_clients'))],
        ['label' => 'Students managed',   'value' => trim((string) ep_site_metric('total_students'))],
        ['label' => 'Cities covered',     'value' => trim((string) ep_site_metric('total_cities'))],
        ['label' => 'PSEB registration',  'value' => trim((string) ep_setting('reg_pseb', ''))],
        ['label' => 'SECP registration',  'value' => trim((string) ep_setting('reg_secp', ''))],
        ['label' => 'Chamber of Commerce','value' => trim((string) ep_setting('reg_chamber', ''))],
    ];

    return array_values(array_filter(
        $candidates,
        static fn(array $f): bool => $f['value'] !== ''
    ));
}

function ep_get_blogs(int $limit = 50, ?int $excludeId = null): array
{
    global $m;
    $m->where('status', 'published');
    $m->orderBy('published_at', 'DESC');
    if ($excludeId) {
        $m->where('id', $excludeId, '!=');
    }
    return $m->get('ep_blogs', $limit) ?: [];
}

function ep_get_blog_by_slug(string $slug): ?array
{
    global $m;
    $m->where('slug', $slug);
    $m->where('status', 'published');
    $row = $m->getOne('ep_blogs');
    return $row ?: null;
}

function ep_blog_post_url(array $blog): string
{
    return ep_url('blog-post.php?slug=' . urlencode($blog['slug']));
}

function ep_blog_thumb_html(array $blog, string $postUrl, string $alt = ''): string
{
    $alt = ep_h($alt ?: $blog['title']);
    $type = $blog['thumb_type'] ?? 'image';
    $gradient = $blog['thumb_gradient_class'] ?? '';
    $icon = $blog['thumb_icon'] ?? 'sparkles';
    $href = ep_h($postUrl);

    if ($type === 'image' && !empty($blog['featured_image'])) {
        return '<a href="' . $href . '" class="blog-card-thumb" aria-hidden="true">'
            . '<img src="' . ep_h(ep_asset($blog['featured_image'])) . '" alt="' . $alt . '"'
            . ep_img_dimension_attrs($blog['featured_image'])
            . ' loading="lazy" decoding="async"></a>';
    }

    $classes = 'blog-card-thumb blog-card-thumb--gradient';
    if ($gradient) {
        $classes .= ' ' . ep_h($gradient);
    }
    return '<a href="' . $href . '" class="' . $classes . '" aria-hidden="true">'
        . '<i data-lucide="' . ep_h($icon) . '"></i></a>';
}

function ep_get_case_studies(): array
{
    global $m;
    $m->where('status', 'published');
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('published_at', 'DESC');
    return $m->get('ep_case_studies') ?: [];
}

function ep_get_case_study_by_slug(string $slug): ?array
{
    global $m;
    $m->where('slug', $slug);
    $m->where('status', 'published');
    $study = $m->getOne('ep_case_studies');
    if (!$study) {
        return null;
    }

    $id = (int) $study['id'];
    $m->where('case_study_id', $id);
    $m->orderBy('sort_order', 'ASC');
    $study['metrics'] = $m->get('ep_case_study_metrics') ?: [];

    $m->where('case_study_id', $id);
    $m->orderBy('sort_order', 'ASC');
    $study['modules'] = $m->get('ep_case_study_modules', null, 'feature_slug') ?: [];

    return $study;
}

/**
 * Short plain-text excerpt for a case study, used on the listing cards and as
 * the meta description on the detail page.
 *
 * Prefers the hand-written summary and falls back to the body copy, so a study
 * that has not had a summary written yet still shows something sensible rather
 * than an empty card. Tags are stripped and entities decoded first, because the
 * result goes into a meta description and card text, never into markup.
 */
function ep_case_study_excerpt(array $study, int $length = 160): string
{
    $text = trim((string) ($study['summary'] ?? ''));
    if ($text === '') {
        $text = trim((string) ($study['description'] ?? ''));
    }
    if ($text === '') {
        return '';
    }

    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/', ' ', $text));

    if ($length < 1 || mb_strlen($text) <= $length) {
        return $text;
    }

    // Cut on a word boundary so the excerpt does not end mid-word.
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $length * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }

    return rtrim($cut, " ,.;:-") . '…';
}

function ep_case_study_url(array $study): string
{
    return ep_url('case-studies/' . rawurlencode((string) $study['slug']));
}

/** Absolute canonical URL for a case study. */
function ep_case_study_canonical(array $study): string
{
    return 'https://eduportal.pk/case-studies/' . rawurlencode((string) $study['slug']);
}

function ep_case_study_thumbnail_url(array $study): string
{
    // Returns '' when the file is missing so templates fall through to their
    // placeholder branch. Some rows point at thumbnails that are no longer on
    // disk; emitting the path anyway produced an <img> that 404s on page load
    // — a broken image plus a console error on every visit.
    $rel = trim((string) ($study['thumbnail_path'] ?? ''));
    if ($rel === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $rel) && !is_file(dirname(__DIR__) . '/' . ltrim($rel, '/'))) {
        return '';
    }
    return ep_asset($rel);
}

function ep_sync_video_upload_types(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    global $m;
    try {
        $m->mysqli()->query(
            "UPDATE ep_video_testimonials SET video_type = 'upload'
             WHERE video_file_path IS NOT NULL AND TRIM(video_file_path) != ''
               AND (video_type IS NULL OR TRIM(video_type) = '' OR LOWER(video_type) != 'upload')"
        );
    } catch (Throwable $e) {
        // ignore if columns not migrated yet
    }
}

/**
 * Orders rows by an admin-set "sort_order" position number: 1, 2, 3… show
 * in exactly that order, first. A row left at the default 0 is treated as
 * "not manually positioned" and falls in afterward, newest-added first —
 * so nobody has to number every single row just to promote a few to the top.
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function ep_sort_by_display_order(array $rows): array
{
    usort($rows, static function (array $a, array $b): int {
        $orderA = (int) ($a['sort_order'] ?? 0);
        $orderB = (int) ($b['sort_order'] ?? 0);
        if ($orderA !== 0 && $orderB !== 0) {
            return $orderA <=> $orderB;
        }
        if ($orderA !== 0) {
            return -1;
        }
        if ($orderB !== 0) {
            return 1;
        }
        return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
    });
    return $rows;
}

function ep_get_video_testimonials(bool $homepageOnly = false): array
{
    ep_sync_video_upload_types();
    global $m;
    $m->where('status', 'published');
    if ($homepageOnly) {
        $m->where('show_on_homepage', 1);
        $m->orderBy('homepage_sort_order', 'ASC');
        $rows = $m->get('ep_video_testimonials') ?: [];
    } else {
        $rows = $m->get('ep_video_testimonials') ?: [];
        $rows = ep_sort_by_display_order($rows);
    }

    // Drop testimonials with nothing playable behind them. Several published
    // rows point at uploaded video files that are no longer on disk; their
    // cards rendered normally but 404'd the moment anyone pressed play. A
    // missing YouTube id or a missing file means there is no video, so the
    // card is not shown at all rather than shown broken. Restoring the files
    // brings them straight back — nothing here is deleted.
    return array_values(array_filter($rows, static function (array $v): bool {
        if (trim((string) ($v['youtube_video_id'] ?? '')) !== '') {
            return true;
        }
        $rel = trim((string) ($v['video_file_path'] ?? ''));
        return $rel !== '' && is_file(dirname(__DIR__) . '/' . ltrim($rel, '/'));
    }));
}

function ep_video_role_label(string $roleFilter): string
{
    $map = [
        'principal' => 'Principal',
        'owner' => 'School Owner',
        'director' => 'Director',
        'admin' => 'Administrator',
        'other' => 'School Leader',
    ];
    $key = strtolower(trim($roleFilter));
    return $map[$key] ?? ucfirst($key);
}

function ep_video_designation(array $video): string
{
    $designation = trim((string) ($video['designation'] ?? ''));
    if ($designation !== '') {
        return $designation;
    }
    $legacy = trim((string) ($video['person_title'] ?? ''));
    if ($legacy !== '') {
        return $legacy;
    }
    return ep_video_role_label((string) ($video['role_filter'] ?? ''));
}

function ep_video_subtitle(array $video): string
{
    $designation = ep_video_designation($video);
    $school = trim((string) ($video['school_name'] ?? ''));
    if ($designation !== '' && $school !== '') {
        return $designation . ', ' . $school;
    }
    return $designation !== '' ? $designation : $school;
}

function ep_video_type(array $video): string
{
    if (trim((string) ($video['video_file_path'] ?? '')) !== '') {
        return 'upload';
    }
    $type = strtolower(trim((string) ($video['video_type'] ?? 'youtube')));
    return $type === 'upload' ? 'upload' : 'youtube';
}

/**
 * Absolute URL for a site asset that may be written as a relative path.
 *
 * Pages served from /features/{slug} are one path segment deep, so a relative
 * "css/shared.css" would resolve to /features/css/shared.css and 404. Passing
 * asset paths through here keeps them correct from any URL depth, while
 * leaving absolute and protocol-relative URLs untouched.
 */
function ep_asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, '/') || str_starts_with($path, 'data:')) {
        return $path;
    }

    $url = ep_url($path);

    // Cache-busting fingerprint. .htaccess serves CSS/JS with
    // "Cache-Control: public, max-age=2592000" -- thirty days -- so without a
    // version in the URL a returning visitor keeps the old stylesheet for a
    // month after a deploy, and never sees a fix. Appending the file's mtime
    // changes the URL whenever the file changes, which lets the long cache
    // stay aggressive while updates still land immediately.
    //
    // The mtime read is the one that is actually served: .htaccess
    // content-negotiates to the .min variant when one exists, so a rebuilt
    // .min.css must be what moves the version.
    static $stamps = [];
    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'css' || $ext === 'js') {
        if (!array_key_exists($path, $stamps)) {
            $root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
            $plain = $root . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
            $min = preg_replace('/\.(css|js)$/i', '.min.$1', $plain);
            $file = ($min !== null && is_file($min)) ? $min : $plain;
            $stamps[$path] = is_file($file) ? (string) filemtime($file) : '';
        }
        if ($stamps[$path] !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $stamps[$path];
        }
    }

    return $url;
}

/* ---------------------------------------------------------------------
 * Documentation — /docs and /docs/{slug}  (Task 8)
 *
 * One step-by-step help article per feature module. An article only goes
 * public once it has real steps in it: publishing a guide whose screens do
 * not match the product generates support calls instead of preventing them.
 * ------------------------------------------------------------------ */

function ep_ensure_docs_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $m;
    $db = $m->mysqli();

    $db->query("CREATE TABLE IF NOT EXISTS ep_docs (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      slug VARCHAR(160) NOT NULL,
      title VARCHAR(190) NOT NULL,
      feature_script VARCHAR(120) NOT NULL DEFAULT '',
      category VARCHAR(80) NOT NULL DEFAULT '',
      summary VARCHAR(400) NOT NULL DEFAULT '',
      intro_html TEXT NULL,
      steps_json TEXT NULL,
      tips_html TEXT NULL,
      faqs_json TEXT NULL,
      meta_title VARCHAR(190) NOT NULL DEFAULT '',
      meta_description VARCHAR(320) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'Draft',
      sort_order INT NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY slug_uq (slug),
      KEY status_idx (status, category, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ep_docs_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    global $m;
    try {
        $res = $m->mysqli()->query("SHOW TABLES LIKE 'ep_docs'");
        $ready = $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/**
 * Decodes a JSON column into a list of arrays, tolerating an empty or
 * malformed value rather than fataling on a hand-edited row.
 *
 * @return array<int, array<string, string>>
 */
function ep_docs_decode(?string $json): array
{
    $json = trim((string) $json);
    if ($json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach ($decoded as $item) {
        if (is_array($item)) {
            $out[] = array_map(static fn($v): string => is_scalar($v) ? (string) $v : '', $item);
        }
    }
    return $out;
}

/** @return array<int, array<string, mixed>> */
function ep_get_docs(): array
{
    if (!ep_docs_tables_ready()) {
        return [];
    }
    global $m;
    $m->where('status', 'Active');
    $m->orderBy('category', 'ASC');
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('title', 'ASC');
    return $m->get('ep_docs') ?: [];
}

function ep_get_doc_by_slug(string $slug): ?array
{
    if (!ep_docs_tables_ready() || $slug === '') {
        return null;
    }
    global $m;
    $m->where('slug', $slug);
    $m->where('status', 'Active');
    $doc = $m->getOne('ep_docs');
    if (!$doc) {
        return null;
    }
    $doc['steps'] = ep_docs_decode($doc['steps_json'] ?? '');
    $doc['faqs'] = ep_docs_decode($doc['faqs_json'] ?? '');
    return $doc;
}

function ep_doc_url(array $doc): string
{
    return ep_url('docs/' . rawurlencode((string) $doc['slug']));
}

function ep_doc_canonical(array $doc): string
{
    return 'https://eduportal.pk/docs/' . rawurlencode((string) $doc['slug']);
}

/**
 * HowTo structured data for a guide, so the steps can be surfaced directly in
 * search results. Only emitted when there are at least two real steps —
 * a one-step HowTo is not a procedure and Google rejects it.
 *
 * @param array<int, array<string, string>> $steps
 * @return array<string, mixed>
 */
function ep_doc_howto_schema(array $doc, array $steps): array
{
    if (count($steps) < 2) {
        return [];
    }

    $items = [];
    $position = 1;
    foreach ($steps as $step) {
        $name = trim((string) ($step['title'] ?? ''));
        if ($name === '') {
            continue;
        }
        $item = [
            '@type' => 'HowToStep',
            'position' => $position++,
            'name' => $name,
        ];
        $body = trim(strip_tags((string) ($step['body'] ?? '')));
        if ($body !== '') {
            $item['text'] = $body;
        }
        $items[] = $item;
    }

    if (count($items) < 2) {
        return [];
    }

    return [
        '@type' => 'HowTo',
        '@id' => ep_doc_canonical($doc) . '#howto',
        'name' => (string) $doc['title'],
        'description' => (string) $doc['summary'],
        'step' => $items,
    ];
}

/* ---------------------------------------------------------------------
 * Comparison pages — /compare/eduportal-vs-{competitor}  (Task 8)
 *
 * A comparison page states facts about another company, so the model treats
 * those facts as sourced and dated rather than as free copy. A comparison
 * only goes public when it has been verified AND it concedes at least one
 * point to the competitor — a table where one side wins every row reads as
 * advertising and gets discounted by readers and search engines alike.
 * ------------------------------------------------------------------ */

function ep_ensure_comparison_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $m;
    $db = $m->mysqli();

    $db->query("CREATE TABLE IF NOT EXISTS ep_comparisons (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      slug VARCHAR(160) NOT NULL,
      competitor_name VARCHAR(120) NOT NULL,
      competitor_summary TEXT NULL,
      intro_html TEXT NULL,
      verdict_html TEXT NULL,
      where_they_win_html TEXT NULL,
      source_url VARCHAR(500) NOT NULL DEFAULT '',
      verified_at DATE NULL DEFAULT NULL,
      meta_title VARCHAR(190) NOT NULL DEFAULT '',
      meta_description VARCHAR(320) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'Draft',
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY slug_uq (slug),
      KEY status_idx (status, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS ep_comparison_rows (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      comparison_id INT UNSIGNED NOT NULL,
      section VARCHAR(80) NOT NULL DEFAULT '',
      feature_label VARCHAR(190) NOT NULL,
      eduportal_value TEXT NULL,
      competitor_value TEXT NULL,
      advantage VARCHAR(20) NOT NULL DEFAULT 'tie',
      note VARCHAR(400) NOT NULL DEFAULT '',
      sort_order INT NOT NULL DEFAULT 0,
      PRIMARY KEY (id),
      KEY comparison_idx (comparison_id, sort_order),
      CONSTRAINT fk_ep_comparison_rows FOREIGN KEY (comparison_id) REFERENCES ep_comparisons(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach ([
        ['eduportal-vs-edusuite',  'EduSuite',  1],
        ['eduportal-vs-capobrain', 'Capobrain', 2],
        ['eduportal-vs-skoolzoom', 'SkoolZoom', 3],
        ['eduportal-vs-glowsims',  'Glowsims',  4],
        ['eduportal-vs-prodesk',   'ProDesk',   5],
    ] as [$slug, $name, $order]) {
        $stmt = $db->prepare(
            "INSERT INTO ep_comparisons (slug, competitor_name, status, sort_order)
             VALUES (?, ?, 'Draft', ?) ON DUPLICATE KEY UPDATE slug = slug"
        );
        if ($stmt) {
            $stmt->bind_param('ssi', $slug, $name, $order);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/** True when the comparison tables exist, so public pages never fatal. */
function ep_comparison_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    global $m;
    try {
        $res = $m->mysqli()->query("SHOW TABLES LIKE 'ep_comparisons'");
        $ready = $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** @return array<int, string> */
function ep_comparison_advantages(): array
{
    return ['eduportal', 'competitor', 'tie'];
}

/**
 * A comparison is publishable only if it concedes at least one row to the
 * competitor. This is enforced rather than merely advised: the whole value of
 * these pages is that a reader can tell they were not written by marketing.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function ep_comparison_is_balanced(array $rows): bool
{
    foreach ($rows as $row) {
        if (($row['advantage'] ?? '') === 'competitor') {
            return true;
        }
    }
    return false;
}

/** Comparisons that are live, in display order. @return array<int, array<string, mixed>> */
function ep_get_comparisons(): array
{
    if (!ep_comparison_tables_ready()) {
        return [];
    }
    global $m;
    $m->where('status', 'Active');
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('competitor_name', 'ASC');
    return $m->get('ep_comparisons') ?: [];
}

/** One live comparison with its table rows, or null. */
function ep_get_comparison_by_slug(string $slug): ?array
{
    if (!ep_comparison_tables_ready() || $slug === '') {
        return null;
    }
    global $m;
    $m->where('slug', $slug);
    $m->where('status', 'Active');
    $cmp = $m->getOne('ep_comparisons');
    if (!$cmp) {
        return null;
    }
    $m->where('comparison_id', (int) $cmp['id']);
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('id', 'ASC');
    $cmp['rows'] = $m->get('ep_comparison_rows') ?: [];
    return $cmp;
}

function ep_comparison_url(array $cmp): string
{
    return ep_url('compare/' . rawurlencode((string) $cmp['slug']));
}

function ep_comparison_canonical(array $cmp): string
{
    return 'https://eduportal.pk/compare/' . rawurlencode((string) $cmp['slug']);
}

/* ---------------------------------------------------------------------
 * Feature module URLs (Task 8)
 *
 * The module pages moved from /whatever-management.php to /features/{slug}.
 * This map is the single source of truth: .htaccess routing, canonical tags,
 * the sitemap, and internal links are all derived from it, so a slug can
 * never be right in one place and wrong in another.
 * ------------------------------------------------------------------ */

/**
 * script filename => /features/ slug.
 *
 * @return array<string, string>
 */
function ep_feature_pages(): array
{
    return [
        // The seven named in the Task 8 brief.
        'attendance-management.php' => 'attendance',
        'fee-management.php' => 'fee-management',
        'examination-management.php' => 'examinations',
        'sms-messaging.php' => 'sms-alerts',
        'whatsapp-communication.php' => 'whatsapp',
        'parent-mobile-app.php' => 'parent-app',
        'teacher-mobile-app.php' => 'teacher-app',

        // The rest of the module pages, same scheme.
        'accounting-management.php' => 'accounting',
        'canteen-pos.php' => 'canteen-pos',
        'certificates-id-cards.php' => 'certificates',
        'customer-support.php' => 'customer-support',
        'customization-options.php' => 'customization',
        'exam-datasheets.php' => 'exam-datasheets',
        'expenses-profit-loss.php' => 'expenses',
        'hostel-management.php' => 'hostel',
        'id-cards-generation.php' => 'id-cards',
        'inventory-management.php' => 'inventory',
        'lesson-plans-syllabus.php' => 'lesson-plans',
        'library-management.php' => 'library',
        'lms-management.php' => 'lms',
        'multi-campus-management.php' => 'multi-campus',
        'online-admissions.php' => 'admissions',
        'payroll-management.php' => 'payroll',
        'student-information-system.php' => 'student-information',
        'timetable-management.php' => 'timetable',
        'transport-management.php' => 'transport',
    ];
}

/** The /features/ slug for a module script, or '' if it is not a module page. */
function ep_feature_slug(string $script): string
{
    return ep_feature_pages()[basename($script)] ?? '';
}

/**
 * Site-relative URL for a module page, e.g. ep_feature_url('fee-management.php')
 * -> /eduportal/features/fee-management. Falls back to the plain script URL for
 * anything not in the map, so a mistyped filename degrades instead of 404ing.
 */
function ep_feature_url(string $script): string
{
    $slug = ep_feature_slug($script);
    return $slug !== '' ? ep_url('features/' . $slug) : ep_url(basename($script));
}

/** Absolute canonical URL for a module page. */
function ep_feature_canonical(string $script): string
{
    $slug = ep_feature_slug($script);
    return $slug !== ''
        ? 'https://eduportal.pk/features/' . $slug
        : 'https://eduportal.pk/' . basename($script);
}

/* ---------------------------------------------------------------------
 * Module page FAQs (Task 8)
 *
 * Each feature/module page defines its FAQs once as an array. That single
 * array drives both the visible accordion and the FAQPage structured data,
 * so the two can never drift apart — which matters, because Google drops
 * FAQ rich results when the markup does not match the visible text.
 * ------------------------------------------------------------------ */

/**
 * Expands {placeholder} tokens in FAQ text to live site metrics.
 *
 * The FAQ copy quotes figures like the client count, which are admin-editable
 * settings rather than fixed text. Storing a token keeps the array plain data
 * (so it can also go into JSON-LD) while still rendering the current number.
 * Returns raw text — callers escape it themselves.
 */
function ep_faq_expand_tokens(string $text): string
{
    if (strpos($text, '{') === false) {
        return $text;
    }
    return (string) preg_replace_callback(
        '/\{(total_clients|total_students|total_cities)\}/',
        static fn(array $m): string => (string) ep_site_metric($m[1]),
        $text
    );
}

/**
 * Renders the FAQ accordion used on every module page.
 *
 * @param array<int, array{q: string, a: string}> $faqs
 */
function ep_render_faq_accordion(array $faqs): void
{
    $index = 0;
    foreach ($faqs as $faq) {
        $question = ep_faq_expand_tokens(trim((string) ($faq['q'] ?? '')));
        $answer = ep_faq_expand_tokens(trim((string) ($faq['a'] ?? '')));
        if ($question === '' || $answer === '') {
            continue;
        }
        ?>

          <div class="fd-faq-item reveal-fd">
            <button type="button" class="fd-faq-q" aria-expanded="false" aria-controls="faq-a-<?= $index ?>">
              <span><?= ep_h($question) ?></span>
              <i data-lucide="chevron-down"></i>
            </button>
            <div class="fd-faq-a" id="faq-a-<?= $index ?>"><div class="fd-faq-a-inner"><?= ep_h($answer) ?></div></div>
          </div>
        <?php
        $index++;
    }
}

/**
 * FAQPage node for schema.org, built from the same array.
 *
 * Returns [] when there are no usable FAQs, so a page never emits an empty
 * FAQPage — Google treats that as invalid rather than ignoring it.
 *
 * @param array<int, array{q: string, a: string}> $faqs
 * @return array<string, mixed>
 */
function ep_faq_page_schema(array $faqs, string $pageUrl = ''): array
{
    $questions = [];
    foreach ($faqs as $faq) {
        $question = ep_faq_expand_tokens(trim((string) ($faq['q'] ?? '')));
        $answer = ep_faq_expand_tokens(trim((string) ($faq['a'] ?? '')));
        if ($question === '' || $answer === '') {
            continue;
        }
        $questions[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }
    if (!$questions) {
        return [];
    }

    $node = ['@type' => 'FAQPage'];
    if ($pageUrl !== '') {
        $node['@id'] = $pageUrl . '#faq';
    }
    $node['mainEntity'] = $questions;

    return $node;
}

/**
 * Appends the FAQPage node to a page's existing @graph JSON string.
 *
 * The module pages already emit a WebSite + BreadcrumbList + WebPage graph as
 * a heredoc; this adds the FAQPage to it rather than emitting a second,
 * competing <script> block.
 *
 * @param array<int, array{q: string, a: string}> $faqs
 */
function ep_append_faq_schema(string $graphJson, array $faqs, string $pageUrl = ''): string
{
    $node = ep_faq_page_schema($faqs, $pageUrl);
    if (!$node) {
        return $graphJson;
    }

    $decoded = json_decode($graphJson, true);
    if (!is_array($decoded)) {
        // Malformed graph: leave the original untouched rather than
        // replacing working markup with a guess.
        return $graphJson;
    }

    if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
        $decoded['@graph'][] = $node;
    } else {
        $decoded = [
            '@context' => $decoded['@context'] ?? 'https://schema.org',
            '@graph' => [$decoded, $node],
        ];
    }

    return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT);
}

/* ---------------------------------------------------------------------
 * Homepage demo video (Task 6)
 *
 * The video shown when the hero dashboard mockup is clicked. Admins paste a
 * normal YouTube link into Settings -> Home Page; the id is parsed out of it here
 * so nobody has to know what part of the URL is the video id.
 * ------------------------------------------------------------------ */

/**
 * Extracts the 11-character video id from any ordinary YouTube URL.
 *
 * Handles the three common formats —
 *   https://www.youtube.com/watch?v=ID
 *   https://youtu.be/ID
 *   https://www.youtube.com/embed/ID
 * — plus /shorts/, /live/, /v/, the mobile and music hosts, the no-cookie
 * domain, and v= appearing after other query parameters. A bare id is
 * accepted too, so pasting just the id still works.
 *
 * The host is checked before anything is extracted. Without that, a URL from
 * an unrelated site that happens to carry a ?v= parameter would yield an id
 * and get embedded as though it were a YouTube video.
 *
 * Returns '' when nothing usable is found — callers fall back rather than
 * embedding a broken player.
 */
function ep_youtube_id_from_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    // A bare id, e.g. "ar637Gcm3K0".
    if (preg_match('#^[A-Za-z0-9_-]{11}$#', $url)) {
        return $url;
    }

    // Accept a scheme-less paste like "youtu.be/ID" by giving parse_url a
    // scheme to work with; without one it reads the whole thing as a path.
    $forParsing = preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) ? $url : 'https://' . $url;
    $host = strtolower((string) parse_url($forParsing, PHP_URL_HOST));
    if ($host === '') {
        return '';
    }
    $host = preg_replace('#^www\.#', '', $host);

    static $allowedHosts = [
        'youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'youtu.be',
    ];
    if (!in_array($host, $allowedHosts, true)) {
        return '';
    }

    // youtu.be puts the id directly in the path; everything else uses either
    // the v= parameter or a known path prefix.
    if ($host === 'youtu.be') {
        $path = (string) parse_url($forParsing, PHP_URL_PATH);
        return preg_match('#^/([A-Za-z0-9_-]{11})#', $path, $match) ? $match[1] : '';
    }

    $query = (string) parse_url($forParsing, PHP_URL_QUERY);
    parse_str($query, $params);
    $v = (string) ($params['v'] ?? '');
    if (preg_match('#^[A-Za-z0-9_-]{11}$#', $v)) {
        return $v;
    }

    $path = (string) parse_url($forParsing, PHP_URL_PATH);
    if (preg_match('#^/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})#', $path, $match)) {
        return $match[1];
    }

    return '';
}

/**
 * Start offset in seconds from a YouTube URL's t= / start= parameter.
 * Accepts the "1m30s" form as well as plain seconds.
 */
function ep_youtube_start_from_url(string $url): int
{
    if (!preg_match('#[?&](?:t|start)=([0-9hms]+)#i', $url, $match)) {
        return 0;
    }
    $value = strtolower($match[1]);

    if (ctype_digit($value)) {
        return (int) $value;
    }
    $seconds = 0;
    if (preg_match('#(\d+)h#', $value, $h)) {
        $seconds += (int) $h[1] * 3600;
    }
    if (preg_match('#(\d+)m#', $value, $mn)) {
        $seconds += (int) $mn[1] * 60;
    }
    if (preg_match('#(\d+)s#', $value, $sec)) {
        $seconds += (int) $sec[1];
    }
    return $seconds;
}

/**
 * The homepage demo video, resolved from the admin settings.
 *
 * Always returns a playable id: if the setting is empty or the pasted link
 * cannot be parsed, it falls back to the original hard-coded video so the
 * hero play button never opens an empty player.
 *
 * @return array{url: string, title: string, youtube_id: string, start: int, is_default: bool}
 */
function ep_demo_video(): array
{
    $fallbackId = 'ar637Gcm3K0';

    $type = trim((string) ep_setting('demo_video_type', 'youtube')) === 'upload' ? 'upload' : 'youtube';
    $title = trim((string) ep_setting('demo_video_title', ''));
    if ($title === '') {
        $title = 'EduPortal product demo';
    }

    $fileUrl = '';
    if ($type === 'upload') {
        $filePath = ep_normalize_public_path(
            (string) ep_setting('demo_video_file_path', ''),
            'assets/video-testimonials'
        );
        // A file that's gone from disk (moved/deleted outside the admin
        // panel) falls back to the YouTube video below, same as the
        // testimonial cards do for a missing upload.
        if ($filePath !== '' && is_file(dirname(__DIR__) . '/' . ltrim($filePath, '/'))) {
            $fileUrl = ep_url($filePath);
        } else {
            $type = 'youtube';
        }
    }

    $url = trim((string) ep_setting('demo_video_url', ''));
    $id = ep_youtube_id_from_url($url);
    $isDefault = $type === 'youtube' && $id === '';

    return [
        'type' => $type,
        'url' => $url,
        'title' => $title,
        'youtube_id' => $isDefault ? $fallbackId : $id,
        'start' => $isDefault ? 0 : ep_youtube_start_from_url($url),
        'file_url' => $fileUrl,
        'is_default' => $isDefault,
    ];
}

function ep_video_thumbnail_url(array $video): string
{
    // Same guard as the case-study thumbnails: a path whose file is gone
    // renders an <img> that 404s on load, so fall back to the placeholder.
    $path = trim((string) ($video['thumbnail_path'] ?? ''));
    if ($path === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $path) && !is_file(dirname(__DIR__) . '/' . ltrim($path, '/'))) {
        return '';
    }
    return ep_url($path);
}

function ep_video_file_url(array $video): string
{
    $path = ep_normalize_public_path((string) ($video['video_file_path'] ?? ''), 'assets/video-testimonials');
    return $path !== '' ? ep_url($path) : '';
}

function ep_video_file_path_relative(array $video): string
{
    $path = ep_normalize_public_path((string) ($video['video_file_path'] ?? ''), 'assets/video-testimonials');
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return '';
    }
    return ltrim($path, '/');
}

function ep_get_contact_items(bool $contactPageOnly = false): array
{
    static $cache = [];
    if (isset($cache[$contactPageOnly ? 1 : 0])) {
        return $cache[$contactPageOnly ? 1 : 0];
    }
    global $m;
    $m->where('is_active', 1);
    if ($contactPageOnly) {
        $m->where('show_on_contact_page', 1);
    } else {
        $m->where('show_in_footer', 1);
    }
    $m->orderBy('sort_order', 'ASC');
    $rows = $m->get('ep_contact_items') ?: [];
    $cache[$contactPageOnly ? 1 : 0] = $rows;
    return $rows;
}

function ep_get_social_links(bool $footerOnly = true): array
{
    static $cache = [];
    if (isset($cache[1])) {
        // footerOnly=true is always a strict subset of the unfiltered set
        // (same is_active=1 base, plus show_in_footer=1) — derive it in PHP
        // instead of a second query once the broader set is cached.
        return $footerOnly
            ? array_values(array_filter($cache[1], fn(array $s) => !empty($s['show_in_footer'])))
            : $cache[1];
    }
    global $m;
    $m->where('is_active', 1);
    $m->orderBy('sort_order', 'ASC');
    $rows = $m->get('ep_social_links') ?: [];
    // Drop entries whose URL was never filled in. Rows are seeded with "#"
    // as a placeholder, which rendered as a dead link in the footer of every
    // page. Filtering here rather than in the template keeps the row intact —
    // the icon reappears on its own once a real URL is saved in the CMS.
    $rows = array_values(array_filter($rows, static function (array $s): bool {
        $url = trim((string) ($s['url'] ?? ''));
        return $url !== '' && $url !== '#';
    }));
    $cache[1] = $rows;
    return $footerOnly ? array_values(array_filter($rows, fn(array $s) => !empty($s['show_in_footer']))) : $rows;
}

/**
 * Organization JSON-LD node, built from real site settings, contact items,
 * and social links (only those explicitly flagged show_in_schema=1) —
 * no invented company details, dates, prices, or social URLs.
 */
function ep_organization_schema(): array
{
    $base = 'https://eduportal.pk/';
    $siteName = ep_setting('site_name', 'EduPortal');

    $phone = '';
    $email = ep_setting('support_email', '');
    foreach (ep_get_contact_items(false) as $item) {
        if ($item['item_type'] === 'whatsapp' && !empty($item['is_primary'])) {
            $phone = trim((string) $item['value']);
        }
        if ($item['item_type'] === 'email' && !empty($item['is_primary']) && !empty($item['value'])) {
            $email = trim((string) $item['value']);
        }
    }

    $sameAs = [];
    foreach (ep_get_social_links(false) as $social) {
        $url = trim((string) ($social['url'] ?? ''));
        if (!empty($social['show_in_schema']) && $url !== '' && $url !== '#') {
            $sameAs[] = $url;
        }
    }

    $org = [
        '@type' => 'Organization',
        '@id' => $base . '#organization',
        'name' => $siteName,
        'legalName' => $siteName,
        'url' => $base,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $base . 'assets/logo_icon.jpg',
            'width' => 512,
            'height' => 512,
        ],
        'image' => $base . 'assets/logo_icon.jpg',
        'description' => ep_setting('site_tagline', $siteName),
    ];

    if ($email !== '') {
        $org['email'] = $email;
    }
    if ($phone !== '') {
        $org['telephone'] = $phone;
    }
    $foundingYear = ep_setting('founding_year', '');
    if ($foundingYear !== '') {
        $org['foundingDate'] = $foundingYear;
    }

    $street = ep_setting('office_street', '');
    if ($street !== '') {
        $org['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => ep_setting('office_city', ''),
            'addressRegion' => ep_setting('office_region', ''),
            'postalCode' => ep_setting('office_postal', ''),
            'addressCountry' => ep_setting('office_country', ''),
        ];
    }

    if ($phone !== '' || $email !== '') {
        $contactPoint = ['@type' => 'ContactPoint', 'contactType' => 'customer support'];
        if ($phone !== '') {
            $contactPoint['telephone'] = $phone;
        }
        if ($email !== '') {
            $contactPoint['email'] = $email;
        }
        $countryCode = ep_setting('office_country', 'PK');
        if ($countryCode === 'PK') {
            // "Pakistan" is the standard expansion of the real office_country=PK
            // setting, matching the same claim already published site-wide
            // (e.g. "Nationwide Coverage Across Pakistan" on about.php) —
            // not a broader/unsupported claim like "worldwide".
            $contactPoint['areaServed'] = ['@type' => 'Country', 'name' => 'Pakistan'];
        } elseif ($countryCode !== '') {
            $contactPoint['areaServed'] = $countryCode;
        }
        $contactPoint['availableLanguage'] = ['English', 'Urdu'];
        $org['contactPoint'] = [$contactPoint];
    }

    if ($sameAs) {
        $org['sameAs'] = $sameAs;
    }

    return $org;
}

function ep_get_home_feature_cards(): array
{
    global $m;
    $m->where('is_active', 1);
    $m->orderBy('sort_order', 'ASC');
    return $m->get('ep_home_feature_cards') ?: [];
}

function ep_save_demo_lead(array $data): ?int
{
    global $m;
    $insert = [
        'institute_name' => $data['institute_name'] ?? '',
        'student_count' => $data['student_count'] ?? null,
        'contact_name' => $data['contact_name'] ?? '',
        'designation' => $data['designation'] ?? '',
        'country_code' => $data['country_code'] ?? null,
        'whatsapp' => $data['whatsapp'] ?? null,
        'whatsapp_full' => $data['whatsapp_full'] ?? '',
        'source_page' => $data['source_page'] ?? null,
        'ip_address' => $data['ip_address'] ?? null,
        'user_agent' => isset($data['user_agent']) ? substr($data['user_agent'], 0, 500) : null,
        'status' => 'new',
    ];
    $id = $m->insert('ep_demo_leads', $insert);
    return $id ? (int) $id : null;
}

function ep_contact_icon(string $type): string
{
    $map = [
        'phone' => 'phone',
        'whatsapp' => 'phone',
        'email' => 'mail',
        'address' => 'map-pin',
        'hours' => 'clock',
        'map_link' => 'map-pin',
    ];
    return $map[$type] ?? 'circle';
}

function ep_page_label(string $pageKey): string
{
    static $labels = [
        'blog-list' => 'Blog listing',
        'case-studies-list' => 'Case studies listing',
        'videos-list' => 'Video testimonials',
        'contact' => 'Contact page',
        'index.html' => 'Homepage',
        'blog.php' => 'Blog listing',
        'case-studies.php' => 'Case studies listing',
        'videos.php' => 'Videos listing',
        'contact.php' => 'Contact page',
    ];
    if (isset($labels[$pageKey])) {
        return $labels[$pageKey];
    }
    if (str_starts_with($pageKey, 'blog:')) {
        return 'Blog: ' . substr($pageKey, 5);
    }
    if (str_starts_with($pageKey, 'case-study:')) {
        return 'Case study: ' . substr($pageKey, 12);
    }
    return $pageKey;
}

/* ---------------------------------------------------------------------
 * Team members — admin-managed roster shown as cards on /team.php.
 * ------------------------------------------------------------------ */

function ep_ensure_team_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $m;
    $db = $m->mysqli();

    $db->query("CREATE TABLE IF NOT EXISTS ep_team_members (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(150) NOT NULL,
      designation VARCHAR(150) NOT NULL DEFAULT '',
      photo_path VARCHAR(255) NOT NULL DEFAULT '',
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_sort (sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ep_team_members_table_ready(): bool
{
    global $m;
    $db = $m->mysqli();
    $res = @$db->query("SHOW TABLES LIKE 'ep_team_members'");
    if (!$res) {
        return false;
    }
    $ok = $res->num_rows > 0;
    $res->free();
    return $ok;
}

/**
 * @return array<int, array<string, mixed>>
 */
function ep_get_team_members(bool $onlyActive = true): array
{
    if (!ep_team_members_table_ready()) {
        return [];
    }
    global $m;
    if ($onlyActive) {
        $m->where('is_active', 1);
    }
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('id', 'ASC');
    $rows = $m->get('ep_team_members');
    return is_array($rows) ? $rows : [];
}

/**
 * @return array<string, mixed>|null
 */
function ep_get_team_member(int $id): ?array
{
    if (!ep_team_members_table_ready() || $id <= 0) {
        return null;
    }
    global $m;
    $m->where('id', $id);
    $row = $m->getOne('ep_team_members');
    return $row ?: null;
}
