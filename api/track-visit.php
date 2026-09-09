<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

require_once dirname(__DIR__) . '/cms/includes/bootstrap.php';
global $m;

$raw = json_decode((string) file_get_contents('php://input'), true);
$page = trim((string) ($raw['page'] ?? 'unknown'));
if ($page === '') {
    $page = 'unknown';
}

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$cookie = (string) ($_COOKIE['epv'] ?? '');
if ($cookie === '') {
    $cookie = bin2hex(random_bytes(16));
    setcookie('epv', $cookie, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$hash = hash('sha256', $cookie . '|' . $ip . '|' . $ua);

// uq_daily_visitor_page deliberately allows only one row per visitor, per
// page, per day — so the SECOND view of a page by the same person is a
// duplicate by design, not an error. A plain insert() threw an uncaught
// mysqli_sql_exception there, which returned HTTP 500 to the fetch() in
// includes/partials/public-track.php and logged a stack trace on every
// repeat visit. ON DUPLICATE KEY UPDATE makes the repeat a no-op.
//
// It stays wrapped as well: analytics must never be able to surface an
// error to a visitor, and this endpoint's response is not used for anything.
try {
    $m->rawQuery(
        'INSERT INTO ep_visitor_hits (visit_date, page_key, visitor_hash, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE id = id',
        [date('Y-m-d'), substr($page, 0, 190), $hash, substr($ip, 0, 45), $ua]
    );
} catch (Throwable $e) {
    error_log('[EduPortal] visitor tracking failed: ' . $e->getMessage());
}

echo json_encode(['ok' => true]);
