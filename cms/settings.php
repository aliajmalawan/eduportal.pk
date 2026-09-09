<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

// Numeric metric fields are stored the same way they're displayed on the
// public site (e.g. "500+", "300,000+") — not bare digits — so validation
// accepts digits with optional comma separators and an optional trailing
// "+", rather than requiring a plain integer.
$numericMetricKeys = ['total_clients', 'total_students', 'total_cities'];
$numericMetricPattern = '/^[0-9][0-9,]*\+?$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: settings.php');
        exit;
    }

    // Demo video file upload (Home Page -> Demo Video). Handled separately
    // from the generic settings[] loop below since it's a file, not a plain
    // text value — reuses the same upload/validation the video testimonials
    // use (mp4/webm/mov, 200MB max, MOV auto-converted to MP4).
    $demoFile = $_FILES['demo_video_file'] ?? null;
    if (is_array($demoFile) && ($demoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $m->where('setting_key', 'demo_video_file_path');
        $existingDemoFileRow = $m->getOne('ep_site_settings');
        $existingDemoPath = $existingDemoFileRow ? (string) $existingDemoFileRow['setting_value'] : '';
        $newDemoPath = ep_normalize_public_path(
            cms_upload_video_file($demoFile, $existingDemoPath, 'demo-video'),
            'assets/video-testimonials'
        );
        if ($existingDemoFileRow) {
            $m->where('setting_key', 'demo_video_file_path');
            $m->update('ep_site_settings', ['setting_value' => $newDemoPath]);
        } else {
            $m->insert('ep_site_settings', [
                'setting_key' => 'demo_video_file_path',
                'setting_value' => $newDemoPath,
                'setting_group' => 'home_page',
            ]);
        }
    }

    $pairs = $_POST['settings'] ?? [];
    if (is_array($pairs)) {
        $errors = [];
        foreach ($pairs as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            // google_reviews keys (OAuth credentials/tokens) are gated behind
            // the separate reviews.manage permission — never writable from this
            // generic form, even by an admin who can reach this page.
            $m->where('setting_key', $key);
            $existingRow = $m->getOne('ep_site_settings');
            if ($existingRow && $existingRow['setting_group'] === 'google_reviews') {
                continue;
            }
            $value = trim((string) $value);

            if ($key === 'demo_video_type' && !in_array($value, ['youtube', 'upload'], true)) {
                $value = 'youtube';
            }

            if (in_array($key, $numericMetricKeys, true) && $value !== '' && !preg_match($numericMetricPattern, $value)) {
                $errors[] = "\"$key\" must be a number (digits only, optionally with commas and a trailing +) — got \"$value\".";
                continue;
            }
            if ($key === 'metrics_updated_date' && ($value !== strip_tags($value) || mb_strlen($value) > 60)) {
                $errors[] = '"metrics_updated_date" contains invalid characters or is too long.';
                continue;
            }

            if ($existingRow) {
                $m->where('setting_key', $key);
                $m->update('ep_site_settings', ['setting_value' => $value]);
            } else {
                $m->insert('ep_site_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_group' => 'general',
                ]);
            }
        }
        if ($errors) {
            cms_flash('error', 'Some values were not saved: ' . implode(' ', $errors));
        } else {
            cms_flash('success', 'Settings updated.');
        }
    }
    header('Location: settings.php');
    exit;
}

// google_reviews holds OAuth credentials/tokens — managed exclusively via
// the dedicated cms/google-reviews.php page, never shown as a plain-text
// input here.
$m->where('setting_group', 'google_reviews', '!=');
$m->orderBy('setting_group', 'ASC');
$m->orderBy('setting_key', 'ASC');
$rows = $m->get('ep_site_settings');

