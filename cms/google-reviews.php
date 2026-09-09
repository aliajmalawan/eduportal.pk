<?php
require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/google-reviews.php';
global $m;

/**
 * Server-side cooldown between manual "Refresh Now" runs.
 *
 * The page already has the project's standard admin protections — session
 * auth, cms_require_permission('reviews.manage') and cms_verify_csrf() —
 * which stop unauthorised or forged requests. This adds the missing piece:
 * stopping an *authorised* admin from accidentally hammering the button.
 *
 * It matters more than it used to, because one click is no longer one API
 * call: the sync follows Google's nextPageToken (STEP 25), so a location
 * with thousands of reviews costs many requests per refresh, against a
 * 300-req/min Business Profile quota. STEP 30's disable-on-submit is
 * client-side only and is bypassed by replaying the POST or disabling JS,
 * so the real limit has to live here on the server.
 *
 * Deliberately enforced in this admin handler rather than inside
 * ep_google_sync_reviews(), so the scheduled cron sync is never throttled.
 */
const EP_ADMIN_REFRESH_COOLDOWN_SECONDS = 60;

// --- OAuth redirect from Google lands here (GET, before auth-gated page render) ---
if (isset($_GET['oauth_callback'])) {
    cms_require_permission('reviews.manage');

    if (!empty($_GET['error'])) {
        cms_flash('error', 'Google sign-in was cancelled or failed: ' . (string) $_GET['error']);
        header('Location: google-reviews.php');
        exit;
    }

    $state = (string) ($_GET['state'] ?? '');
    $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
    unset($_SESSION['google_oauth_state']);
    if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
        cms_flash('error', 'Google sign-in failed: invalid or expired request. Please try connecting again.');
        header('Location: google-reviews.php');
        exit;
    }

    $code = (string) ($_GET['code'] ?? '');
    if ($code === '') {
        cms_flash('error', 'Google sign-in failed: no authorization code returned.');
        header('Location: google-reviews.php');
        exit;
    }

    $result = ep_google_oauth_exchange_code($code);
    if (!$result['ok'] || empty($result['data']['refresh_token'])) {
        $err = $result['error'] ?? 'Google did not return a refresh token. Try disconnecting any prior authorization for this app in your Google Account, then reconnect.';
        cms_flash('error', 'Google sign-in failed: ' . $err);
        header('Location: google-reviews.php');
        exit;
    }

    ep_save_setting('google_oauth_refresh_token', $result['data']['refresh_token']);
    ep_save_setting('google_oauth_access_token', $result['data']['access_token'] ?? '');
    $expiresIn = (int) ($result['data']['expires_in'] ?? 3600);
    ep_save_setting('google_oauth_token_expires_at', date('Y-m-d H:i:s', time() + $expiresIn));

    cms_flash('success', 'Connected to Google. Now choose your business location below.');
    header('Location: google-reviews.php');
    exit;
}

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('reviews.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: google-reviews.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'save_credentials') {
        $clientId = trim((string) ($_POST['client_id'] ?? ''));
        $clientSecret = trim((string) ($_POST['client_secret'] ?? ''));
        ep_save_setting('google_oauth_client_id', $clientId);
        if ($clientSecret !== '') {
            // Blank means "leave unchanged" — the field is rendered masked, so an
            // empty submit should never overwrite a previously saved secret.
            ep_save_setting('google_oauth_client_secret', $clientSecret);
        }
        cms_flash('success', 'Google API credentials saved.');
    } elseif ($action === 'disconnect') {
        foreach (['google_oauth_refresh_token', 'google_oauth_access_token', 'google_oauth_token_expires_at', 'google_business_location_id'] as $key) {
            ep_save_setting($key, '');
        }
        cms_flash('success', 'Disconnected from Google. Previously synced reviews are still shown on the site.');
    } elseif ($action === 'save_location') {
        $locationId = trim((string) ($_POST['location_id'] ?? ''));
        ep_save_setting('google_business_location_id', $locationId);
        cms_flash('success', 'Business location saved.');
    } elseif ($action === 'sync_now') {
        // Cooldown gate — checked before any Google call is made, so a
        // rejected attempt costs zero API quota.
        $lastManual = ep_setting('google_reviews_last_manual_refresh_at', '');
        $lastManualTs = $lastManual !== '' ? strtotime($lastManual) : false;
        $elapsed = $lastManualTs !== false ? time() - $lastManualTs : PHP_INT_MAX;
        if ($elapsed < EP_ADMIN_REFRESH_COOLDOWN_SECONDS) {
            $wait = EP_ADMIN_REFRESH_COOLDOWN_SECONDS - $elapsed;
            cms_flash('error', 'Refresh was already run ' . $elapsed . ' second(s) ago — please wait '
                . $wait . ' more second(s). This limit protects your Google API quota; the scheduled sync is unaffected.');
            header('Location: google-reviews.php');
            exit;
        }
        // Recorded BEFORE the call so a slow, failed, or timed-out run
        // still starts the cooldown — otherwise repeatedly failing syncs
        // could be retried without limit.
        ep_save_setting('google_reviews_last_manual_refresh_at', date('Y-m-d H:i:s'));

        // Calls the exact same service the cron script runs
        // (ep_google_sync_reviews) — no API logic is duplicated here, so
        // fetching, validation, the 30-day purge, credential redaction and
        // "preserve old reviews on failure" all behave identically whether
        // triggered manually or on schedule.
        $result = ep_google_sync_reviews();
        $purgedNote = (int) ($result['purged'] ?? 0) > 0
            ? ' Removed ' . (int) $result['purged'] . ' review(s) older than 30 days per Google\'s Business Profile API storage policy.'
            : '';
        if ($result['ok']) {
            cms_flash('success', 'Refreshed from Google — ' . (int) $result['synced'] . ' review(s) processed, '
                . (int) ($result['new'] ?? 0) . ' new.' . $purgedNote);
        } else {
            // $result['error'] is already redacted at source by
            // ep_google_redact_secrets(), so no key/token can reach this
            // message. Previously cached reviews are left intact.
            cms_flash('error', 'Refresh failed: ' . $result['error']
                . ' Previously saved reviews are still being shown.' . $purgedNote);
        }
    } elseif ($action === 'save_place_id') {
        $placeId = trim((string) ($_POST['place_id'] ?? ''));
        ep_save_setting('google_place_id', $placeId);
        cms_flash('success', $placeId !== '' ? 'Place ID saved.' : 'Place ID cleared.');
    } elseif ($action === 'toggle_hidden') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $row = $m->getOne('ep_google_reviews');
        if ($row) {
            $newHidden = (int) $row['is_hidden'] === 1 ? 0 : 1;
            $m->where('id', $id);
            $m->update('ep_google_reviews', ['is_hidden' => $newHidden]);
            // Remember it separately so the decision survives the 30-day
            // purge and any later re-sync (never deletes the review).
            ep_google_remember_moderation((string) $row['google_review_id'], $newHidden);
        }
        // Return to the page the admin was on, so toggling a review on
        // page 4 doesn't bounce them back to page 1.
        $backPage = max(1, (int) ($_POST['p'] ?? 1));
        header('Location: google-reviews.php' . ($backPage > 1 ? '?p=' . $backPage : ''));
        exit;
    }
    header('Location: google-reviews.php');
    exit;
}

