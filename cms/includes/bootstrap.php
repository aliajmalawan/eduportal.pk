<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/cms.php';
require_once dirname(__DIR__, 2) . '/includes/careers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cms_csrf'])) {
    $_SESSION['cms_csrf'] = bin2hex(random_bytes(24));
}

function cms_csrf(): string
{
    return $_SESSION['cms_csrf'] ?? '';
}

function cms_verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(cms_csrf(), $token);
}

function cms_is_logged_in(): bool
{
    return !empty($_SESSION['cms_user']);
}

function cms_user(): ?array
{
    return $_SESSION['cms_user'] ?? null;
}

function cms_require_auth(): void
{
    if (!cms_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function cms_require_super_admin(): void
{
    cms_require_auth();
    $user = cms_user();
    if (($user['role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}

function cms_permissions_catalog(): array
{
    return [
        'dashboard.view' => 'Dashboard',
        'visitors.view' => 'Visitor Stats',
        'leads.manage' => 'Leads',
        'blogs.manage' => 'Blogs',
        'case_studies.manage' => 'Case Studies',
        'videos.manage' => 'Video Testimonials',
        'features.manage' => 'Homepage Features',
        'media.manage' => 'Media Manager',
        'contact.manage' => 'Contact Items',
        'social.manage' => 'Social Links',
        'settings.manage' => 'Site Settings',
        'reviews.manage' => 'Google Reviews',
        'docs.manage' => 'Documentation',
        'comparisons.manage' => 'Comparison Pages',
        'careers.manage' => 'Jobs & Careers',
        'applications.manage' => 'Job Applications',
        'team.manage' => 'Team Members',
        'users.manage' => 'Users',
        'permissions.manage' => 'Permissions',
    ];
}

function cms_default_role_permissions(string $role): array
{
    $all = array_fill_keys(array_keys(cms_permissions_catalog()), true);
    if ($role === 'super_admin') {
        return $all;
    }

    $editor = array_fill_keys(array_keys(cms_permissions_catalog()), false);
    foreach ([
        'dashboard.view',
        'visitors.view',
        'leads.manage',
        'blogs.manage',
        'case_studies.manage',
        'videos.manage',
        'features.manage',
        'media.manage',
        'contact.manage',
        'social.manage',
        'team.manage',
    ] as $perm) {
        $editor[$perm] = true;
    }
    return $editor;
}

function cms_ensure_permissions_tables(): void
{
    global $m;
    $db = $m->mysqli();
    $sql = "CREATE TABLE IF NOT EXISTS ep_admin_user_permissions (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id INT UNSIGNED NOT NULL,
      permission_key VARCHAR(80) NOT NULL,
      is_allowed TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_user_permission (user_id, permission_key),
      CONSTRAINT fk_ep_admin_user_permissions_user FOREIGN KEY (user_id) REFERENCES ep_admin_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->query($sql);
}

function cms_user_permissions(int $userId, string $role): array
{
    global $m;
    $perms = cms_default_role_permissions($role);
    $m->where('user_id', $userId);
    $rows = $m->get('ep_admin_user_permissions');
    foreach ($rows as $row) {
        $key = (string) $row['permission_key'];
        if (array_key_exists($key, $perms)) {
            $perms[$key] = (int) $row['is_allowed'] === 1;
        }
    }
    return $perms;
}

function cms_can(string $permission): bool
{
    $user = cms_user();
    if (!$user) {
        return false;
    }
    if (($user['role'] ?? '') === 'super_admin') {
        return true;
    }
    if (empty($_SESSION['cms_perms']) || !is_array($_SESSION['cms_perms'])) {
        $_SESSION['cms_perms'] = cms_user_permissions((int) $user['id'], (string) $user['role']);
    }
    return (bool) ($_SESSION['cms_perms'][$permission] ?? false);
}

function cms_require_permission(string $permission): void
{
    cms_require_auth();
    if (!cms_can($permission)) {
        http_response_code(403);
        exit('You do not have permission for this page.');
    }
}

function cms_set_user_permission_overrides(int $userId, array $checkedPermissions): void
{
    global $m;
    $catalog = cms_permissions_catalog();
    $m->where('user_id', $userId);
    $m->delete('ep_admin_user_permissions');
    foreach ($catalog as $perm => $_label) {
        $allowed = in_array($perm, $checkedPermissions, true) ? 1 : 0;
        $m->insert('ep_admin_user_permissions', [
            'user_id' => $userId,
            'permission_key' => $perm,
            'is_allowed' => $allowed,
        ]);
    }
}

function cms_landing_page(): string
{
    $map = [
        'dashboard.view' => 'index.php',
        'leads.manage' => 'leads.php',
        'blogs.manage' => 'blogs.php',
        'case_studies.manage' => 'case-studies.php',
        'videos.manage' => 'videos.php',
        'features.manage' => 'features.php',
        'media.manage' => 'media.php',
        'contact.manage' => 'contact-items.php',
        'social.manage' => 'social-links.php',
        'settings.manage' => 'settings.php',
        'reviews.manage' => 'google-reviews.php',
        'docs.manage' => 'docs.php',
        'comparisons.manage' => 'comparisons.php',
        'careers.manage' => 'careers.php',
        'applications.manage' => 'job-applications.php',
        'visitors.view' => 'visitors.php',
        'users.manage' => 'users.php',
        'permissions.manage' => 'permissions.php',
    ];
    foreach ($map as $perm => $route) {
        if (cms_can($perm)) {
            return $route;
        }
    }
    return 'login.php';
}

/**
 * Brute-force throttle for the admin login.
 *
 * Failures are recorded per IP and per email so that neither a single address
 * hammering many accounts nor many addresses hammering one account goes
 * unnoticed. A successful login clears the counters, so an admin who mistypes
 * a couple of times is never locked out for long.
 */
const CMS_LOGIN_MAX_ATTEMPTS = 8;
const CMS_LOGIN_WINDOW_MINUTES = 15;

function cms_ensure_login_attempts_table(): void
{
    global $m;
    $db = $m->mysqli();
    $db->query("CREATE TABLE IF NOT EXISTS ep_admin_login_attempts (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      ip_address VARCHAR(45) NOT NULL DEFAULT '',
      email VARCHAR(190) NOT NULL DEFAULT '',
      attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY ip_time_idx (ip_address, attempted_at),
      KEY email_time_idx (email, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function cms_login_client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Seconds the caller must wait before trying again, or 0 if they may try now. */
function cms_login_lockout_seconds(string $email): int
{
    global $m;
    $ip = cms_login_client_ip();
    $email = substr(trim($email), 0, 190);

    // A throttle that throws is a throttle that locks everyone out, so a
    // failure here fails open rather than denying a legitimate login.
    try {
        // The remaining wait is computed by MySQL, not PHP. attempted_at is
        // written on the database clock, and this project's PHP runs on
        // Europe/Berlin while MySQL runs on SYSTEM -- a three-hour gap that
        // made a 15-minute lockout report as 195 minutes when the two clocks
        // were mixed in one calculation.
        $row = $m->rawQueryOne(
            "SELECT COUNT(*) AS n,
                    TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL ? MINUTE) AS wait_seconds
               FROM ep_admin_login_attempts
              WHERE attempted_at > (NOW() - INTERVAL ? MINUTE)
                AND (ip_address = ? OR (email <> '' AND email = ?))",
            [CMS_LOGIN_WINDOW_MINUTES, CMS_LOGIN_WINDOW_MINUTES, $ip, $email]
        );
    } catch (Throwable $e) {
        error_log('[EduPortal] login throttle unavailable: ' . $e->getMessage());
        return 0;
    }

    if (!$row || (int) $row['n'] < CMS_LOGIN_MAX_ATTEMPTS) {
        return 0;
    }
    return max(1, (int) $row['wait_seconds']);
}

function cms_login_record_failure(string $email): void
{
    global $m;
    try {
        $m->insert('ep_admin_login_attempts', [
            'ip_address' => cms_login_client_ip(),
            'email' => substr(trim($email), 0, 190),
        ]);
    } catch (Throwable $e) {
        error_log('[EduPortal] could not record login failure: ' . $e->getMessage());
    }
}

function cms_login_clear_failures(string $email): void
{
    global $m;
    try {
        $m->rawQuery(
            'DELETE FROM ep_admin_login_attempts WHERE ip_address = ? OR email = ?',
            [cms_login_client_ip(), substr(trim($email), 0, 190)]
        );
        // Opportunistic cleanup so the table cannot grow without bound.
        $m->rawQuery('DELETE FROM ep_admin_login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    } catch (Throwable $e) {
        error_log('[EduPortal] could not clear login failures: ' . $e->getMessage());
    }
}

function cms_login(string $email, string $password): bool
{
    global $m;
    $m->where('email', trim($email));
    $m->where('is_active', 1);
    $user = $m->getOne('ep_admin_users');
    if (!$user) {
        cms_login_record_failure($email);
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        cms_login_record_failure($email);
        return false;
    }

    // Rotate the session id before any authenticated data is written to it.
    // Without this, a session id planted before login (via a link or a shared
    // machine) stays valid afterwards and inherits the new privileges.
    session_regenerate_id(true);
    cms_login_clear_failures($email);

    $_SESSION['cms_user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    $_SESSION['cms_perms'] = cms_user_permissions((int) $user['id'], (string) $user['role']);

    $m->where('id', (int) $user['id']);
    $m->update('ep_admin_users', ['last_login_at' => date('Y-m-d H:i:s')]);
    return true;
}

function cms_logout(): void
{
    unset($_SESSION['cms_user']);
    unset($_SESSION['cms_perms']);
}

function cms_flash(string $type, string $message): void
{
    $_SESSION['cms_flash'] = ['type' => $type, 'message' => $message];
}

function cms_flash_get(): ?array
{
    if (empty($_SESSION['cms_flash'])) {
        return null;
    }
    $flash = $_SESSION['cms_flash'];
    unset($_SESSION['cms_flash']);
    return $flash;
}

function cms_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function cms_seed_super_admin_if_missing(): void
{
    global $m;
    $db = $m->mysqli();
    $res = $db->query('SELECT COUNT(*) FROM ep_admin_users');
    $count = 0;
    if ($res) {
        $row = $res->fetch_row();
        $count = (int) ($row[0] ?? 0);
        $res->free();
    }
    if ($count > 0) {
        return;
    }
    $m->insert('ep_admin_users', [
        'name' => 'Super Admin',
        'email' => 'admin@eduportal.local',
        'password_hash' => password_hash('Admin@12345', PASSWORD_DEFAULT),
        'role' => 'super_admin',
        'is_active' => 1,
    ]);
}

function cms_ensure_visitor_tables(): void
{
    global $m;
    $db = $m->mysqli();
    $sql = "CREATE TABLE IF NOT EXISTS ep_visitor_hits (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      visit_date DATE NOT NULL,
      page_key VARCHAR(190) NOT NULL,
      visitor_hash VARCHAR(64) NOT NULL,
      ip_address VARCHAR(45) NULL,
      user_agent VARCHAR(255) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_daily_visitor_page (visit_date, page_key, visitor_hash),
      KEY idx_visit_date_page (visit_date, page_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->query($sql);
}

function cms_ensure_media_storage(): void
{
    $dir = dirname(__DIR__, 2) . '/uploads/cms-media';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function cms_blog_thumbnail_dir_abs(): string
{
    return dirname(__DIR__, 2) . '/assets/blog-thumbnail';
}

function cms_blog_thumbnail_dir_rel(): string
{
    return 'assets/blog-thumbnail/';
}

function cms_ensure_blog_thumbnail_dir(): void
{
    $dir = cms_blog_thumbnail_dir_abs();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function cms_compress_blog_image_to_file(string $sourceTmp, string $destAbs, int $maxBytes = 0): bool
{
    if (!extension_loaded('gd')) {
        return is_uploaded_file($sourceTmp) && @copy($sourceTmp, $destAbs);
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

    $maxW = 1200;
    $maxH = 800;
    $scale = min($maxW / $srcW, $maxH / $srcH, 1.0);
    $newW = max(1, (int) round($srcW * $scale));
    $newH = max(1, (int) round($srcH * $scale));

    $work = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($work, 255, 255, 255);
    imagefill($work, 0, 0, $white);
    imagecopyresampled($work, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($src);

    ob_start();
    imagejpeg($work, null, 85);
    $blob = ob_get_clean();
    imagedestroy($work);

    if ($blob === false || $blob === '') {
        return false;
    }

    return file_put_contents($destAbs, $blob) !== false;
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_blog_featured_image(?array $file, string $existingPath, ?string $slugHint = null): string
{
    cms_ensure_blog_thumbnail_dir();
    $existingPath = trim($existingPath);

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        return $existingPath;
    }
    if ($size > 8 * 1024 * 1024) {
        return $existingPath;
    }
    if (!is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'blog-thumb';
    }
    $finalName = $base . '-' . date('YmdHis') . '.jpg';
    $abs = cms_blog_thumbnail_dir_abs() . '/' . $finalName;

    if (!cms_compress_blog_image_to_file($tmp, $abs)) {
        return $existingPath;
    }

    if ($existingPath !== '' && str_starts_with($existingPath, cms_blog_thumbnail_dir_rel())) {
        $old = dirname(__DIR__, 2) . '/' . ltrim($existingPath, '/');
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return cms_blog_thumbnail_dir_rel() . $finalName;
}

function cms_team_photo_dir_abs(): string
{
    return dirname(__DIR__, 2) . '/assets/team-photos';
}

function cms_team_photo_dir_rel(): string
{
    return 'assets/team-photos/';
}

function cms_ensure_team_photo_dir(): void
{
    $dir = cms_team_photo_dir_abs();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_team_photo(?array $file, string $existingPath, ?string $slugHint = null): string
{
    cms_ensure_team_photo_dir();
    $existingPath = trim($existingPath);

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        return $existingPath;
    }
    if ($size > 8 * 1024 * 1024) {
        return $existingPath;
    }
    if (!is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'team-member';
    }
    $finalName = $base . '-' . date('YmdHis') . '.jpg';
    $abs = cms_team_photo_dir_abs() . '/' . $finalName;

    if (!cms_compress_blog_image_to_file($tmp, $abs)) {
        return $existingPath;
    }

    if ($existingPath !== '' && str_starts_with($existingPath, cms_team_photo_dir_rel())) {
        $old = dirname(__DIR__, 2) . '/' . ltrim($existingPath, '/');
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return cms_team_photo_dir_rel() . $finalName;
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_blog_card_thumbnail(?array $file, string $existingPath, ?string $slugHint = null): string
{
    cms_ensure_blog_thumbnail_dir();
    $existingPath = trim($existingPath);

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || $size > 8 * 1024 * 1024 || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'blog-card';
    }
    $finalName = $base . '-card-' . date('YmdHis') . '.jpg';
    $abs = cms_blog_thumbnail_dir_abs() . '/' . $finalName;

    if (!cms_compress_blog_card_thumbnail($tmp, $abs)) {
        return $existingPath;
    }

    if ($existingPath !== '' && str_starts_with($existingPath, cms_blog_thumbnail_dir_rel())) {
        $old = dirname(__DIR__, 2) . '/' . ltrim($existingPath, '/');
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return cms_blog_thumbnail_dir_rel() . $finalName;
}

function cms_compress_blog_card_thumbnail(string $sourceTmp, string $destAbs): bool
{
    if (!extension_loaded('gd')) {
        return is_uploaded_file($sourceTmp) && @copy($sourceTmp, $destAbs);
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

    // Card thumbnail: 600×400 max, never upscale
    $maxW = 600;
    $maxH = 400;
    $scale = min($maxW / $srcW, $maxH / $srcH, 1.0);
    $newW = max(1, (int) round($srcW * $scale));
    $newH = max(1, (int) round($srcH * $scale));

    $work = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($work, 255, 255, 255);
    imagefill($work, 0, 0, $white);
    imagecopyresampled($work, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($src);

    ob_start();
    imagejpeg($work, null, 82);
    $blob = ob_get_clean();
    imagedestroy($work);

    if ($blob === false || $blob === '') {
        return false;
    }

    return file_put_contents($destAbs, $blob) !== false;
}

function cms_blog_read_minutes(string $html): int
{
    $plain = trim(strip_tags($html));
    if ($plain === '') {
        return 1;
    }
    $words = str_word_count($plain);
    return max(1, (int) ceil($words / 200));
}

function cms_thumbnail_dir_abs(): string
{
    return dirname(__DIR__, 2) . '/assets/thumbnail';
}

function cms_thumbnail_dir_rel(): string
{
    return 'assets/thumbnail/';
}

function cms_video_files_dir_abs(): string
{
    return dirname(__DIR__, 2) . '/assets/video-testimonials';
}

function cms_video_files_dir_rel(): string
{
    return 'assets/video-testimonials/';
}

function cms_ensure_thumbnail_dir(): void
{
    if (!is_dir(cms_thumbnail_dir_abs())) {
        @mkdir(cms_thumbnail_dir_abs(), 0775, true);
    }
}

function cms_ensure_video_files_dir(): void
{
    if (!is_dir(cms_video_files_dir_abs())) {
        @mkdir(cms_video_files_dir_abs(), 0775, true);
    }
}

function cms_ensure_video_testimonial_columns(): void
{
    global $m;
    $db = $m->mysqli();
    $existing = [];
    $res = $db->query('SHOW COLUMNS FROM ep_video_testimonials');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $existing[$row['Field']] = true;
        }
    }
    $alters = [];
    if (empty($existing['designation'])) {
        $alters[] = "ADD COLUMN designation VARCHAR(120) NULL DEFAULT NULL AFTER person_name";
    }
    if (empty($existing['video_type'])) {
        $alters[] = "ADD COLUMN video_type VARCHAR(20) NOT NULL DEFAULT 'youtube' AFTER role_filter";
    }
    if (empty($existing['thumbnail_path'])) {
        $alters[] = 'ADD COLUMN thumbnail_path VARCHAR(255) NULL DEFAULT NULL AFTER youtube_start_seconds';
    }
    if (empty($existing['video_file_path'])) {
        $alters[] = 'ADD COLUMN video_file_path VARCHAR(255) NULL DEFAULT NULL AFTER thumbnail_path';
    }
    foreach ($alters as $sql) {
        $db->query('ALTER TABLE ep_video_testimonials ' . $sql);
    }
    $db->query(
        "UPDATE ep_video_testimonials SET designation = person_title
         WHERE (designation IS NULL OR designation = '') AND person_title IS NOT NULL AND person_title != ''"
    );
    $db->query(
        "UPDATE ep_video_testimonials SET video_type = 'upload'
         WHERE video_file_path IS NOT NULL AND TRIM(video_file_path) != ''
           AND (video_type IS NULL OR TRIM(video_type) = '' OR LOWER(video_type) != 'upload')"
    );
}

function cms_delete_public_file(?string $relPath, string $allowedPrefix): void
{
    $relPath = trim((string) $relPath);
    if ($relPath === '' || !str_starts_with($relPath, $allowedPrefix)) {
        return;
    }
    $abs = dirname(__DIR__, 2) . '/' . ltrim($relPath, '/');
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_video_thumbnail(?array $file, string $existingPath, ?string $slugHint = null, int $maxBytes = 51200): string
{
    cms_ensure_thumbnail_dir();
    $existingPath = trim($existingPath);

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || $size > 8 * 1024 * 1024 || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME)) ?: 'video-thumb';
    $finalName = $base . '-' . date('YmdHis') . '.jpg';
    $abs = cms_thumbnail_dir_abs() . '/' . $finalName;

    if (!cms_compress_blog_image_to_file($tmp, $abs, $maxBytes)) {
        return $existingPath;
    }

    cms_delete_public_file($existingPath, cms_thumbnail_dir_rel());
    return cms_thumbnail_dir_rel() . $finalName;
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_video_file(?array $file, string $existingPath, ?string $slugHint = null): string
{
    cms_ensure_video_files_dir();
    $existingPath = trim($existingPath);
    $maxBytes = 200 * 1024 * 1024;

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'mov'], true)) {
        return $existingPath;
    }
    if ($size > $maxBytes || $size < 1 || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME)) ?: 'testimonial';
    $finalName = $base . '-' . date('YmdHis') . '.' . $ext;
    $abs = cms_video_files_dir_abs() . '/' . $finalName;

    if (!move_uploaded_file($tmp, $abs)) {
        return $existingPath;
    }

    // Auto-convert MOV to MP4 for browser compatibility
    if ($ext === 'mov') {
        $mp4Name = $base . '-' . date('YmdHis') . '.mp4';
        $mp4Abs  = cms_video_files_dir_abs() . '/' . $mp4Name;
        if (cms_ffmpeg_convert($abs, $mp4Abs)) {
            @unlink($abs);
            $finalName = $mp4Name;
        }
    }

    cms_delete_public_file($existingPath, cms_video_files_dir_rel());
    return cms_video_files_dir_rel() . $finalName;
}

function cms_ffmpeg_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!function_exists('exec')) {
        return $cached = '';
    }

    $isWindows = DIRECTORY_SEPARATOR === '\\';

    $fixedPaths = $isWindows
        ? ['C:\\ffmpeg\\bin\\ffmpeg.exe', 'C:\\xampp\\ffmpeg\\ffmpeg.exe']
        : ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/ffmpeg/bin/ffmpeg'];
    foreach ($fixedPaths as $p) {
        if (is_file($p) && ($isWindows || is_executable($p))) {
            return $cached = $p;
        }
    }

    // Falls back to whatever the PATH resolves — "where" on Windows,
    // "which" everywhere else, since neither command exists on the other OS.
    $lookupCmd = $isWindows ? 'where ffmpeg 2>NUL' : 'which ffmpeg 2>/dev/null';
    $out = [];
    $ret = 0;
    @exec($lookupCmd, $out, $ret);
    $found = trim($out[0] ?? '');
    if ($ret === 0 && $found !== '' && is_file($found)) {
        return $cached = $found;
    }

    return $cached = '';
}

function cms_ffmpeg_convert(string $input, string $output): bool
{
    $ffmpeg = cms_ffmpeg_path();
    if ($ffmpeg === '') {
        return false;
    }
    // Caps the long edge at 1280px (never upscales a smaller video) — a
    // testimonial doesn't need to be 4K, and the resulting file is both
    // much smaller and starts playing much faster once a visitor clicks
    // it, which matters more here than source resolution ever did.
    $nullSink = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
    $cmd = $ffmpeg
        . ' -y -i ' . escapeshellarg($input)
        . ' -vf ' . escapeshellarg("scale='min(1280,iw)':'min(1280,ih)':force_original_aspect_ratio=decrease:force_divisible_by=2")
        . ' -c:v libx264 -preset medium -crf 23'
        . ' -c:a aac -movflags +faststart'
        . ' ' . escapeshellarg($output)
        . ' 2>' . $nullSink;
    $ret = 0;
    @exec($cmd, $dummy, $ret);
    return $ret === 0 && is_file($output) && filesize($output) > 0;
}

function cms_case_study_thumbnail_dir_abs(): string
{
    return dirname(__DIR__, 2) . '/assets/case-study-thumbnail';
}

function cms_case_study_thumbnail_dir_rel(): string
{
    return 'assets/case-study-thumbnail/';
}

function cms_ensure_case_study_thumbnail_dir(): void
{
    $dir = cms_case_study_thumbnail_dir_abs();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

/**
 * @param array<string, mixed>|null $file
 */
function cms_upload_case_study_thumbnail(?array $file, string $existingPath, ?string $slugHint = null): string
{
    cms_ensure_case_study_thumbnail_dir();
    $existingPath = trim($existingPath);

    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp  = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $size = (int)   ($file['size']     ?? 0);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return $existingPath;
    }
    if ($size > 8 * 1024 * 1024 || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $base      = cms_slugify($slugHint ?: pathinfo($name, PATHINFO_FILENAME)) ?: 'cs-thumb';
    $finalName = $base . '-' . date('YmdHis') . '.jpg';
    $abs       = cms_case_study_thumbnail_dir_abs() . '/' . $finalName;

    if (!cms_compress_blog_image_to_file($tmp, $abs)) {
        return $existingPath;
    }

    if ($existingPath !== '' && str_starts_with($existingPath, cms_case_study_thumbnail_dir_rel())) {
        $old = dirname(__DIR__, 2) . '/' . ltrim($existingPath, '/');
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return cms_case_study_thumbnail_dir_rel() . $finalName;
}

function cms_migrate_case_studies_schema(): void
{
    global $m;
    $db = $m->mysqli();
    $existing = [];
    $res = $db->query('SHOW COLUMNS FROM ep_case_studies');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $existing[(string) ($row['Field'] ?? '')] = true;
        }
        $res->free();
    }
    $alters = [];
    if (empty($existing['thumbnail_path'])) {
        $alters[] = "ADD COLUMN thumbnail_path VARCHAR(255) NOT NULL DEFAULT '' AFTER hero_image";
    }
    if (empty($existing['description'])) {
        $alters[] = 'ADD COLUMN description LONGTEXT NULL AFTER summary';
    }
    if (empty($existing['published_at'])) {
        $alters[] = 'ADD COLUMN published_at DATETIME NULL AFTER status';
    }
    foreach ($alters as $sql) {
        $db->query('ALTER TABLE ep_case_studies ' . $sql);
    }
}

cms_seed_super_admin_if_missing();
cms_ensure_permissions_tables();
cms_ensure_login_attempts_table();
cms_ensure_visitor_tables();
cms_ensure_media_storage();
cms_ensure_blog_thumbnail_dir();
cms_ensure_thumbnail_dir();
cms_ensure_video_files_dir();
cms_ensure_video_testimonial_columns();
cms_ensure_case_study_thumbnail_dir();
cms_migrate_case_studies_schema();
ep_ensure_careers_schema();
ep_ensure_comparison_schema();
ep_ensure_docs_schema();
ep_ensure_team_schema();
cms_ensure_team_photo_dir();
