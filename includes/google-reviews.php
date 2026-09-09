<?php
/**
 * Google Business Profile reviews integration.
 *
 * This file is the ONLY place that ever calls Google's API. It is invoked
 * by the cron script (scripts/sync-google-reviews.php) and by the admin
 * panel's manual "Sync now" button — never by a public-facing page or by
 * a normal site visitor.
 *
 * Uses the Google Business Profile API (Business Information / Account
 * Management v4 "reviews" endpoints), authenticated via OAuth 2.0
 * (authorization code + refresh token), NOT a simple API key — this API
 * requires the verified business owner's consent and returns first-party
 * data for their own location, which is what makes local caching and
 * cron-based refresh policy-compliant (unlike the general-purpose Places
 * API, which restricts long-term storage of review content).
 *
 * The Places API key (GOOGLE_PLACES_API_KEY) used for the admin's Place ID
 * lookup is a real Google credential and is loaded from .env — it must
 * never be written to the database, git, or any page/response. See
 * includes/env.php. GOOGLE_PLACE_ID is not a secret (Google's own policy
 * exempts it from caching restrictions specifically because it's meant to
 * be public — it's embedded directly in the "write a review" / "view on
 * Maps" links on the public reviews page), so it's read from .env first
 * but falls back to the admin-editable ep_site_settings value for sites
 * without direct server file access.
 */

require_once __DIR__ . '/env.php';

const EP_GOOGLE_OAUTH_SCOPE = 'https://www.googleapis.com/auth/business.manage';
const EP_GOOGLE_OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const EP_GOOGLE_OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const EP_GOOGLE_ACCOUNTS_API = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
const EP_GOOGLE_LOCATIONS_API_BASE = 'https://mybusinessbusinessinformation.googleapis.com/v1';
const EP_GOOGLE_REVIEWS_API_BASE = 'https://mybusiness.googleapis.com/v4';

const EP_GOOGLE_STAR_MAP = [
    'ONE' => 1,
    'TWO' => 2,
    'THREE' => 3,
    'FOUR' => 4,
    'FIVE' => 5,
];

// Places API (New) — used only for a one-time, admin-triggered lookup of the
// business's real Google Place ID (never invented/guessed) and for building
// static "write a review" / "view on Google" links. Never used to fetch or
// cache review content — see the file header and STEP 0's policy findings
// (Places API prohibits storing reviews/ratings; only the place ID itself is
// exempt from its caching restrictions, which is exactly the field this uses).
const EP_GOOGLE_PLACES_SEARCH_URL = 'https://places.googleapis.com/v1/places:searchText';
const EP_GOOGLE_PLACE_DETAILS_URL = 'https://places.googleapis.com/v1/places/';

// Current Place Details (New) field mask (verified against
// developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places,
// not an older/legacy Place Details field list) — requests exactly the
// fields ep_google_place_live_preview() below needs and nothing else:
// the place's aggregate rating/count, and its reviews (each review object
// includes rating, text, authorAttribution, publishTime and its own
// googleMapsUri as part of that one field — Places API doesn't support
// billing a subset of a review's own sub-fields separately). Requesting
// "reviews" always bills at the Enterprise + Atmosphere SKU regardless of
// what else is in the mask, so nothing broader than this is requested.
const EP_GOOGLE_PLACE_DETAILS_FIELD_MASK = 'rating,userRatingCount,reviews';

function ep_google_oauth_redirect_uri(): string
{
    return rtrim(ADMIN, '/') . '/google-reviews.php?oauth_callback=1';
}