cms_require_permission('reviews.manage');

$clientId = ep_setting('google_oauth_client_id', '');
$clientSecret = ep_setting('google_oauth_client_secret', '');
$refreshToken = ep_setting('google_oauth_refresh_token', '');
$locationId = ep_setting('google_business_location_id', '');
$isConnected = $refreshToken !== '';
$hasCredentials = $clientId !== '' && $clientSecret !== '';

$lastSyncedAt = ep_setting('google_reviews_last_synced_at', '');
$lastSyncStatus = ep_setting('google_reviews_last_sync_status', '');
$lastSyncError = ep_setting('google_reviews_last_sync_error', '');
$lastSyncErrorType = ep_setting('google_reviews_last_sync_error_type', '');
$errorTypeLabels = [
    'not_configured' => 'Not configured',
    'not_connected' => 'Not connected',
    'invalid_credentials' => 'Invalid credentials',
    'quota_exceeded' => 'Quota exceeded',
    'permission_denied' => 'Permission denied',
    'network_failure' => 'Network failure / timeout',
    'malformed_response' => 'Malformed response',
    'partial_sync' => 'Partial sync (some pages failed)',
    'api_error' => 'API error',
];
$reviewsSummary = ep_google_reviews_summary();
$avgRating = $reviewsSummary['average_rating'];
$totalCount = $reviewsSummary['total_count'];

