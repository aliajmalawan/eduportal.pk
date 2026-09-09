<?php
/**
 * Cron entry point: refreshes cached Google reviews. This — and the admin
 * panel's "Sync now" button — are the ONLY code paths that ever call
 * Google's API. Public pages (index.php, reviews.php) always read from the
 * local ep_google_reviews table, never live — see STEP 10.
 *
 * No cron/ or jobs/ directory exists anywhere in this project (verified in
 * STEP 1's architecture inspection); scripts/ is the project's existing,
 * established location for standalone CLI entry points (see the sibling
 * scripts/verify-canonical.php), so this lives there rather than
 * introducing a new top-level directory for one script.
 *
 * Review content is actually fetched via the Business Profile API (OAuth),
 * not the Places API — see STEP 0's policy findings: Places API prohibits
 * storing/caching review content, so it's unusable for this pipeline. The
 * Places API is used elsewhere in this project only for the admin's Place
 * ID lookup and live-preview tool (includes/google-reviews.php), neither
 * of which this cron script touches.
 *
 * On failure (Google API down, token revoked, quota exceeded, etc.)
 * previously cached reviews — and the last known-good google_rating /
 * google_review_count — are left untouched, so the site keeps showing the
 * last good data. See ep_google_sync_reviews() in includes/google-reviews.php
 * for the actual fetch/validate/save logic and STEP 9/10/12's failure
 * handling.
 *
 * Google's Business Profile API storage policy caps cached content at 30
 * calendar days (no per-review exception), so every run also purges any
 * review not reconfirmed by Google within that window, independent of
 * whether this run's live fetch succeeds. Run this at least daily — every
 * 6 hours recommended — so that cap is never actually reached in normal
 * operation.
 *
 * Usage (cPanel cron, e.g. every 6 hours):
 *   php /home/USER/public_html/eduportal/scripts/sync-google-reviews.php
 */

// Do NOT require a visitor to trigger this process: refuse anything that
// isn't a direct CLI invocation (covers Apache/php-fpm/php's built-in web
// server alike) — a cron job always runs as PHP_SAPI === 'cli', so this
// never blocks the actual scheduled run, only a stray web hit.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI-only.\n";
    exit(1);
}

// Load environment variables (Places API key / Place ID, if configured).
require_once __DIR__ . '/../includes/env.php';
// Load the database connection.
require_once __DIR__ . '/../includes/callMe.php';
// Load application bootstrap (settings helpers, ep_setting()/ep_save_setting(), etc.).
require_once __DIR__ . '/../includes/cms.php';
// Load the Google Reviews service — reads credentials, calls the API,
// validates the response, saves reviews, updates google_rating /
// google_review_count, all internally (see includes/google-reviews.php).
require_once __DIR__ . '/../includes/google-reviews.php';

// The structured success/failure log entry (see STEP 16 — reviews
// processed/new/rating/count on success, reason on failure, never any
// credential) is written by ep_google_sync_reviews() itself via
// error_log(), so both this cron script and the admin panel's "Sync now"
// button share one logging path instead of duplicating it here. This
// script's own stdout echo is just for cron-mail/terminal visibility.
$result = ep_google_sync_reviews();
$purgedNote = (int) ($result['purged'] ?? 0) > 0 ? " (purged {$result['purged']} review(s) past the 30-day cache limit)" : '';

if ($result['ok']) {
    echo '[' . date('Y-m-d H:i:s') . "] OK — synced {$result['synced']} review(s), {$result['new']} new.{$purgedNote}\n";
    exit(0);
}

echo '[' . date('Y-m-d H:i:s') . "] FAILED — {$result['error']}{$purgedNote}\n";
exit(1);