$groupOrder = ['home_page', 'company', 'site_content', 'general', 'branding', 'contact', 'careers', 'media'];
$groupLabels = [
    'home_page' => 'Home Page',
    'company' => 'Company Facts (About page)',
    'site_content' => 'Site Content',
    'general' => 'General',
    'branding' => 'Branding',
    'contact' => 'Contact',
    'media' => 'Media',
    'careers' => 'Careers Page',
];
$fieldHelp = [
    'total_clients' => 'Shown as the headline "institutes/clients" stat on the About page, homepage, and landing page.',
    'total_students' => 'Shown as the "students managed" stat on the About page.',
    'total_cities' => 'Shown as the coverage stat on the About page once filled in. Leave empty to keep showing "Nationwide".',
    'metrics_updated_date' => 'Shown as the small "Verified as of..." line under the stats on the About page. Leave empty to hide it.',
    'careers_intro_heading' => 'Headline at the top of the public careers page.',
    'careers_intro_text' => 'Intro paragraph under the careers headline.',
    'careers_no_jobs_text' => 'Message shown on the careers page when no positions are open. The general CV form is displayed underneath it.',
    'careers_notify_email' => 'Address that receives a notification for each new application. A job can override this with its own notification email.',
    'careers_hr_whatsapp' => 'HR WhatsApp number shown to applicants, e.g. 923001234567. Leave empty to hide it.',
    'founder_name' => 'Published in plain text on the About page. Leave empty to omit the line entirely.',
    'team_size' => 'e.g. "25+ people" or "40". Shown on the About page; leave empty to omit.',
    'founding_year' => 'e.g. 2018. Shown on the About page and used in the company structured data.',
    'reg_pseb' => 'Pakistan Software Export Board registration number or status. Leave empty if not registered — do not enter a placeholder.',
    'reg_secp' => 'SECP incorporation number. Leave empty if not applicable.',
    'reg_chamber' => 'Chamber of Commerce membership number and chamber name. Leave empty if not a member.',
    'demo_video_url' => 'Paste the full YouTube link for the demo video played from the homepage hero, e.g. https://www.youtube.com/watch?v=xxxxxxxxxxx. youtu.be, /embed/ and /shorts/ links all work, and a ?t= start time is respected. Leave empty to keep the built-in demo.',
    'demo_video_title' => 'Short title for the demo video. Used as the accessible label on the play button and as the video player title.',
];
// Groups rendered as labelled text fields rather than the raw key/value
// table used for everything else.
$labelledGroups = ['home_page', 'company', 'site_content'];
$fieldWidths = [
    'demo_video_url' => 'col-12',
    'demo_video_title' => 'col-sm-6',
];
$fieldLabels = [
    'total_clients' => 'Total Clients / Institutions',
    'total_students' => 'Total Students',
    'total_cities' => 'Total Cities',
    'metrics_updated_date' => 'Metrics Updated Date',
    'founder_name' => 'Founder Name',
    'team_size' => 'Team Size',
    'reg_pseb' => 'PSEB Registration',
    'reg_secp' => 'SECP Registration',
    'reg_chamber' => 'Chamber of Commerce',
    'founding_year' => 'Founding Year',
    'demo_video_url' => 'Demo Video — YouTube Link',
    'demo_video_title' => 'Demo Video — Title',
];

$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['setting_group']][] = $row;
}
uksort($grouped, function ($a, $b) use ($groupOrder) {
    $ia = array_search($a, $groupOrder, true);
    $ib = array_search($b, $groupOrder, true);
    $ia = $ia === false ? count($groupOrder) : $ia;
    $ib = $ib === false ? count($groupOrder) : $ib;
    return $ia <=> $ib;
});

$homePageOrder = ['demo_video_url', 'demo_video_title'];
if (isset($grouped['home_page'])) {
    usort($grouped['home_page'], function ($a, $b) use ($homePageOrder) {
        $ia = array_search($a['setting_key'], $homePageOrder, true);
        $ib = array_search($b['setting_key'], $homePageOrder, true);
        return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
    });
}