$locationsResult = null;
if ($isConnected && $locationId === '' && isset($_GET['choose_location'])) {
    $accountsResult = ep_google_list_accounts();
    if ($accountsResult['ok'] && $accountsResult['accounts']) {
        $allLocations = [];
        foreach ($accountsResult['accounts'] as $account) {
            $locResult = ep_google_list_locations($account['name']);
            if ($locResult['ok']) {
                $allLocations = array_merge($allLocations, $locResult['locations']);
            }
        }
        $locationsResult = ['ok' => true, 'locations' => $allLocations];
    } else {
        $locationsResult = ['ok' => false, 'error' => $accountsResult['error'] ?? 'Could not load Google accounts.'];
    }
}

$placesApiKey = ep_env('GOOGLE_PLACES_API_KEY');
$placeId = ep_google_place_id();
$placeIdFromEnv = ep_env('GOOGLE_PLACE_ID') !== '';
$placeSearchQuery = trim((string) ($_GET['q'] ?? ''));
$placeSearchResult = null;
if ($placesApiKey !== '' && $placeSearchQuery !== '') {
    $placeSearchResult = ep_google_places_search($placeSearchQuery);
}
// Real, already-verified business info from Contact Items (not invented) —
// used only to pre-fill the search box so the admin doesn't retype it.
$m->where('item_type', 'address');
$addressRow = $m->getOne('ep_contact_items');
$placeSearchDefault = 'EduPortal' . (!empty($addressRow['value']) ? ', ' . str_replace("\n", ', ', $addressRow['value']) : '');

// Manual, admin-triggered only — never runs automatically. Nothing this
// returns is persisted; see ep_google_place_live_preview()'s doc comment.
$livePreview = null;
if ($placesApiKey !== '' && $placeId !== '' && isset($_GET['preview_live'])) {
    $livePreview = ep_google_place_live_preview($placeId);
}

// Paginated so the admin table never loads the whole collection into the
// browser — the sync follows Google's nextPageToken (STEP 25), so this
// table can legitimately hold thousands of rows.
//
// Deliberately NOT using MysqliDb::paginate(): it relies on
// SQL_CALC_FOUND_ROWS, which MySQL deprecated in 8.0.17. An explicit
// COUNT(*) + LIMIT/OFFSET is portable across both MySQL and MariaDB, which
// matters because production is cPanel-hosted and its engine/version isn't
// guaranteed to match this dev box.
const EP_ADMIN_REVIEWS_PER_PAGE = 25;

$m->where('is_hidden', 0);
$visibleCount = (int) $m->getValue('ep_google_reviews', 'COUNT(*)');

$totalReviews = (int) $m->getValue('ep_google_reviews', 'COUNT(*)');
$totalPages = max(1, (int) ceil($totalReviews / EP_ADMIN_REVIEWS_PER_PAGE));
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * EP_ADMIN_REVIEWS_PER_PAGE;

$m->orderBy('review_date', 'DESC');
$reviews = $m->get('ep_google_reviews', [$offset, EP_ADMIN_REVIEWS_PER_PAGE]);

$authState = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $authState;

