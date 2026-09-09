<?php
/**
 * Public job detail page — /careers/{slug} (rewritten to career.php?slug=…).
 *
 * Renders the full role description, the Google JobPosting structured data,
 * and the application form. Server-rendered end to end: the form posts back
 * to this page and works without JavaScript.
 */
require_once __DIR__ . '/includes/careers.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$job = ep_get_job_by_slug($slug);

if (!$job) {
    // Closed, expired, draft, or never existed — send applicants to the
    // listing rather than a dead end, and keep it out of the index.
    http_response_code(404);
    $pageTitle = 'Position not found — EduPortal Careers';
    $pageDescription = 'This position is no longer available. See all current openings at EduPortal.';
    $canonicalUrl = 'https://eduportal.pk/careers';
    $navActive = 'careers';
    $useDemoModal = true;
    $extraStylesheets = ['css/shared.css', 'css/careers.css'];
    $epTrackPage = 'career-not-found';
    $extraHeadHtml = '<meta name="robots" content="noindex, follow">';
    require __DIR__ . '/includes/head.php';
    require __DIR__ . '/includes/header.php';
    ?>
    <main>
      <section class="page-hero">
        <div class="container">
          <span class="section-label">Careers</span>
          <h1>This position is no longer open</h1>
          <p>The role you were looking for has been filled or has closed. Have a look at what else is open right now.</p>
        </div>
      </section>
      <section class="section">
        <div class="container careers-empty">
          <a href="<?= ep_h(ep_careers_url()) ?>" class="btn btn-primary">See all open positions</a>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/partials/demo-modal.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$formErrors = [];
$formValues = [];
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $result = ep_careers_handle_submission($_POST, $_FILES, $job);
    if ($result['ok']) {
        // Post/Redirect/Get so a refresh cannot resubmit the application.
        header('Location: ' . ep_job_url($job) . '?submitted=1#apply');
        exit;
    }
    $formErrors = $result['errors'];
    $formValues = $result['values'];
    $formSubmitted = true;
}

$justSubmitted = isset($_GET['submitted']);
$salary = ep_job_salary_display($job);
$responsibilities = ep_job_bullets($job['responsibilities'] ?? '');
$requirements = ep_job_bullets($job['requirements'] ?? '');
$benefits = ep_job_bullets($job['benefits'] ?? '');
$skills = ep_job_skills($job);