$siteContentOrder = ['total_clients', 'total_students', 'total_cities', 'metrics_updated_date'];
if (isset($grouped['site_content'])) {
    usort($grouped['site_content'], function ($a, $b) use ($siteContentOrder) {
        $ia = array_search($a['setting_key'], $siteContentOrder, true);
        $ib = array_search($b['setting_key'], $siteContentOrder, true);
        return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
    });
}

// Demo video (Home Page): rendered as a bespoke type-toggle block below
// rather than through the generic labelled-group loop, since a file upload
// and a conditional YouTube/upload panel don't fit the plain-text-field
// pattern the rest of that loop assumes. Pulled out of $grouped so it isn't
// also rendered a second time by that generic loop.
$demoVideoType = trim((string) ep_setting('demo_video_type', 'youtube')) === 'upload' ? 'upload' : 'youtube';
$demoVideoUrl = trim((string) ep_setting('demo_video_url', ''));
$demoVideoTitleValue = trim((string) ep_setting('demo_video_title', ''));
$demoVideoFilePath = trim((string) ep_setting('demo_video_file_path', ''));
$demoVideoFileExists = $demoVideoFilePath !== '' && is_file(dirname(__DIR__) . '/' . ltrim(
    ep_normalize_public_path($demoVideoFilePath, 'assets/video-testimonials'),
    '/'
));
if (isset($grouped['home_page'])) {
    $grouped['home_page'] = array_values(array_filter(
        $grouped['home_page'],
        static fn (array $row): bool => !in_array(
            $row['setting_key'],
            ['demo_video_url', 'demo_video_title', 'demo_video_type', 'demo_video_file_path'],
            true
        )
    ));
    if (!$grouped['home_page']) {
        unset($grouped['home_page']);
    }
}