function ep_google_oauth_authorize_url(string $state): string
{
    $params = [
        'client_id' => ep_setting('google_oauth_client_id', ''),
        'redirect_uri' => ep_google_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope' => EP_GOOGLE_OAUTH_SCOPE,
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $state,
    ];
    return EP_GOOGLE_OAUTH_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Strips any secret this project holds (Places API key, OAuth client
 * secret/tokens) out of a string before it's logged or stored anywhere —
 * belt-and-suspenders on top of Google's own error responses not normally
 * echoing back the key, in case that ever changes or a proxy/CDN error
 * page ever includes the request URL.
 */
function ep_google_redact_secrets(string $text): string
{
    $secrets = array_filter([
        ep_env('GOOGLE_PLACES_API_KEY'),
        ep_setting('google_oauth_client_secret', ''),
        ep_setting('google_oauth_access_token', ''),
        ep_setting('google_oauth_refresh_token', ''),
    ], static fn(string $s): bool => $s !== '');

    foreach ($secrets as $secret) {
        $text = str_replace($secret, '[REDACTED]', $text);
    }
    // Catch-all for anything shaped like a Google API key, even one not
    // currently configured (e.g. a stale key still referenced by an old
    // error message).
    return preg_replace('/AIza[0-9A-Za-z_-]{35}/', '[REDACTED]', $text);
}

/**
 * Categorizes a failed HTTP call so ep_google_sync_reviews() can log and
 * report distinctly for: invalid/misconfigured API key, quota exhaustion,
 * vs. a generic API error — required so "log the issue" is actually
 * actionable rather than one undifferentiated error string.
 */
function ep_google_classify_error(int $httpCode, string $errorMessage): string
{
    $lower = strtolower($errorMessage);
    if ($httpCode === 429 || str_contains($lower, 'quota') || str_contains($lower, 'resource_exhausted') || str_contains($lower, 'rate limit')) {
        return 'quota_exceeded';
    }
    if ($httpCode === 401 || str_contains($lower, 'api key not valid') || str_contains($lower, 'api_key_invalid') || str_contains($lower, 'invalid_grant') || str_contains($lower, 'invalid_client')) {
        return 'invalid_credentials';
    }
    if ($httpCode === 403) {
        return 'permission_denied';
    }
    if ($httpCode === 0) {
        return 'network_failure';
    }
    return 'api_error';
}

/** Low-level HTTP helper (cURL) — used for both OAuth token calls and API calls. */
function ep_google_http_request(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        // Covers both network failure and timeout — cURL reports a
        // CURLE_OPERATION_TIMEDOUT the same way as any other transport
        // failure, both surfaced here with code=0.
        return ['ok' => false, 'code' => 0, 'error' => ep_google_redact_secrets('cURL error: ' . $curlError), 'data' => null];
    }

    // Google's APIs always return JSON here; a 2xx with an unparseable body
    // (or a body that isn't a JSON object) is treated as an error rather
    // than handed to callers as if it were valid response data.
    $decoded = json_decode($response, true);
    $validJson = json_last_error() === JSON_ERROR_NONE && is_array($decoded);

    if ($httpCode < 200 || $httpCode >= 300) {
        $errMsg = ($validJson ? ($decoded['error']['message'] ?? null) : null) ?? ('HTTP ' . $httpCode);
        return ['ok' => false, 'code' => $httpCode, 'error' => ep_google_redact_secrets($errMsg), 'data' => $validJson ? $decoded : null];
    }
    if (!$validJson) {
        return ['ok' => false, 'code' => $httpCode, 'error' => 'Malformed response from Google (invalid JSON).', 'data' => null];
    }
    return ['ok' => true, 'code' => $httpCode, 'error' => null, 'data' => $decoded];
}

/** Exchange an OAuth authorization code for tokens. Called once, from the OAuth callback. */
function ep_google_oauth_exchange_code(string $code): array
{
    $body = http_build_query([
        'code' => $code,
        'client_id' => ep_setting('google_oauth_client_id', ''),
        'client_secret' => ep_setting('google_oauth_client_secret', ''),
        'redirect_uri' => ep_google_oauth_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);
    return ep_google_http_request('POST', EP_GOOGLE_OAUTH_TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], $body);
}

/**
 * Return a valid access token, refreshing it first if expired/near-expiry.
 * This is the only function that mutates the stored access token.
 */
function ep_google_get_access_token(): ?string
{
    global $m;

    $accessToken = ep_setting('google_oauth_access_token', '');
    $expiresAt = ep_setting('google_oauth_token_expires_at', '');
    $refreshToken = ep_setting('google_oauth_refresh_token', '');

    if ($refreshToken === '') {
        return null; // not connected yet
    }

    $stillValid = $accessToken !== '' && $expiresAt !== '' && strtotime($expiresAt) > (time() + 60);
    if ($stillValid) {
        return $accessToken;
    }

    $body = http_build_query([
        'client_id' => ep_setting('google_oauth_client_id', ''),
        'client_secret' => ep_setting('google_oauth_client_secret', ''),
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]);
    $result = ep_google_http_request('POST', EP_GOOGLE_OAUTH_TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], $body);

    if (!$result['ok'] || empty($result['data']['access_token'])) {
        return null;
    }

    $newToken = $result['data']['access_token'];
    $expiresIn = (int) ($result['data']['expires_in'] ?? 3600);
    ep_save_setting('google_oauth_access_token', $newToken);
    ep_save_setting('google_oauth_token_expires_at', date('Y-m-d H:i:s', time() + $expiresIn));

    return $newToken;
}

/** Small helper: update (or insert) a single setting row, bypassing the admin form. */
function ep_save_setting(string $key, string $value): void
{
    global $m;
    $m->where('setting_key', $key);
    $exists = $m->getOne('ep_site_settings');
    if ($exists) {
        $m->where('setting_key', $key);
        $m->update('ep_site_settings', ['setting_value' => $value]);
    } else {
        $m->insert('ep_site_settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => 'google_reviews']);
    }
    // Keep ep_setting()'s request-scoped cache in sync so a read immediately
    // after a save in the same request sees the new value, not a stale one.
    if (is_array($GLOBALS['_ep_settings_cache'] ?? null)) {
        $GLOBALS['_ep_settings_cache'][$key] = $value;
    }
}

/** List the Google Business Profile accounts the connected user has access to. */
function ep_google_list_accounts(): array
{
    $token = ep_google_get_access_token();
    if ($token === null) {
        return ['ok' => false, 'error' => 'Not connected to Google.', 'accounts' => []];
    }
    $result = ep_google_http_request('GET', EP_GOOGLE_ACCOUNTS_API, ['Authorization: Bearer ' . $token]);
    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['error'], 'accounts' => []];
    }
    $accounts = $result['data']['accounts'] ?? [];
    return ['ok' => true, 'error' => null, 'accounts' => is_array($accounts) ? $accounts : []];
}