$metaTitle = trim((string) ($job['meta_title'] ?? ''));
$pageTitle = ($metaTitle !== '' ? $metaTitle : $job['title'] . ' — Careers at EduPortal');
$pageDescription = ep_job_meta_description($job);
$canonicalUrl = 'https://eduportal.pk/careers/' . rawurlencode((string) $job['slug']);
$navActive = 'careers';
$useDemoModal = true;
$extraStylesheets = ['css/shared.css', 'css/careers.css'];
$epTrackPage = 'career:' . $job['slug'];
$jsonLdSchema = json_encode(
    ep_job_posting_schema($job),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero job-hero">
      <div class="container">
        <nav class="job-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= ep_h(ep_careers_url()) ?>">Careers</a>
          <span aria-hidden="true">/</span>
          <span><?= ep_h($job['title']) ?></span>
        </nav>
        <h1><?= ep_h($job['title']) ?></h1>
        <div class="job-badges job-badges--hero">
          <span class="job-badge job-badge--mode job-badge--mode-<?= ep_h(strtolower($job['work_mode'])) ?>"><?= ep_h($job['work_mode']) ?></span>
          <span class="job-badge job-badge--type"><?= ep_h($job['job_type']) ?></span>
          <?php if (!empty($job['department'])): ?>
          <span class="job-badge job-badge--dept"><?= ep_h($job['department']) ?></span>
          <?php endif; ?>
          <?php if (!empty($job['experience'])): ?>
          <span class="job-badge job-badge--exp"><?= ep_h($job['experience']) ?></span>
          <?php endif; ?>
          <?php if (!empty($job['is_featured'])): ?>
          <span class="job-badge job-badge--featured">Featured</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($job['summary'])): ?>
        <p><?= ep_h($job['summary']) ?></p>
        <?php endif; ?>
        <ul class="job-meta job-meta--hero">
          <?php if (!empty($job['location'])): ?>
          <li><i data-lucide="map-pin" aria-hidden="true"></i><?= ep_h($job['location']) ?></li>
          <?php endif; ?>
          <?php if ($salary !== ''): ?>
          <li><i data-lucide="wallet" aria-hidden="true"></i><?= ep_h($salary) ?></li>
          <?php endif; ?>
          <?php if (!empty($job['deadline'])): ?>
          <li><i data-lucide="calendar-clock" aria-hidden="true"></i>Apply by <?= ep_h(ep_format_date((string) $job['deadline'])) ?></li>
          <?php endif; ?>
          <?php if (!empty($job['posted_at'])): ?>
          <li><i data-lucide="calendar" aria-hidden="true"></i>Posted <?= ep_h(ep_format_date((string) $job['posted_at'])) ?></li>
          <?php endif; ?>
        </ul>
        <a href="#apply" class="btn btn-primary job-hero-cta">Apply Now</a>
      </div>
    </section>

    <?php if ($justSubmitted): ?>
    <section class="section careers-thanks-section">
      <div class="container">
        <div class="apply-alert apply-alert--success" role="status" data-conversion="job_application">
          <h2>Thank you — your application has been received.</h2>
          <p>Our HR team reviews every application for <strong><?= ep_h($job['title']) ?></strong>. If your profile matches, we will contact you on the email or WhatsApp number you provided.</p>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section job-detail-section">
      <div class="container job-detail-layout">
        <div class="job-detail-main">
          <?php if (!empty($job['description'])): ?>
          <div class="job-block">
            <h2>About the role</h2>
            <div class="job-prose"><?= nl2br(ep_h((string) $job['description'])) ?></div>
          </div>
          <?php endif; ?>

          <?php
          foreach ([
              'What you will do' => $responsibilities,
              'What we are looking for' => $requirements,
              'What we offer' => $benefits,
          ] as $blockTitle => $bullets):
            if (!$bullets) {
                continue;
            }
          ?>
          <div class="job-block">
            <h2><?= ep_h($blockTitle) ?></h2>
            <ul class="job-bullets">
              <?php foreach ($bullets as $bullet): ?>
              <li><i data-lucide="check" aria-hidden="true"></i><span><?= ep_h($bullet) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>

          <?php if ($skills): ?>
          <div class="job-block">
            <h2>Skills</h2>
            <ul class="job-skills">
              <?php foreach ($skills as $skill): ?>
              <li><?= ep_h($skill) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>

        <aside class="job-detail-aside">
          <div class="job-summary-card">
            <h2>At a glance</h2>
            <dl>
              <?php if (!empty($job['department'])): ?>
              <div><dt>Department</dt><dd><?= ep_h($job['department']) ?></dd></div>
              <?php endif; ?>
              <?php if (!empty($job['location'])): ?>
              <div><dt>Location</dt><dd><?= ep_h($job['location']) ?></dd></div>
              <?php endif; ?>
              <div><dt>Work mode</dt><dd><?= ep_h($job['work_mode']) ?></dd></div>
              <div><dt>Job type</dt><dd><?= ep_h($job['job_type']) ?></dd></div>
              <?php if (!empty($job['experience'])): ?>
              <div><dt>Experience</dt><dd><?= ep_h($job['experience']) ?></dd></div>
              <?php endif; ?>
              <?php if ((int) $job['vacancies'] > 1): ?>
              <div><dt>Vacancies</dt><dd><?= (int) $job['vacancies'] ?></dd></div>
              <?php endif; ?>
              <?php if ($salary !== ''): ?>
              <div><dt>Salary</dt><dd><?= ep_h($salary) ?></dd></div>
              <?php endif; ?>
              <?php if (!empty($job['deadline'])): ?>
              <div><dt>Apply by</dt><dd><?= ep_h(ep_format_date((string) $job['deadline'])) ?></dd></div>
              <?php endif; ?>
              <?php if (!empty($job['posted_at'])): ?>
              <div><dt>Posted</dt><dd><?= ep_h(ep_format_date((string) $job['posted_at'])) ?></dd></div>
              <?php endif; ?>
            </dl>
            <a href="#apply" class="btn btn-primary">Apply Now</a>
          </div>
        </aside>
      </div>
    </section>

    <?php require __DIR__ . '/includes/partials/application-form.php'; ?>

    <section class="section job-other-section">
      <div class="container">
        <a href="<?= ep_h(ep_careers_url()) ?>" class="btn btn-ghost"><i data-lucide="arrow-left" aria-hidden="true"></i> All open positions</a>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