cms_page_start('Site Settings', 'settings', 'settings.manage');
?>
<div class="panel">
  <?php if (cms_can('reviews.manage')): ?>
  <?php // The google_reviews setting group is deliberately excluded from the
        // grid below (it holds OAuth credentials/tokens), so point admins at
        // the page that does manage it rather than leaving them to wonder. ?>
  <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-star-fill mt-1"></i>
    <span>Google Reviews settings — API connection, business location, Place ID, and which reviews are shown publicly — are managed on their own page: <a href="google-reviews.php" class="alert-link">open Google Reviews</a>.</span>
  </div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">

    <h2>Home Page</h2>
    <div class="mb-4">
      <label class="form-label fw-semibold">Demo Video</label>
      <div class="form-text mb-2">Played from the homepage hero's play button. Use a YouTube link, or upload a video file directly.</div>

      <div class="btn-group mb-3" role="group" aria-label="Demo video source">
        <input type="radio" class="btn-check" name="settings[demo_video_type]" id="demoVideoTypeYoutube" value="youtube" autocomplete="off" <?= $demoVideoType === 'youtube' ? 'checked' : '' ?>>
        <label class="btn btn-outline-primary" for="demoVideoTypeYoutube">YouTube link</label>

        <input type="radio" class="btn-check" name="settings[demo_video_type]" id="demoVideoTypeUpload" value="upload" autocomplete="off" <?= $demoVideoType === 'upload' ? 'checked' : '' ?>>
        <label class="btn btn-outline-primary" for="demoVideoTypeUpload">Upload from computer</label>
      </div>

      <div id="demoVideoYoutubePanel" class="<?= $demoVideoType === 'upload' ? 'd-none' : '' ?>">
        <label class="form-label" for="set-demo_video_url">Demo Video — YouTube Link</label>
        <input type="text" id="set-demo_video_url" name="settings[demo_video_url]" class="form-control" value="<?= ep_h($demoVideoUrl) ?>" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx">
        <div class="form-text">youtu.be, /embed/ and /shorts/ links all work, and a ?t= start time is respected. Leave empty to keep the built-in demo.</div>
      </div>

      <div id="demoVideoUploadPanel" class="<?= $demoVideoType === 'upload' ? '' : 'd-none' ?>">
        <label class="form-label" for="demoVideoFile">Video file</label>
        <?php if ($demoVideoFileExists): ?>
        <p class="small text-success mb-2"><i class="bi bi-check-circle me-1"></i>Current file: <?= ep_h(basename($demoVideoFilePath)) ?></p>
        <?php endif; ?>
        <input type="file" id="demoVideoFile" name="demo_video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
        <div class="form-text">MP4, WebM, or MOV — maximum 200 MB. Choosing a new file replaces the current one. Leave empty to keep it.</div>
      </div>

      <div class="mt-3" style="max-width:360px">
        <label class="form-label" for="set-demo_video_title">Demo Video — Title</label>
        <input type="text" id="set-demo_video_title" name="settings[demo_video_title]" class="form-control" value="<?= ep_h($demoVideoTitleValue) ?>">
        <div class="form-text">Short title for the demo video. Used as the accessible label on the play button and as the video player title.</div>
      </div>
    </div>
    <div class="mb-4">
      <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
    </div>
    <script>
      (function () {
        var y = document.getElementById('demoVideoTypeYoutube');
        var u = document.getElementById('demoVideoTypeUpload');
        var yPanel = document.getElementById('demoVideoYoutubePanel');
        var uPanel = document.getElementById('demoVideoUploadPanel');
        function sync() {
          yPanel.classList.toggle('d-none', !y.checked);
          uPanel.classList.toggle('d-none', !u.checked);
        }
        y.addEventListener('change', sync);
        u.addEventListener('change', sync);
      })();
    </script>

    <?php foreach ($grouped as $groupKey => $groupRows): ?>
    <h2><?= ep_h($groupLabels[$groupKey] ?? ucfirst($groupKey)) ?></h2>
    <?php if (in_array($groupKey, $labelledGroups, true)): ?>
    <div class="row g-3 mb-3">
      <?php foreach ($groupRows as $row): ?>
      <div class="<?= ep_h($fieldWidths[$row['setting_key']] ?? 'col-sm-6') ?>">
        <label class="form-label" for="set-<?= ep_h($row['setting_key']) ?>"><?= ep_h($fieldLabels[$row['setting_key']] ?? $row['setting_key']) ?></label>
        <input type="text" id="set-<?= ep_h($row['setting_key']) ?>" name="settings[<?= ep_h($row['setting_key']) ?>]" class="form-control" value="<?= ep_h($row['setting_value']) ?>">
        <?php if (!empty($fieldHelp[$row['setting_key']])): ?>
        <div class="form-text"><?= ep_h($fieldHelp[$row['setting_key']]) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php // Save button for this section. It submits the same form as the one
          // at the bottom of the page, so any other edits are saved too. ?>
    <div class="mb-4">
      <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
    </div>
    <?php else: ?>
    <table class="table table-hover align-middle mb-4">
      <thead class="table-light"><tr><th>Key</th><th>Value</th></tr></thead>
      <tbody>
        <?php foreach ($groupRows as $row): ?>
        <tr>
          <td>
            <?php if (!empty($fieldLabels[$row['setting_key']])): ?>
            <div class="fw-semibold"><?= ep_h($fieldLabels[$row['setting_key']]) ?></div>
            <code class="small text-muted"><?= ep_h($row['setting_key']) ?></code>
            <?php else: ?>
            <code><?= ep_h($row['setting_key']) ?></code>
            <?php endif; ?>
            <?php if (!empty($fieldHelp[$row['setting_key']])): ?>
            <div class="small text-muted"><?= ep_h($fieldHelp[$row['setting_key']]) ?></div>
            <?php endif; ?>
          </td>
          <td><input name="settings[<?= ep_h($row['setting_key']) ?>]" class="form-control" value="<?= ep_h($row['setting_value']) ?>"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <?php endforeach; ?>
    <button class="btn btn-primary" style="margin-top:10px">Save Settings</button>
  </form>
</div>
<?php cms_page_end(); ?>