cms_page_start('Google Reviews', 'reviews', 'reviews.manage');
?>
<div class="row g-4">
  <div class="col-lg-6">
    <?php cms_card_open('Google API connection', 'Credentials from your Google Cloud Console OAuth client. Never shown in full once saved.'); ?>
      <form method="post" class="mb-3">
        <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
        <input type="hidden" name="action" value="save_credentials">
        <div class="mb-3">
          <label class="form-label">OAuth Client ID</label>
          <input type="text" name="client_id" class="form-control" value="<?= ep_h($clientId) ?>" placeholder="xxxxxxxx.apps.googleusercontent.com">
        </div>
        <div class="mb-3">
          <label class="form-label">OAuth Client Secret</label>
          <input type="password" name="client_secret" class="form-control" value="" placeholder="<?= $clientSecret !== '' ? 'Saved — leave blank to keep it' : 'Enter client secret' ?>" autocomplete="new-password">
        </div>
        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-save me-1"></i>Save credentials</button>
      </form>

      <hr>

      <?php if (!$hasCredentials): ?>
        <p class="text-muted small mb-0">Save your Client ID and Client Secret above before connecting.</p>
      <?php elseif (!$isConnected): ?>
        <a class="btn btn-primary" href="<?= ep_h(ep_google_oauth_authorize_url($authState)) ?>">
          <i class="bi bi-google me-1"></i>Connect with Google
        </a>
      <?php else: ?>
        <p class="mb-2"><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Connected</span></p>
        <form method="post" onsubmit="return confirm('Disconnect from Google? Previously synced reviews will remain visible on the site until you reconnect and sync again.')">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="disconnect">
          <button class="btn btn-outline-danger btn-sm">Disconnect</button>
        </form>
      <?php endif; ?>
    <?php cms_card_close(); ?>

    <?php if ($isConnected): ?>
    <?php cms_card_open('Business location', 'The Google Business Profile location reviews are synced from.'); ?>
      <?php if ($locationId !== ''): ?>
        <p class="mb-2"><code><?= ep_h($locationId) ?></code></p>
        <a class="btn btn-outline-secondary btn-sm" href="google-reviews.php?choose_location=1">Change location</a>
      <?php else: ?>
        <a class="btn btn-outline-primary btn-sm mb-3" href="google-reviews.php?choose_location=1"><i class="bi bi-geo-alt me-1"></i>Load my locations</a>
        <?php if ($locationsResult !== null): ?>
          <?php if (!$locationsResult['ok']): ?>
            <p class="text-danger small"><?= ep_h($locationsResult['error']) ?></p>
          <?php elseif (!$locationsResult['locations']): ?>
            <p class="text-muted small mb-0">No locations found on this Google account.</p>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
              <input type="hidden" name="action" value="save_location">
              <?php foreach ($locationsResult['locations'] as $loc): ?>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="location_id" id="loc<?= ep_h(md5($loc['name'])) ?>" value="<?= ep_h($loc['name']) ?>" required>
                <label class="form-check-label" for="loc<?= ep_h(md5($loc['name'])) ?>"><?= ep_h($loc['title'] ?? $loc['name']) ?></label>
              </div>
              <?php endforeach; ?>
              <button class="btn btn-primary btn-sm mt-2">Use this location</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    <?php cms_card_close(); ?>
    <?php endif; ?>

    <?php cms_card_open('Google Place ID', 'Used only for the "Write a review" / "View on Google" links below — never used to fetch or cache review content. This ID is looked up directly from Google, never guessed.'); ?>
      <div class="mb-3">
        <label class="form-label mb-1">Places API key</label>
        <div>
          <?php if ($placesApiKey !== ''): ?>
          <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Configured via .env</span>
          <?php else: ?>
          <span class="badge bg-secondary">Not configured</span>
          <?php endif; ?>
        </div>
        <p class="form-text mb-0">Set directly in the server's <code>.env</code> file as <code>GOOGLE_PLACES_API_KEY</code> — never entered through this page, so it's never written to the database or sent in any form submission.</p>
      </div>

      <?php if ($placeId !== '' && $placeIdFromEnv): ?>
        <p class="mb-2"><code><?= ep_h($placeId) ?></code> <span class="badge bg-light text-dark border">from .env</span></p>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <a class="btn btn-outline-secondary btn-sm" href="<?= ep_h(ep_google_maps_place_url($placeId)) ?>" target="_blank" rel="noopener">View on Google Maps</a>
          <a class="btn btn-outline-secondary btn-sm" href="<?= ep_h(ep_google_write_review_url($placeId)) ?>" target="_blank" rel="noopener">Test "Write a review" link</a>
        </div>
        <p class="text-muted small mb-0">Set via <code>GOOGLE_PLACE_ID</code> in <code>.env</code>, which takes priority over the value below. Remove it from <code>.env</code> to manage it here instead.</p>
      <?php elseif ($placeId !== ''): ?>
        <p class="mb-2"><code><?= ep_h($placeId) ?></code></p>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <a class="btn btn-outline-secondary btn-sm" href="<?= ep_h(ep_google_maps_place_url($placeId)) ?>" target="_blank" rel="noopener">View on Google Maps</a>
          <a class="btn btn-outline-secondary btn-sm" href="<?= ep_h(ep_google_write_review_url($placeId)) ?>" target="_blank" rel="noopener">Test "Write a review" link</a>
        </div>
        <form method="post" onsubmit="return confirm('Clear the saved Place ID?')">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="save_place_id">
          <input type="hidden" name="place_id" value="">
          <button class="btn btn-outline-danger btn-sm">Clear</button>
        </form>
      <?php endif; ?>

      <?php if ($placeId !== '' && $placesApiKey !== ''): ?>
        <hr>
        <a class="btn btn-outline-secondary btn-sm" href="google-reviews.php?preview_live=1#live-preview">
          <i class="bi bi-eye me-1"></i>Preview live from Google
        </a>
        <p class="form-text mb-0">Fetches your current rating/review count/reviews directly from Google right now, for comparison only — nothing shown here is saved. Each click is a billed Places API call (Enterprise + Atmosphere SKU), so use it to spot-check, not routinely.</p>

        <?php if ($livePreview !== null): ?>
        <div id="live-preview" class="mt-3 pt-3 border-top">
          <?php if (!$livePreview['ok']): ?>
            <p class="text-danger small mb-0"><?= ep_h($livePreview['error']) ?></p>
          <?php else: ?>
            <p class="mb-2">
              <strong>Google right now:</strong>
              <?= $livePreview['rating'] !== null ? ep_h((string) $livePreview['rating']) . ' / 5' : '—' ?>
              from <?= $livePreview['user_rating_count'] !== null ? ep_h((string) $livePreview['user_rating_count']) : '?' ?> review(s)
            </p>
            <?php if (!$livePreview['reviews']): ?>
              <p class="text-muted small mb-0">Google returned no reviews in this response (Place Details returns at most 5).</p>
            <?php else: ?>
              <p class="text-muted small mb-2">Ordered by relevance — Google's default for Place Details reviews. Attribution per Google Maps Platform policy: author avatar, name, profile link, and a link to view each review on <strong>Google Maps</strong>.</p>
              <ul class="list-unstyled small mb-0">
              <?php foreach ($livePreview['reviews'] as $lr): ?>
              <li class="mb-2 pb-2 border-bottom d-flex gap-2">
                <?php if (!empty($lr['author_photo'])): ?>
                <img src="<?= ep_h($lr['author_photo']) ?>" alt="" width="32" height="32" style="border-radius:50%;flex-shrink:0" loading="lazy" referrerpolicy="no-referrer">
                <?php endif; ?>
                <span>
                  <?php if (!empty($lr['author_profile_url'])): ?>
                  <a href="<?= ep_h($lr['author_profile_url']) ?>" target="_blank" rel="noopener"><strong><?= ep_h($lr['author_name']) ?></strong></a>
                  <?php else: ?>
                  <strong><?= ep_h($lr['author_name']) ?></strong>
                  <?php endif; ?>
                  — <?= ep_google_star_display((float) $lr['rating']) ?>
                  <span class="text-muted">(<?= ep_h($lr['relative_time'] ?? $lr['review_date'] ?? '') ?>)</span>
                  <?php if (!empty($lr['review_text'])): ?><br><?= ep_h(mb_strimwidth($lr['review_text'], 0, 200, '…')) ?><?php endif; ?>
                  <?php if (!empty($lr['google_maps_review_url'])): ?><br><a href="<?= ep_h($lr['google_maps_review_url']) ?>" target="_blank" rel="noopener">View on Google Maps</a><?php endif; ?>
                </span>
              </li>
              <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($placeId !== ''): ?>
        <?php // Already shown above (env or DB-saved value + live-preview button) — nothing more to render here. ?>
      <?php elseif ($placesApiKey === ''): ?>
        <p class="text-muted small mb-0">Set a Places API key in <code>.env</code> to search for your business, or paste a known Place ID directly below.</p>
        <form method="post" class="mt-2">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="save_place_id">
          <div class="input-group input-group-sm">
            <input type="text" name="place_id" class="form-control" placeholder="ChIJ...">
            <button class="btn btn-outline-primary">Save</button>
          </div>
        </form>
      <?php else: ?>
        <form method="get" class="mb-3">
          <label class="form-label">Search for your business on Google</label>
          <div class="input-group input-group-sm">
            <input type="text" name="q" class="form-control" value="<?= ep_h($placeSearchQuery !== '' ? $placeSearchQuery : $placeSearchDefault) ?>" placeholder="Business name and address">
            <button class="btn btn-primary">Search</button>
          </div>
        </form>
        <?php if ($placeSearchResult !== null): ?>
          <?php if (!$placeSearchResult['ok']): ?>
            <p class="text-danger small"><?= ep_h($placeSearchResult['error']) ?></p>
          <?php elseif (!$placeSearchResult['places']): ?>
            <p class="text-muted small">No matches found. Try a more specific name/address.</p>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
              <input type="hidden" name="action" value="save_place_id">
              <?php foreach ($placeSearchResult['places'] as $place):
                $pid = $place['id'] ?? '';
                $pname = $place['displayName']['text'] ?? $pid;
                $paddr = $place['formattedAddress'] ?? '';
              ?>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="place_id" id="p<?= ep_h(md5($pid)) ?>" value="<?= ep_h($pid) ?>" required>
                <label class="form-check-label" for="p<?= ep_h(md5($pid)) ?>">
                  <strong><?= ep_h($pname) ?></strong><br><span class="text-muted small"><?= ep_h($paddr) ?></span>
                </label>
              </div>
              <?php endforeach; ?>
              <button class="btn btn-primary btn-sm mt-1">Use selected as our Place ID</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
        <hr>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="save_place_id">
          <label class="form-label small">Or paste a known Place ID directly</label>
          <div class="input-group input-group-sm">
            <input type="text" name="place_id" class="form-control" placeholder="ChIJ...">
            <button class="btn btn-outline-primary">Save</button>
          </div>
        </form>
      <?php endif; ?>
    <?php cms_card_close(); ?>
  </div>

  <div class="col-lg-6">
    <?php cms_card_open('Sync status', 'Reviews are never fetched live for site visitors — only by this manual button or the scheduled cron sync.'); ?>
      <p class="small text-muted">Per Google's Business Profile API storage policy, cached reviews cannot be kept longer than 30 days without being reconfirmed from Google — schedule the cron sync more often than that (e.g. every 6 hours) so this never affects what's shown on the site.</p>
      <table class="table table-sm mb-3">
        <tr><th class="text-muted fw-normal" style="width:45%">Last synced</th><td><?= $lastSyncedAt !== '' ? ep_h($lastSyncedAt) : '<span class="text-muted">Never</span>' ?></td></tr>
        <tr><th class="text-muted fw-normal">Status</th><td>
          <?php if ($lastSyncStatus === 'success'): ?><span class="badge bg-success">Success</span>
          <?php elseif ($lastSyncStatus === 'error'): ?><span class="badge bg-danger">Error</span>
          <?php else: ?><span class="text-muted">—</span><?php endif; ?>
        </td></tr>
        <?php if ($lastSyncError !== ''): ?>
        <tr><th class="text-muted fw-normal">Last error</th><td class="text-danger small">
          <?php if ($lastSyncErrorType !== ''): ?><span class="badge bg-danger-subtle text-danger border me-1"><?= ep_h($errorTypeLabels[$lastSyncErrorType] ?? $lastSyncErrorType) ?></span><br><?php endif; ?>
          <?= ep_h($lastSyncError) ?>
        </td></tr>
        <?php endif; ?>
        <tr><th class="text-muted fw-normal">Google average rating</th><td><?= $avgRating !== '' ? ep_h($avgRating) : '<span class="text-muted">—</span>' ?></td></tr>
        <tr><th class="text-muted fw-normal">Google total review count</th><td><?= $totalCount !== '' ? ep_h($totalCount) : '<span class="text-muted">—</span>' ?></td></tr>
      </table>
      <?php
      $lastManualRefresh = ep_setting('google_reviews_last_manual_refresh_at', '');
      $lastManualTs = $lastManualRefresh !== '' ? strtotime($lastManualRefresh) : false;
      $cooldownLeft = $lastManualTs !== false
          ? max(0, EP_ADMIN_REFRESH_COOLDOWN_SECONDS - (time() - $lastManualTs))
          : 0;
      $refreshDisabled = !$isConnected || $locationId === '' || $cooldownLeft > 0;
      ?>
      <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='Refreshing…';">
        <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
        <input type="hidden" name="action" value="sync_now">
        <button class="btn btn-primary btn-sm"<?= $refreshDisabled ? ' disabled' : '' ?>><i class="bi bi-arrow-repeat me-1"></i>Refresh Now</button>
      </form>
      <?php if (!$isConnected || $locationId === ''): ?>
      <p class="form-text mb-0">Connect to Google and choose a business location above to enable refreshing.</p>
      <?php elseif ($cooldownLeft > 0): ?>
      <p class="form-text mb-0">Recently refreshed — available again in <?= (int) $cooldownLeft ?> second(s). The scheduled sync keeps running regardless.</p>
      <?php else: ?>
      <p class="form-text mb-0">Manual refreshes are limited to one per <?= (int) EP_ADMIN_REFRESH_COOLDOWN_SECONDS ?> seconds to protect your Google API quota.</p>
      <?php endif; ?>
    <?php cms_card_close(); ?>
  </div>