/** List the locations (business listings) under a given account. */
function ep_google_list_locations(string $accountName): array
{
    $token = ep_google_get_access_token();
    if ($token === null) {
        return ['ok' => false, 'error' => 'Not connected to Google.', 'locations' => []];
    }
    $url = EP_GOOGLE_LOCATIONS_API_BASE . '/' . $accountName . '/locations?readMask=name,title';
    $result = ep_google_http_request('GET', $url, ['Authorization: Bearer ' . $token]);
    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['error'], 'locations' => []];
    }
    $locations = $result['data']['locations'] ?? [];
    return ['ok' => true, 'error' => null, 'locations' => is_array($locations) ? $locations : []];
}

/**
 * Google's Business Profile APIs policies (developers.google.com/my-business/content/policies,
 * "Content storage" section, verified current as of 2026-08-18) prohibit
 * pre-fetching, caching, indexing, or storing API content — including
 * reviews — for more than 30 calendar days. There is no longer-retention
 * carve-out for reviews specifically.
 *
 * This is enforced independently of whether a live sync succeeds: any
 * cached review whose content hasn't been reconfirmed by Google within the
 * last 30 days is purged. In practice, with the cron sync running far more
 * often than every 30 days, this never fires under normal operation — it
 * only kicks in during an extended Google outage, at which point continuing
 * to display month-old unrefreshed content would violate the policy, so the
 * review is removed from public display rather than kept indefinitely.
 */
const EP_GOOGLE_REVIEWS_MAX_CACHE_DAYS = 30;

// Google caps reviews.list at pageSize=50 per request (v4 reference), so
// all reviews beyond the first 50 are retrieved by following nextPageToken.
const EP_GOOGLE_REVIEWS_PAGE_SIZE = 50;
// Safety bound only — 100 pages is 5,000 reviews, far beyond any realistic
// single location. Exists purely so a malformed/looping token sequence
// can't spin a cron run forever; it is not an intended display limit.
const EP_GOOGLE_REVIEWS_MAX_PAGES = 100;

/**
 * Remembers an admin's hide/show decision for a review, keyed by review
 * ID, in ep_google_review_moderation.
 *
 * Why this exists: the 30-day retention purge deletes cached reviews
 * regardless of visibility. If syncing is broken for over a month, a
 * review the admin deliberately hid gets purged and then re-inserted by
 * the next successful sync as VISIBLE — silently undoing moderation on
 * exactly the reviews someone chose to suppress. This table survives the
 * purge so that decision is reapplied on re-insert.
 *
 * Policy note: this stores ONLY the opaque Google review identifier plus
 * our own boolean — never author name, text, rating, or any other review
 * content. It is a record of our moderation decision, not a cache of
 * Google's content, so it is not what the 30-day content-storage cap
 * governs. The review content itself is still purged on schedule.
 */
function ep_google_remember_moderation(string $googleReviewId, int $isHidden): void
{
    global $m;
    if ($googleReviewId === '') {
        return;
    }
    $m->where('google_review_id', $googleReviewId);
    $existing = $m->getOne('ep_google_review_moderation');
    if ($existing) {
        $m->where('google_review_id', $googleReviewId);
        $m->update('ep_google_review_moderation', ['is_hidden' => $isHidden]);
    } else {
        $m->insert('ep_google_review_moderation', [
            'google_review_id' => $googleReviewId,
            'is_hidden' => $isHidden,
        ]);
    }
}

/** Recalls a remembered hide/show decision; defaults to visible (0). */
function ep_google_recall_moderation(string $googleReviewId): int
{
    global $m;
    if ($googleReviewId === '') {
        return 0;
    }
    $m->where('google_review_id', $googleReviewId);
    $row = $m->getOne('ep_google_review_moderation');
    return $row ? (int) $row['is_hidden'] : 0;
}

/**
 * "Now", on the database's clock.
 *
 * Every table in this project fills created_at/updated_at with MySQL's
 * DEFAULT CURRENT_TIMESTAMP, so MySQL's timezone is the project's
 * established convention. PHP's date.timezone is configured separately and
 * does not necessarily match it — on this dev box PHP is Europe/Berlin
 * while MySQL follows the OS at UTC+5, a three-hour gap. Writing
 * timestamps with PHP's date() therefore produced rows whose fetched_at
 * disagreed with their own created_at by three hours.
 *
 * Taking the value from MySQL keeps every datetime this file writes on the
 * same clock as the rest of the schema, whatever either timezone is set to
 * on a given server.
 */
function ep_google_db_now(): string
{
    global $m;
    $row = $m->rawQuery('SELECT NOW() AS db_now');
    return (string) ($row[0]['db_now'] ?? date('Y-m-d H:i:s'));
}

/**
 * The database's UTC offset as a DateTimeZone, so PHP can convert Google's
 * UTC timestamps onto the same clock the schema uses.
 *
 * Resolved from MySQL itself rather than hardcoded, so it stays correct
 * whatever the server is set to. Deliberately done in PHP instead of with
 * the query builder's func('FROM_UNIXTIME(?)'): this project's copy of
 * MysqliDb has a locally-added pseudo-SQL logging block in insert() that
 * string-casts every value, so passing it a func() array emits an "Array
 * to string conversion" warning — and warnings can print into a response
 * when display_errors is on.
 */
