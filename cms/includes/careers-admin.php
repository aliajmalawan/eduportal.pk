<?php
/**
 * Admin-side helpers for job applications, shared by job-applications.php and
 * export-applications.php so the exported rows are always exactly the rows the
 * admin is looking at — one filter implementation, not two.
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Reads and validates the application filters from the query string.
 *
 * @param array<string, mixed> $query Normally $_GET
 * @return array{status: string, job_id: int, general: string, q: string, from: string, to: string}
 */
function cms_application_filters(array $query): array
{
    $status = trim((string) ($query['status'] ?? ''));
    if (!in_array($status, ep_application_statuses(), true)) {
        $status = '';
    }
    $date = static function (string $value): string {
        $value = trim($value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    };

    return [
        'status' => $status,
        'job_id' => max(0, (int) ($query['job_id'] ?? 0)),
        // 'general' isolates spontaneous CVs, which have no job_id at all.
        'general' => isset($query['general']) && $query['general'] !== '' ? '1' : '',
        'q' => mb_substr(trim((string) ($query['q'] ?? '')), 0, 100),
        'from' => $date((string) ($query['from'] ?? '')),
        'to' => $date((string) ($query['to'] ?? '')),
    ];
}

/**
 * Applications matching the given filters, newest first.
 *
 * @param array{status: string, job_id: int, general: string, q: string, from: string, to: string} $filters
 * @return array<int, array<string, mixed>>
 */
function cms_get_applications(array $filters, ?int $limit = null): array
{
    global $m;

    $where = [];
    $params = [];

    if ($filters['status'] !== '') {
        $where[] = 'a.status = ?';
        $params[] = $filters['status'];
    }
    if ($filters['general'] === '1') {
        $where[] = 'a.job_id IS NULL';
    } elseif ($filters['job_id'] > 0) {
        $where[] = 'a.job_id = ?';
        $params[] = $filters['job_id'];
    }
    if ($filters['from'] !== '') {
        $where[] = 'a.applied_at >= ?';
        $params[] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        $where[] = 'a.applied_at <= ?';
        $params[] = $filters['to'] . ' 23:59:59';
    }
    if ($filters['q'] !== '') {
        $where[] = '(a.full_name LIKE ? OR a.email LIKE ? OR a.whatsapp LIKE ? OR a.city LIKE ? OR a.job_title_snapshot LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $sql = 'SELECT a.*, j.slug AS job_slug, j.title AS job_current_title
            FROM ep_job_applications a
            LEFT JOIN ep_jobs j ON j.id = a.job_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.applied_at DESC, a.id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    return $m->rawQuery($sql, $params ?: null) ?: [];
}

/**
 * Application counts per status across ALL applications (not the filtered
 * set), so the summary tiles stay a stable overview while filtering.
 *
 * @return array<string, int>
 */
function cms_application_status_counts(): array
{
    global $m;
    $counts = array_fill_keys(ep_application_statuses(), 0);
    foreach ($m->rawQuery('SELECT status, COUNT(*) AS c FROM ep_job_applications GROUP BY status') ?: [] as $row) {
        $status = (string) $row['status'];
        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int) $row['c'];
        }
    }
    return $counts;
}

/** Bootstrap badge class for an application status. */
function cms_application_status_badge(string $status): string
{
    static $map = [
        'New' => 'bg-primary',
        'Shortlisted' => 'bg-info text-dark',
        'Interviewed' => 'bg-warning text-dark',
        'Rejected' => 'bg-secondary',
        'Hired' => 'bg-success',
    ];
    return $map[$status] ?? 'bg-light text-dark';
}

/** wa.me click-to-chat URL for an application, or '' when there is no usable number. */
function cms_application_whatsapp_url(array $application, string $message = ''): string
{
    $digits = (string) preg_replace('/\D+/', '', (string) ($application['whatsapp'] ?? ''));
    if (strlen($digits) < 9) {
        return '';
    }
    $url = 'https://wa.me/' . $digits;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/** Default WhatsApp opener for an applicant, using the role they applied for. */
function cms_application_whatsapp_message(array $application): string
{
    $name = trim((string) ($application['full_name'] ?? ''));
    $role = trim((string) ($application['job_title_snapshot'] ?? ''));
    $siteName = ep_setting('site_name', 'EduPortal');
    $firstName = $name !== '' ? explode(' ', $name)[0] : 'there';
    if ($role === '' || $role === 'General Application') {
        return sprintf('Hello %s, this is %s HR regarding the CV you sent us.', $firstName, $siteName);
    }
    return sprintf('Hello %s, this is %s HR regarding your application for the %s role.', $firstName, $siteName, $role);
}

/** Human-readable file size for the résumé column. */
function cms_format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '—';
    }
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

/** Query string that reproduces the current filter set (for links and export). */
function cms_application_filter_query(array $filters, array $overrides = []): string
{
    $params = array_filter([
        'status' => $filters['status'],
        'job_id' => $filters['job_id'] > 0 ? (string) $filters['job_id'] : '',
        'general' => $filters['general'],
        'q' => $filters['q'],
        'from' => $filters['from'],
        'to' => $filters['to'],
    ], static fn(string $v): bool => $v !== '');

    foreach ($overrides as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    return $params ? '?' . http_build_query($params) : '';
}