</div>

<?php cms_list_card_start('Reviews (' . $visibleCount . ' visible of ' . $totalReviews . ')', $totalReviews, '', ''); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Author</th><th>Rating</th><th>Review</th><th>Posted</th><th>Last synced</th><th class="text-center">Visibility</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if (!$reviews): ?><tr><td colspan="7" class="text-center text-muted py-4">No reviews synced yet.</td></tr><?php endif; ?>
      <?php foreach ($reviews as $r): ?>
      <tr>
        <td><?= ep_h($r['author_name']) ?></td>
        <td class="text-nowrap"><?= ep_google_star_display((float) $r['rating']) ?></td>
        <?php // Full review text is rendered and clamped with CSS rather than cut
              // with mb_strimwidth(): the moderator needs to read the whole review
              // to decide whether to hide it, and keeping it in the DOM means it
              // stays selectable and findable with the browser's own search. ?>
        <td style="max-width:340px">
          <div style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden"><?= ep_h((string) $r['review_text']) ?></div>
        </td>
        <td class="text-nowrap small"><?= ep_h(ep_google_format_review_date($r['review_date'] ?? null) ?: '—') ?></td>
        <td class="text-nowrap small text-muted"><?= ep_h(ep_google_format_review_date($r['fetched_at'] ?? null) ?: '—') ?></td>
        <td class="text-center">
          <?php if ((int) $r['is_hidden'] === 0): ?>
          <span class="badge bg-success">Shown</span>
          <?php else: ?>
          <span class="badge bg-secondary">Hidden</span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <form method="post" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="toggle_hidden">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="p" value="<?= (int) $currentPage ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary"><?= (int) $r['is_hidden'] === 0 ? 'Hide' : 'Show' ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php if ($totalPages > 1): ?>
