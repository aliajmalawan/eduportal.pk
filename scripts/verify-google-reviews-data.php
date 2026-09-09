<?php
/**
 * Pre-deployment check: confirms the Google reviews on this server are real
 * data synced from Google, not leftover development or demo content.
 *
 * Run it on production after deploying, and again after the first sync:
 *   php /home/USER/public_html/eduportal/scripts/verify-google-reviews-data.php
 *
 * Exit code 0 = clean, 1 = suspect rows found (so it can gate a deploy).
 *
 * How it tells real from fake: every review Google returns carries a
 * resource name of the form
 *   accounts/{accountId}/locations/{locationId}/reviews/{reviewId}
 * which the sync stores verbatim in google_review_id. Rows written by hand
 * — test fixtures, demo seeds, anything typed into phpMyAdmin — almost
 * never match that shape, so a format check catches them reliably without
 * needing to judge the review text itself.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI-only.\n";
    exit(1);
}

require_once __DIR__ . '/../includes/callMe.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/google-reviews.php';
global $m;

$problems = [];
$notes = [];

$total = (int) $m->getValue('ep_google_reviews', 'COUNT(*)');
$lastSync = ep_setting('google_reviews_last_synced_at', '');
$lastStatus = ep_setting('google_reviews_last_sync_status', '');

echo "Google reviews data check\n";
echo str_repeat('-', 52) . "\n";
echo "Cached reviews:   {$total}\n";
echo "Last sync:        " . ($lastSync !== '' ? $lastSync : 'never') . "\n";
echo "Last sync status: " . ($lastStatus !== '' ? $lastStatus : 'n/a') . "\n";
echo "Rating / count:   " . (ep_setting('google_rating', '') ?: '—')
    . ' / ' . (ep_setting('google_review_count', '') ?: '—') . "\n\n";

// Rows whose identifier is not in Google's resource-name format.
$rows = $m->get('ep_google_reviews', null, 'id, google_review_id, author_name, review_date');
$suspect = [];
foreach ($rows as $r) {
    if (!preg_match('#^accounts/[^/]+/locations/[^/]+/reviews/.+#', (string) $r['google_review_id'])) {
        $suspect[] = $r;
    }
}

if ($suspect) {
    $problems[] = count($suspect) . ' review(s) do NOT have a Google resource-name identifier '
        . '— these look hand-inserted rather than synced:';
    foreach ($suspect as $r) {
        $problems[] = sprintf('    id=%d  google_review_id="%s"  author="%s"',
            $r['id'], $r['google_review_id'], $r['author_name']);
    }
}

// Reviews present but no successful sync ever recorded.
if ($total > 0 && $lastSync === '') {
    $problems[] = "{$total} review(s) exist but no sync has ever completed — "
        . 'they cannot have come from Google.';
}

// Informational, not a failure.
if ($total === 0) {
    $notes[] = 'No reviews cached yet. That is the correct state before the first sync; '
        . 'the site shows its "reviews will appear here" fallback.';
}
if ($lastStatus === 'error') {
    $notes[] = 'Last sync failed — see ep_site_settings.google_reviews_last_sync_error '
        . 'and the PHP error log. Previously cached reviews are still displayed.';
}

foreach ($notes as $n) {
    echo "note: {$n}\n";
}

if ($problems) {
    echo "\nFAILED — possible non-real review data:\n";
    foreach ($problems as $p) {
        echo "  {$p}\n";
    }
    echo "\nRemove these rows before this site is treated as production-ready.\n";
    exit(1);
}

echo "\nOK — no fabricated or hand-inserted review data detected.\n";
exit(0);