function ep_google_db_timezone(): DateTimeZone
{
    global $m;
    static $tz = null;
    if ($tz instanceof DateTimeZone) {
        return $tz;
    }
    $row = $m->rawQuery('SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP()) AS db_offset');
    $raw = (string) ($row[0]['db_offset'] ?? '');
    if (preg_match('/^(-?)(\d{2}):(\d{2}):/', $raw, $parts)) {
        $tz = new DateTimeZone(($parts[1] === '-' ? '-' : '+') . $parts[2] . ':' . $parts[3]);
    } else {
        $tz = new DateTimeZone(date_default_timezone_get());
    }
    return $tz;
}

/**
 * Validates a reviewer avatar URL before it is stored.
 *
 * Only the URL is ever kept — the image itself is never downloaded or
 * copied onto this server, which is what keeps this compliant: Google's
 * policies forbid pre-fetching, caching or storing API content beyond the
 * limited allowance, and the stored URL is purged with the rest of the
 * review row at the 30-day cap like any other cached field.
 *
 * Enforces https. Google serves these over https already, so anything
 * else is either malformed or an attempt to inject something; a plain
 * http URL would also be blocked as mixed content on the live site and
 * render as a broken image. Rejected values become null, which makes the
 * templates fall back to the initial-letter avatar.
 */
function ep_google_safe_photo_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        return null;
    }
    // The column is VARCHAR(500); a longer URL would be silently truncated
    // into something broken, so drop it and use the fallback avatar.
    return strlen($url) <= 500 ? $url : null;
}

/**
 * Converts one of Google's RFC 3339 UTC timestamps ("2026-07-01T10:00:00Z")
 * into a datetime string on the database's clock. Returns null for a
 * missing or unparseable value rather than inventing a date.
 */
function ep_google_to_db_datetime(?string $rfc3339): ?string
{
    if (empty($rfc3339)) {
        return null;
    }
    try {
        return (new DateTimeImmutable($rfc3339))
            ->setTimezone(ep_google_db_timezone())
            ->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        error_log('[EduPortal] Google Reviews: unparseable review timestamp "' . $rfc3339 . '"');
        return null;
    }
}

function ep_google_purge_expired_reviews(): int
{
    global $m;
    // Cutoff computed by MySQL so it is on the same clock as the
    // fetched_at values it is compared against.
    $cutoffRow = $m->rawQuery('SELECT DATE_SUB(NOW(), INTERVAL ' . (int) EP_GOOGLE_REVIEWS_MAX_CACHE_DAYS . ' DAY) AS cutoff');
    $cutoff = (string) ($cutoffRow[0]['cutoff'] ?? date('Y-m-d H:i:s', time() - EP_GOOGLE_REVIEWS_MAX_CACHE_DAYS * 86400));

    $m->where('fetched_at', $cutoff, '<');
    $deleted = (int) ($m->getValue('ep_google_reviews', 'COUNT(*)') ?? 0);

    if ($deleted > 0) {
        $m->where('fetched_at', $cutoff, '<');
        $m->delete('ep_google_reviews');
    }

    $lastSyncedAt = ep_setting('google_reviews_last_synced_at', '');
    if ($lastSyncedAt !== '' && strtotime($lastSyncedAt) < strtotime($cutoff)) {
        ep_save_setting('google_rating', '');
        ep_save_setting('google_review_count', '');
    }

    return $deleted;
}

/**
 * The actual sync: fetch reviews for the configured location and upsert
 * them into ep_google_reviews. Never deletes existing rows on failure —
 * the site keeps showing the last successfully synced data if Google's
 * API is temporarily unavailable — except for the hard 30-day cache-age
 * cap required by Google's current storage policy (see
 * ep_google_purge_expired_reviews() above), which runs regardless of
 * whether this sync's live API call succeeds.
 */

/**
 * Guards google_rating / google_review_count against being overwritten by
 * a suspicious value from an otherwise-"successful" (2xx) response —
 * distinct from the request-level failure handling above, this covers the
 * narrower case where Google returns 200 OK but the field itself is
 * missing, blank, or an implausible drop to zero, which is far more likely
 * to be a transient API/serialization glitch than a real change:
 *   - rating is always 1.0–5.0 for a location with any reviews; Google
 *     omits the field entirely rather than returning 0, so an explicit 0
 *     is always treated as invalid, never written, regardless of whether
 *     a previous value exists.
 *   - review_count of exactly 0 is only accepted when there is no prior
 *     value yet (a genuinely new/never-synced location can legitimately
 *     start at zero); if a real positive count was already stored, a new
 *     0 is rejected as a likely glitch rather than silently overwriting
 *     known-good data.
 */
function ep_google_should_update_metric(string $metric, ?string $newValue, string $previousValue): bool
{
    if ($newValue === null || trim($newValue) === '') {
        return false;
    }
    if (!is_numeric($newValue)) {
        return false;
    }
    $numericValue = (float) $newValue;

    if ($metric === 'rating') {
        return $numericValue > 0;
    }
    // review_count
    if ($numericValue > 0) {
        return true;
    }
    // $numericValue === 0 here: only acceptable if nothing valid was stored before.
    return $previousValue === '' || !is_numeric($previousValue) || (float) $previousValue <= 0;
}