<nav aria-label="Reviews pages" class="mt-3">
  <ul class="pagination pagination-sm justify-content-center mb-1">
    <li class="page-item<?= $currentPage <= 1 ? ' disabled' : '' ?>">
      <a class="page-link" href="google-reviews.php?p=<?= (int) ($currentPage - 1) ?>">Previous</a>
    </li>
    <?php
    // Windowed page links so a few thousand reviews don't render a few
    // hundred numbered links.
    $windowStart = max(1, $currentPage - 2);
    $windowEnd = min($totalPages, $currentPage + 2);
    if ($windowStart > 1): ?>
      <li class="page-item"><a class="page-link" href="google-reviews.php?p=1">1</a></li>
      <?php if ($windowStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
    <?php endif; ?>
    <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
      <li class="page-item<?= $i === $currentPage ? ' active' : '' ?>">
        <a class="page-link" href="google-reviews.php?p=<?= (int) $i ?>"><?= (int) $i ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($windowEnd < $totalPages): ?>
      <?php if ($windowEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <li class="page-item"><a class="page-link" href="google-reviews.php?p=<?= (int) $totalPages ?>"><?= (int) $totalPages ?></a></li>
    <?php endif; ?>
    <li class="page-item<?= $currentPage >= $totalPages ? ' disabled' : '' ?>">
      <a class="page-link" href="google-reviews.php?p=<?= (int) ($currentPage + 1) ?>">Next</a>
    </li>
  </ul>
  <p class="text-center text-muted small mb-0">
    Page <?= (int) $currentPage ?> of <?= (int) $totalPages ?> — showing <?= count($reviews) ?> of <?= (int) $totalReviews ?> reviews
  </p>
</nav>
<?php endif; ?>
<?php cms_page_end(); ?>
