<?php
/**
 * Save Book-a-Demo form to ep_demo_leads (JSON API).
 *
 * This is the LOCAL MIRROR of the lead, not the primary destination: the
 * browser also fires the lead at the CRM (js/lead-form.js). That CRM request
 * uses mode:'no-cors', so its response is opaque and the front end cannot tell
 * whether it succeeded -- meaning without this mirror a CRM outage loses the
 * lead silently while still showing the visitor a success message.
 *
 * POST JSON: instituteName, studentCount, fullName, designation,
 *            country_code, whatsapp, whatsapp_full, source_page
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/includes/cms.php';

$raw = file_get_contents('php://input');

// Cap the request body before decoding. A legitimate lead is well under 4 KB;
// anything larger is either a bug or an attempt to make the server do work.
if (strlen((string) $raw) > 8192) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Payload too large']);
    exit;
}

$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

/**
 * Trim and hard-cap a field to its column width. sql_mode here does NOT
 * include STRICT_TRANS_TABLES, so an over-length value is silently truncated
 * by MySQL rather than rejected -- the cap has to be applied in PHP for the
 * stored value to be predictable.
 */
$field = static function (array $d, array $keys, int $max): string {
    foreach ($keys as $k) {
        if (isset($d[$k]) && is_scalar($d[$k])) {
            $v = trim((string) $d[$k]);
            if ($v !== '') {
                return mb_substr($v, 0, $max);
            }
        }
    }
    return '';
};

// Honeypot: a field no human sees and no real browser fills. Bots that submit
// every input give themselves away here. Answer 200 so the bot cannot tell it
// was rejected and start probing for the reason.
$trap = $field($data, ['company_website', 'website'], 200);
if ($trap !== '') {
    echo json_encode(['ok' => true, 'id' => 0]);
    exit;
}

$institute    = $field($data, ['instituteName', 'institute_name'], 200);
$name         = $field($data, ['fullName', 'contact_name'], 120);
$designation  = $field($data, ['designation'], 120);
$whatsappFull = $field($data, ['whatsapp_full', 'whatsappFull'], 30);

if ($institute === '' || $name === '' || $designation === '' || $whatsappFull === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

// A WhatsApp number is digits, spaces and the usual separators. Rejecting
// anything else keeps link spam and injection attempts out of the field the
// sales team clicks on.
if (!preg_match('/^[0-9+()\-\s]{8,30}$/', $whatsappFull)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid WhatsApp number']);
    exit;
}

$ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

// Rate limit by IP against the leads already stored, so no extra table is
// needed. Genuine duplicate enquiries from one office are rare; six in ten
// minutes is a script.
try {
    global $m;
    $recent = $m->rawQueryOne(
        'SELECT COUNT(*) AS n FROM ep_demo_leads
          WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)',
        [$ip]
    );
    if ($recent && (int) $recent['n'] >= 6) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'Too many requests. Please try again shortly.']);
        exit;
    }
} catch (Throwable $e) {
    // Fail open: a broken rate-limit query must never block a real lead.
    error_log('[EduPortal] lead rate-limit check failed: ' . $e->getMessage());
}

$id = ep_save_demo_lead([
    'institute_name' => $institute,
    'student_count'  => $field($data, ['studentCount', 'student_count'], 40),
    'contact_name'   => $name,
    'designation'    => $designation,
    'country_code'   => $field($data, ['country_code'], 10),
    'whatsapp'       => $field($data, ['whatsapp'], 30),
    'whatsapp_full'  => $whatsappFull,
    'source_page'    => $field($data, ['source_page'], 255)
        ?: substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255),
    'ip_address'     => $ip,
    'user_agent'     => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
]);

if (!$id) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save lead']);
    exit;
}

echo json_encode(['ok' => true, 'id' => $id]);