/**
 * Dedup key for upserting a synced review, so re-running the cron sync
 * never creates duplicate rows for a review already saved.
 *
 * Per the Business Profile API v4 Review resource (verified current,
 * developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews),
 * every review carries a `name` field — a fully-qualified, stable resource
 * name of the form "accounts/{accountId}/locations/{locationId}/reviews/{reviewId}"
 * — which is preferred here over "author name + date" as suggested in the
 * original spec: two different reviews can share both (e.g. two 5-star
 * reviews posted the same day by different people both named "Google User",
 * which Google's API returns verbatim for reviewers who haven't shared a
 * public display name — not a hypothetical edge case, it's Google's own
 * documented placeholder behavior). `name` has no such collision risk.
 *
 * `reviewId` (the bare encrypted ID, without the account/location prefix)
 * is also present on every review per the same reference and is kept here
 * as a fallback only in case `name` is ever absent from a response Google
 * didn't document. Author name + review date is used only as a last resort
 * if neither Google-issued identifier is present, and is explicitly the
 * least reliable of the three for the collision reason above.
 */
function ep_google_review_dedup_key(array $review): string
{
    if (!empty($review['name'])) {
        return $review['name'];
    }
    if (!empty($review['reviewId'])) {
        return 'reviewId:' . $review['reviewId'];
    }
    $reviewer = is_array($review['reviewer'] ?? null) ? $review['reviewer'] : [];
    $author = $reviewer['displayName'] ?? '';
    $date = $review['createTime'] ?? '';
    if ($author !== '' && $date !== '') {
        return 'authordate:' . $author . '|' . $date;
    }
    return '';
}

/**
 * Records a sync failure both to the admin-visible DB status (gated behind
 * the reviews.manage permission, never public) and to the server's PHP
 * error log (matching the project's existing error_log() convention in
 * includes/callMe.php) — so a failing unattended cron run leaves a trace
 * even if nobody opens the admin panel. The message is always redacted
 * before either destination sees it.
 */
function ep_google_log_sync_failure(string $type, string $message): void
{
    // Redact first — never let a key, token, or Authorization header value
    // reach either destination below, even indirectly via Google's own
    // error text (see ep_google_redact_secrets()'s doc comment).
    $safeMessage = ep_google_redact_secrets($message);
    ep_save_setting('google_reviews_last_sync_status', 'error');
    ep_save_setting('google_reviews_last_sync_error', $safeMessage);
    ep_save_setting('google_reviews_last_sync_error_type', $type);
    error_log(
        "[EduPortal] Google reviews fetch failed.\n" .
        "Reason: {$safeMessage} (type: {$type})"
    );
}

