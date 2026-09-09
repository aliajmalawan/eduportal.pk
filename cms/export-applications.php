<?php
/**
 * Admin — export the currently filtered job applications to Excel.
 *
 * The project has no spreadsheet library (no Composer/vendor directory), so
 * this writes CSV with a UTF-8 BOM and CRLF line endings — the format Excel
 * opens natively with correct Urdu/accented characters, and which every other
 * spreadsheet tool reads too. It reuses cms_get_applications(), so the export
 * always contains exactly the rows the admin is looking at.
 */
require_once __DIR__ . '/includes/careers-admin.php';

cms_require_permission('applications.manage');

$filters = cms_application_filters($_GET);
$applications = cms_get_applications($filters);

$filename = 'eduportal-applications-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel detects the encoding instead of guessing the local codepage.
fwrite($out, "\xEF\xBB\xBF");

$columns = [
    'ID' => static fn(array $a): string => (string) $a['id'],
    'Applied at' => static fn(array $a): string => (string) $a['applied_at'],
    'Position' => static fn(array $a): string => $a['job_id'] === null ? 'General Application' : (string) $a['job_title_snapshot'],
    'Status' => static fn(array $a): string => (string) $a['status'],
    'Full name' => static fn(array $a): string => (string) $a['full_name'],
    'Email' => static fn(array $a): string => (string) $a['email'],
    'WhatsApp' => static fn(array $a): string => (string) $a['whatsapp'],
    'City' => static fn(array $a): string => (string) $a['city'],
    'Years of experience' => static fn(array $a): string => (string) $a['years_experience'],
    'Relevant experience' => static fn(array $a): string => (string) ($a['relevant_experience'] ?? ''),
    'Expected salary' => static fn(array $a): string => $a['expected_salary'] === null ? '' : (string) $a['expected_salary'],
    'Joining time' => static fn(array $a): string => (string) $a['joining_time'],
    'LinkedIn' => static fn(array $a): string => (string) $a['linkedin'],
    'Cover letter' => static fn(array $a): string => (string) ($a['cover_letter'] ?? ''),
    'Résumé file' => static fn(array $a): string => (string) $a['resume_original_name'],
    'Has photo' => static fn(array $a): string => trim((string) $a['photo']) !== '' ? 'Yes' : 'No',
    'Additional information' => static function (array $a): string {
        $pairs = [];
        foreach (ep_careers_extra_fields_display($a) as $label => $value) {
            $pairs[] = $label . ': ' . $value;
        }
        return implode(' | ', $pairs);
    },
    'Admin notes' => static fn(array $a): string => (string) ($a['admin_notes'] ?? ''),
];

/**
 * Neutralises spreadsheet formula injection: a cell that Excel would treat as
 * a formula is prefixed with a single quote, so applicant-supplied text can
 * never execute when the export is opened.
 */
$safeCell = static function (string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t"], true)) {
        return "'" . $value;
    }
    return $value;
};

fputcsv($out, array_keys($columns));

foreach ($applications as $application) {
    $row = [];
    foreach ($columns as $getter) {
        $row[] = $safeCell($getter($application));
    }
    fputcsv($out, $row);
}

fclose($out);
