<?php
/**
 * Save Book-a-Demo form to ep_demo_leads (JSON API).
 * POST JSON: instituteName, studentCount, fullName, designation, country_code, whatsapp, whatsapp_full, source_page
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/includes/cms.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$institute = trim((string) ($data['instituteName'] ?? $data['institute_name'] ?? ''));
$name = trim((string) ($data['fullName'] ?? $data['contact_name'] ?? ''));
$designation = trim((string) ($data['designation'] ?? ''));
$whatsappFull = trim((string) ($data['whatsapp_full'] ?? $data['whatsappFull'] ?? ''));

if ($institute === '' || $name === '' || $designation === '' || $whatsappFull === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

$id = ep_save_demo_lead([
    'institute_name' => $institute,
    'student_count' => trim((string) ($data['studentCount'] ?? $data['student_count'] ?? '')),
    'contact_name' => $name,
    'designation' => $designation,
    'country_code' => trim((string) ($data['country_code'] ?? '')),
    'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
    'whatsapp_full' => $whatsappFull,
    'source_page' => trim((string) ($data['source_page'] ?? ($_SERVER['HTTP_REFERER'] ?? ''))),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
]);

if (!$id) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save lead']);
    exit;
}

echo json_encode(['ok' => true, 'id' => $id]);