function ep_google_sync_reviews(): array
{
    global $m;

    $purged = ep_google_purge_expired_reviews();

    $locationId = ep_setting('google_business_location_id', '');
    if ($locationId === '') {
        $msg = 'No Google Business location selected yet.';
        ep_google_log_sync_failure('not_configured', $msg);
        return ['ok' => false, 'error' => $msg, 'synced' => 0, 'purged' => $purged];
    }

    $token = ep_google_get_access_token();
    if ($token === null) {
        $msg = 'Not connected to Google (no valid access token).';
        ep_google_log_sync_failure('not_connected', $msg);
        return ['ok' => false, 'error' => $msg, 'synced' => 0, 'purged' => $purged];
    }

    // Fetch EVERY review, not just the first page. Google caps pageSize at
    // 50 (per the v4 reviews.list reference), so a location with more than
    // 50 reviews requires following nextPageToken — otherwise /reviews
    // could only ever show the first 50, which would be an arbitrary cap
    // rather than a policy one (see STEP 25).
    // locationId is stored as "accounts/{account}/locations/{location}"
    $baseUrl = EP_GOOGLE_REVIEWS_API_BASE . '/' . $locationId . '/reviews?pageSize=' . EP_GOOGLE_REVIEWS_PAGE_SIZE;
    $reviews = [];
    $firstPage = null;
    $pageToken = '';
    $pagesFetched = 0;
    $seenTokens = [];
    $pageError = null;

    do {
        $url = $baseUrl . ($pageToken !== '' ? '&pageToken=' . rawurlencode($pageToken) : '');
        $result = ep_google_http_request('GET', $url, ['Authorization: Bearer ' . $token]);

        if (!$result['ok']) {
            // Graceful degradation: leave previously cached (still-within-30-days)
            // reviews untouched — covers every failure mode here (API error,
            // timeout, network failure, invalid key, quota exceeded — cURL and
            // ep_google_http_request() both report timeouts/network failures
            // the same way as any other transport failure, code=0).
            if ($pagesFetched === 0) {
                // First page failed: nothing was fetched at all, so bail
                // out entirely without touching any cached row.
                $errorType = ep_google_classify_error($result['code'], $result['error']);
                ep_google_log_sync_failure($errorType, $result['error']);
                return ['ok' => false, 'error' => $result['error'], 'synced' => 0, 'purged' => $purged];
            }
            // A later page failed: keep the pages already retrieved (upserts
            // are non-destructive, so partial data is strictly better than
            // discarding it) but remember the error so this run is reported
            // as failed rather than silently looking complete.
            $pageError = $result['error'];
            break;
        }

        $pageReviews = $result['data']['reviews'] ?? [];
        if (!is_array($pageReviews)) {
            $msg = 'Malformed response from Google: "reviews" was not a list.';
            ep_google_log_sync_failure('malformed_response', $msg);
            return ['ok' => false, 'error' => $msg, 'synced' => 0, 'purged' => $purged];
        }

        if ($firstPage === null) {
            // averageRating / totalReviewCount are location-level values;
            // take them from the first page's response.
            $firstPage = $result;
        }

        $reviews = array_merge($reviews, $pageReviews);
        $pagesFetched++;

        $pageToken = (string) ($result['data']['nextPageToken'] ?? '');
        if ($pageToken !== '' && isset($seenTokens[$pageToken])) {
            // Google repeated a page token — stop rather than loop forever.
            error_log('[EduPortal] Google Reviews sync: repeated nextPageToken, stopping pagination.');
            break;
        }
        if ($pageToken !== '') {
            $seenTokens[$pageToken] = true;
        }
    } while ($pageToken !== '' && $pagesFetched < EP_GOOGLE_REVIEWS_MAX_PAGES);

    if ($pageToken !== '' && $pagesFetched >= EP_GOOGLE_REVIEWS_MAX_PAGES) {
        error_log('[EduPortal] Google Reviews sync: stopped at the ' . EP_GOOGLE_REVIEWS_MAX_PAGES
            . '-page safety limit (' . count($reviews) . ' reviews fetched); more may remain.');
    }

    $result = $firstPage;

    // Empty is a valid response (a location can genuinely have zero
    // reviews) — not an error, so existing rows are left alone exactly as
    // in every other branch, but it's still logged as informational so an
    // unexpected drop to zero doesn't go unnoticed if it actually reflects
    // a misconfigured location ID rather than a real empty review set.
    if (count($reviews) === 0) {
        error_log('[EduPortal] Google Reviews sync: Google returned 0 reviews for this location.');
    }

    $synced = 0;
    $newCount = 0;
    $now = ep_google_db_now();

    foreach ($reviews as $review) {
        if (!is_array($review)) {
            continue; // skip malformed entries rather than guess their shape
        }
        $starRating = EP_GOOGLE_STAR_MAP[$review['starRating'] ?? ''] ?? null;
        if ($starRating === null) {
            continue; // skip malformed entries rather than guess a rating
        }
        $reviewer = is_array($review['reviewer'] ?? null) ? $review['reviewer'] : [];
        $data = [
            'google_review_id' => ep_google_review_dedup_key($review),
            'author_name' => $reviewer['displayName'] ?? 'Google User',
            'author_photo' => ep_google_safe_photo_url($reviewer['profilePhotoUrl'] ?? null),
            'rating' => $starRating,
            'review_text' => $review['comment'] ?? null,
            // Google returns createTime as RFC 3339 UTC ("…Z"); this stores
            // it on the database's clock, matching the project's
            // DEFAULT CURRENT_TIMESTAMP convention. Formatting with PHP's
            // date() instead would store it in whatever timezone the PHP
            // process happens to use, which can shift the displayed date by
            // a whole day.
            'review_date' => ep_google_to_db_datetime($review['createTime'] ?? null),
            'fetched_at' => $now,
        ];
        if ($data['google_review_id'] === '') {
            continue;
        }

        $m->where('google_review_id', $data['google_review_id']);
        $existing = $m->getOne('ep_google_reviews');
        if ($existing) {
            $m->where('google_review_id', $data['google_review_id']);
            $m->update('ep_google_reviews', $data);
        } else {
            // Reapply any remembered hide decision so a review the admin
            // hid before a purge doesn't silently come back visible.
            $data['is_hidden'] = ep_google_recall_moderation($data['google_review_id']);
            $m->insert('ep_google_reviews', $data);
            $newCount++;
        }
        $synced++;
    }

    // Google's own aggregate rating/count for the location — saved verbatim,
    // never computed or altered locally, per "do NOT invent values". Each
    // write is additionally guarded by ep_google_should_update_metric() so
    // a 200 OK response with a missing/blank/suspiciously-zeroed field
    // never overwrites a previously-good value.
    $newRating = isset($result['data']['averageRating']) && is_scalar($result['data']['averageRating'])
        ? (string) $result['data']['averageRating'] : null;
    $newReviewCount = isset($result['data']['totalReviewCount']) && is_scalar($result['data']['totalReviewCount'])
        ? (string) $result['data']['totalReviewCount'] : null;

    if (ep_google_should_update_metric('rating', $newRating, ep_setting('google_rating', ''))) {
        ep_save_setting('google_rating', $newRating);
    } elseif ($newRating !== null) {
        error_log('[EduPortal] Google Reviews sync: ignored suspicious rating value from Google ("' . $newRating . '"), keeping previous value.');
    }
    if (ep_google_should_update_metric('review_count', $newReviewCount, ep_setting('google_review_count', ''))) {
        ep_save_setting('google_review_count', $newReviewCount);
    } elseif ($newReviewCount !== null) {
        error_log('[EduPortal] Google Reviews sync: ignored suspicious review count from Google ("' . $newReviewCount . '"), keeping previous value.');
    }
    // A page failing partway through pagination is reported as a failure
    // even though the pages we did retrieve were saved — otherwise a run
    // that silently fetched only part of the reviews would look clean.
    if ($pageError !== null) {
        $msg = 'Partial sync: saved ' . $synced . ' review(s) from ' . $pagesFetched
            . ' page(s), then a later page failed — ' . $pageError;
        ep_google_log_sync_failure('partial_sync', $msg);
        return ['ok' => false, 'error' => $msg, 'synced' => $synced, 'new' => $newCount, 'purged' => $purged];
    }

    ep_save_setting('google_reviews_last_synced_at', $now);
    ep_save_setting('google_reviews_last_sync_status', 'success');
    ep_save_setting('google_reviews_last_sync_error', '');
    ep_save_setting('google_reviews_last_sync_error_type', '');

    // Success log — counts and the resulting public rating/count values
    // only, never anything credential-shaped (no key, token, or header
    // ever passes through this function).
    $currentRating = ep_setting('google_rating', '');
    $currentReviewCount = ep_setting('google_review_count', '');
    error_log(
        "[EduPortal] Google reviews fetch completed successfully.\n" .
        "Reviews processed: {$synced}\n" .
        "New reviews: {$newCount}\n" .
        'Updated rating: ' . ($currentRating !== '' ? $currentRating : '(none yet)') . "\n" .
        'Review count: ' . ($currentReviewCount !== '' ? $currentReviewCount : '(none yet)')
    );

    return ['ok' => true, 'error' => null, 'synced' => $synced, 'new' => $newCount, 'purged' => $purged];
}

