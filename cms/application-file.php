<?php
/**
 * Admin — secure résumé / photo delivery.
 *
 * The upload directory itself is not web-servable (see the .htaccess written
 * by ep_careers_ensure_upload_dirs()), so this script is the only way to read
 * an applicant's files, and it requires an authenticated admin with the
 * applications.manage permission. The file is located from the database row,
 * never from a client-supplied path, and the resolved path is re-checked
 * against the upload root before anything is sent.
 */
require_once __DIR__ . '/includes/bootstrap.php';

cms_require_permission('applications.manage');

global $m;

$id = (int) ($_GET['id'] ?? 0);
$type = (string) ($_GET['type'] ?? 'resume');
$inline = isset($_GET['inline']);

if ($id < 1 || !in_array($type, ['resume', 'photo'], true)) {
    http_response_code(400);
    exit('Bad request.');
}

$m->where('id', $id);
$application = $m->getOne('ep_job_applications');
if (!$application) {
    http_response_code(404);
    exit('Application not found.');
}

if ($type === 'resume') {
    $relPath = (string) ($application['resume'] ?? '');
    $downloadName = (string) ($application['resume_original_name'] ?? '');
    if ($downloadName === '') {
        $downloadName = 'resume.' . pathinfo($relPath, PATHINFO_EXTENSION);
    }
    // Prefix the applicant's name so a folder of downloads stays readable.
    $namePrefix = (string) preg_replace('/[^A-Za-z0-9 _-]+/', '', (string) $application['full_name']);
    $namePrefix = trim((string) preg_replace('/\s+/', ' ', $namePrefix));
    if ($namePrefix !== '') {
        $downloadName = $namePrefix . ' - ' . $downloadName;
    }
    $contentType = (string) ($application['resume_mime'] ?? '') ?: 'application/octet-stream';
} else {
    $relPath = (string) ($application['photo'] ?? '');
    $downloadName = 'photo.jpg';
    // Photos are always re-encoded to JPEG on upload, so this is exact.
    $contentType = 'image/jpeg';
    $inline = true;
}

$absPath = ep_careers_file_abs_path($relPath);
if ($absPath === '' || !is_file($absPath)) {
    http_response_code(404);
    exit('File not found.');
}

// Never let the browser sniff a different type out of the bytes, and never
// let this response be cached by a shared proxy — it is private data.
header('Content-Type: ' . $contentType);
header('Content-Length: ' . (string) filesize($absPath));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; img-src \'self\'; object-src \'none\'; sandbox');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

while (ob_get_level() > 0) {
    ob_end_clean();
}
readfile($absPath);