/**
 * Google's own aggregate rating/count for the location, as of the last
 * successful sync — reads the local cache only, never calls Google.
 * Backed by the google_rating / google_review_count settings (existing
 * ep_site_settings architecture from Task 4 — no separate table), written
 * only by ep_google_sync_reviews() with Google's own values, verbatim.
 */
function ep_google_reviews_summary(): array
{
    return [
        'average_rating' => ep_setting('google_rating', ''),
        'total_count' => ep_setting('google_review_count', ''),
    ];
}

/**
 * Wraps a review-table read so a database problem degrades to "no reviews"
 * instead of taking the page down.
 *
 * Without this, an unavailable database — or, far more likely, the
 * ep_google_reviews table simply not existing because
 * scripts/add-google-reviews.sql was never run on production — throws an
 * uncaught mysqli_sql_exception. With display_errors on (the default on
 * plenty of shared hosts) that renders the database name, absolute server
 * paths, the SQL statement and a full stack trace straight into the public
 * page, while still returning HTTP 200.
 *
 * Callers get an empty array, which every template already handles by
 * showing its graceful "no reviews yet" state. The real cause is written
 * to the error log, never to the response.
 */
function ep_google_reviews_safe_read(callable $read): array
{
    global $m;
    try {
        return $read() ?: [];
    } catch (Throwable $e) {
        error_log('[EduPortal] Google Reviews database read failed: ' . $e->getMessage());
        // The query builder keeps its pending where()/orderBy() state when a
        // query throws (its reset() runs only on success and is protected),
        // so flush it here — otherwise a leftover clause could silently
        // filter an unrelated query later in the same request.
        try {
            $m->rawQuery('SELECT 1');
        } catch (Throwable $ignored) {
            // Database genuinely unreachable; nothing to flush.
        }
        return [];
    }
}

/** Public-facing read: only ever touches the local database, never Google. */
function ep_get_google_reviews(bool $includeHidden = false): array
{
    global $m;
    return ep_google_reviews_safe_read(static function () use ($m, $includeHidden) {
        if (!$includeHidden) {
            $m->where('is_hidden', 0);
        }
        $m->orderBy('review_date', 'DESC');
        return $m->get('ep_google_reviews');
    });
}

/**
 * Homepage review selection: WHERE is_hidden = 0 AND rating IN (4, 5),
 * ordered newest-first (deterministic — no RAND()/shuffle, so the same
 * reviews show on every request/visitor until the underlying data
 * actually changes, per the "don't randomly rotate" requirement).
 * The full /reviews.php page is intentionally unfiltered and uses
 * ep_get_google_reviews() instead — this selection is homepage-only.
 */
function ep_get_homepage_google_reviews(int $limit = 6): array
{
    global $m;
    return ep_google_reviews_safe_read(static function () use ($m, $limit) {
        $m->where('is_hidden', 0);
        $m->where('rating', [4, 5], 'IN');
        $m->orderBy('review_date', 'DESC');
        return $m->get('ep_google_reviews', $limit);
    });
}

/**
 * Admin-triggered only (never called for a public visitor): looks up
 * candidate places by free-text name/address so the admin can pick the
 * correct real Place ID themselves rather than anyone guessing one.
 * Requests only id/displayName/formattedAddress — the cheapest field mask,
 * and deliberately excludes reviews/ratings since this lookup has nothing
 * to do with the cached-review pipeline.
 */
function ep_google_places_search(string $query): array
{
    $apiKey = ep_env('GOOGLE_PLACES_API_KEY');
    if ($apiKey === '' || trim($query) === '') {
        return ['ok' => false, 'error' => 'Missing API key or search text.', 'places' => []];
    }

    $result = ep_google_http_request(
        'POST',
        EP_GOOGLE_PLACES_SEARCH_URL,
        [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress',
        ],
        json_encode(['textQuery' => $query])
    );

    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['error'], 'places' => []];
    }
    $places = $result['data']['places'] ?? [];
    return ['ok' => true, 'error' => null, 'places' => is_array($places) ? $places : []];
}

/**
 * Admin-triggered only, on demand (never automatic, never called for a
 * public visitor, never on any page load) — fetches the place's current
 * rating/review count/reviews LIVE from Places API (New) so the admin can
 * sanity-check what Google is showing right now against the cached
 * Business Profile data on /reviews.php. Nothing this returns is written
 * to the database: per STEP 0's policy findings, Places API review/rating
 * content has no caching allowance, so persisting it would violate Google's
 * terms — this function exists specifically so that data never touches
 * ep_google_reviews or any settings row, only the current HTTP response.
 *
 * Each call bills at the Enterprise + Atmosphere SKU (~$40/1000 requests)
 * since "reviews" is requested — that's why this is a manual button, not
 * something that runs on a schedule or on every admin page load.
 */
function ep_google_place_live_preview(string $placeId): array
{
    $apiKey = ep_env('GOOGLE_PLACES_API_KEY');
    if ($apiKey === '' || trim($placeId) === '') {
        return ['ok' => false, 'error' => 'Missing API key or Place ID.', 'rating' => null, 'user_rating_count' => null, 'reviews' => []];
    }

    $result = ep_google_http_request(
        'GET',
        EP_GOOGLE_PLACE_DETAILS_URL . rawurlencode($placeId),
        [
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . EP_GOOGLE_PLACE_DETAILS_FIELD_MASK,
        ]
    );

    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['error'], 'rating' => null, 'user_rating_count' => null, 'reviews' => []];
    }

    $data = $result['data'];
    $rawReviews = is_array($data['reviews'] ?? null) ? $data['reviews'] : [];
    $reviews = [];
    foreach ($rawReviews as $review) {
        if (!is_array($review)) {
            continue;
        }
        $author = is_array($review['authorAttribution'] ?? null) ? $review['authorAttribution'] : [];
        $reviews[] = [
            'author_name' => $author['displayName'] ?? 'Google User',
            'author_photo' => ep_google_safe_photo_url($author['photoUri'] ?? null),
            'author_profile_url' => $author['uri'] ?? null,
            'rating' => (int) ($review['rating'] ?? 0),
            'review_text' => $review['text']['text'] ?? ($review['originalText']['text'] ?? null),
            'review_date' => !empty($review['publishTime']) ? date('Y-m-d H:i:s', strtotime($review['publishTime'])) : null,
            'relative_time' => $review['relativePublishTimeDescription'] ?? null,
            'google_maps_review_url' => $review['googleMapsUri'] ?? null,
        ];
    }

    return [
        'ok' => true,
        'error' => null,
        'rating' => is_scalar($data['rating'] ?? null) ? $data['rating'] : null,
        'user_rating_count' => is_scalar($data['userRatingCount'] ?? null) ? $data['userRatingCount'] : null,
        'reviews' => $reviews,
    ];
}

/**
 * The Place ID isn't a secret, so .env is preferred (production convention)
 * but the admin-panel-saved DB value is used as a fallback — see the file
 * header for why this differs from the API key's env-only treatment.
 */
function ep_google_place_id(): string
{
    $fromEnv = ep_env('GOOGLE_PLACE_ID');
    return $fromEnv !== '' ? $fromEnv : ep_setting('google_place_id', '');
}

/** Static Google URL — no API call — for a "leave us a review" call to action. */
function ep_google_write_review_url(string $placeId): string
{
    return 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId);
}

/**
 * Static Google URL — no API call — linking to the business's own Google
 * Maps profile.
 *
 * Uses Google's documented Maps URLs format
 * (developers.google.com/maps/documentation/urls/get-started): `api=1` is
 * required on every request, `query_place_id` pins the exact
 * establishment, and `query` is the human-readable fallback Google uses
 * only if the place ID can't be resolved. The older
 * `/maps/place/?q=place_id:` pattern works but is not documented, so the
 * supported format is used instead.
 *
 * The place ID itself is never guessed — it comes from Google's own
 * Places lookup in the admin panel (see STEP 2), so this always resolves
 * to the real verified profile or renders no link at all.
 */
function ep_google_maps_place_url(string $placeId, string $businessName = ''): string
{
    if ($businessName === '') {
        $businessName = (string) ep_setting('site_name', 'EduPortal');
    }
    return 'https://www.google.com/maps/search/?api=1'
        . '&query=' . rawurlencode($businessName)
        . '&query_place_id=' . rawurlencode($placeId);
}

/**
 * Renders a 0–5 star string for display.
 *
 * Clamps defensively: str_repeat() throws a ValueError on a negative
 * count under PHP 8, so a rating outside 0–5 would fatal-error and blank
 * the entire page (violating STEP 10's "never blank the website"). Google
 * only ever sends 1–5, and ep_google_should_update_metric() rejects
 * non-positive ratings, but neither guarantees an upper bound — a
 * malformed response or a hand-edited setting row could still produce
 * one, and a rating display is never worth taking a page down for.
 */
function ep_google_star_display(float $rating): string
{
    $filled = max(0, min(5, (int) round($rating)));
    return str_repeat('★', $filled) . str_repeat('☆', 5 - $filled);
}

/**
 * Display formatting only — never alters what's stored. Formats the real
 * review_date pulled from Google's own createTime, matching the one
 * date-display format convention already used elsewhere in this project
 * (cms/index.php's `date('M j, Y')`). Returns '' if unavailable so
 * templates can skip rendering it rather than showing a blank/wrong date.
 */
function ep_google_format_review_date(?string $reviewDate): string
{
    if (empty($reviewDate)) {
        return '';
    }
    $timestamp = strtotime($reviewDate);
    return $timestamp !== false ? date('M j, Y', $timestamp) : '';
}
