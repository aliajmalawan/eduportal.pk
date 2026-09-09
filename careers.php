<?php
/**
 * Public careers page: open roles and the general CV form.
 *
 * Server-rendered like every other public page — the application form is a
 * plain POST, so the whole page works with JavaScript disabled.
 */
require_once __DIR__ . '/includes/careers.php';

$totalOpen = ep_count_open_jobs();
$jobs = $totalOpen > 0 ? ep_get_open_jobs() : [];

// The general CV form is always reachable (?general=1), and is shown by
// default whenever there is nothing to apply to.
$showGeneralForm = $totalOpen === 0 || isset($_GET['general']);

$formErrors = [];
$formValues = [];
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $showGeneralForm = true;
    $result = ep_careers_handle_submission($_POST, $_FILES, null);
    if ($result['ok']) {
        // Post/Redirect/Get so a refresh cannot resubmit the application.
        header('Location: ' . ep_careers_url() . '?submitted=1#apply');
        exit;
    }
    $formErrors = $result['errors'];
    $formValues = $result['values'];
    $formSubmitted = true;
}

$justSubmitted = isset($_GET['submitted']);

$pageTitle = 'Careers at EduPortal — Jobs in School ERP Software';
$pageDescription = 'Join the EduPortal team. Browse current openings in engineering, sales, support and operations, or send us your CV for future roles.';
$canonicalUrl = 'https://eduportal.pk/careers';
$navActive = 'careers';
$useDemoModal = true;
$extraStylesheets = ['css/shared.css', 'css/careers.css'];
$extraBodyScripts = [ep_versioned_asset('js/reveal.js')];
$epTrackPage = 'careers';

// ItemList of the open roles, so the listing page itself is understood as a
// set of job postings. Each individual JobPosting lives on its own detail
// page, which is where Google expects the full markup.
if ($jobs) {
    $itemList = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => []];
    foreach ($jobs as $i => $job) {
        $itemList['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => ep_job_url($job),
            'name' => (string) $job['title'],
        ];
    }
    $jsonLdSchema = json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$introHeading = ep_setting('careers_intro_heading', 'Build the future of school management');
$introText = ep_setting('careers_intro_text', 'We are a product team building EduPortal, the school ERP trusted by hundreds of institutes.');
$noJobsText = ep_setting('careers_no_jobs_text', 'We do not have any open positions right now. Send us your CV anyway — we review every general application.');
?>

  <main>
    <section class="careers-hero">
      <div class="careers-hero-inner">

        <div class="careers-hero-copy">
          <span class="section-label">Careers</span>
          <h1><?= ep_h($introHeading) ?></h1>
          <p class="careers-hero-lead"><?= ep_h($introText) ?></p>

          <a href="<?= ep_h(ep_url('team.php')) ?>" class="btn careers-hero-team-btn">
            Meet our Team
            <span class="careers-hero-team-btn-icon">
              <i data-lucide="arrow-right" aria-hidden="true"></i>
            </span>
          </a>
        </div>

        <div class="careers-hero-media">
          <img
            src="<?= ep_h(ep_url('assets/careers-team.jpg')) ?>"
            alt="The EduPortal team"
            width="960" height="340"
            fetchpriority="high" decoding="async">
        </div>

      </div>
    </section>

    <?php if ($justSubmitted): ?>
    <section class="section careers-thanks-section">
      <div class="container">
        <div class="apply-alert apply-alert--success" role="status" data-conversion="job_application">
          <h2>Thank you — your application has been received.</h2>
          <p>Our HR team reviews every application. If your profile matches what we are looking for, we will contact you on the email or WhatsApp number you provided.</p>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section careers-section">
      <div class="container">
        <?php if ($totalOpen > 0): ?>
        <div class="job-list">
          <?php foreach ($jobs as $job):
            $salary = ep_job_salary_display($job);
            $jobUrl = ep_job_url($job);
          ?>
          <article class="job-card<?= !empty($job['is_featured']) ? ' job-card--featured' : '' ?>">
            <div class="job-card-main">
              <div class="job-badges">
                <span class="job-badge job-badge--mode job-badge--mode-<?= ep_h(strtolower($job['work_mode'])) ?>"><?= ep_h($job['work_mode']) ?></span>
                <span class="job-badge job-badge--type"><?= ep_h($job['job_type']) ?></span>
                <?php if (!empty($job['is_featured'])): ?>
                <span class="job-badge job-badge--featured">Featured</span>
                <?php endif; ?>
              </div>
              <h3><a href="<?= ep_h($jobUrl) ?>"><?= ep_h($job['title']) ?></a></h3>
              <?php if (!empty($job['summary'])): ?>
              <p class="job-card-summary"><?= ep_h($job['summary']) ?></p>
              <?php endif; ?>
              <ul class="job-meta">
                <?php if (!empty($job['department'])): ?>
                <li><i data-lucide="layers" aria-hidden="true"></i><?= ep_h($job['department']) ?></li>
                <?php endif; ?>
                <?php if (!empty($job['location'])): ?>
                <li><i data-lucide="map-pin" aria-hidden="true"></i><?= ep_h($job['location']) ?></li>
                <?php endif; ?>
                <?php if ($salary !== ''): ?>
                <li><i data-lucide="wallet" aria-hidden="true"></i><?= ep_h($salary) ?></li>
                <?php endif; ?>
                <?php if (!empty($job['experience'])): ?>
                <li><i data-lucide="trending-up" aria-hidden="true"></i><?= ep_h($job['experience']) ?> experience</li>
                <?php endif; ?>
                <?php if (!empty($job['posted_at'])): ?>
                <li><i data-lucide="calendar" aria-hidden="true"></i>Posted <?= ep_h(ep_format_date((string) $job['posted_at'])) ?></li>
                <?php endif; ?>
              </ul>
            </div>
            <div class="job-card-aside">
              <?php if (!empty($job['deadline'])): ?>
              <span class="job-deadline">Apply by <?= ep_h(ep_format_date((string) $job['deadline'])) ?></span>
              <?php endif; ?>
              <a href="<?= ep_h($jobUrl) ?>" class="btn btn-primary">View &amp; Apply</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <?php if (!$showGeneralForm): ?>
        <div class="careers-cv-callout">
          <div>
            <h2>Don't see a role that fits?</h2>
            <p>We keep general applications on file and get in touch as soon as something matching opens up.</p>
          </div>
          <a href="<?= ep_h(ep_careers_url()) ?>?general=1#apply" class="btn btn-primary">Send us your CV</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="careers-empty careers-empty--none">
          <i data-lucide="briefcase" aria-hidden="true"></i>
          <h2>No open positions right now</h2>
          <p><?= ep_h($noJobsText) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="section careers-work">
      <div class="container">
        <div class="section-header reveal">
          <span class="section-label">The work</span>
          <h2>What you would be working on</h2>
        </div>
        <div class="careers-work-grid">
          <article class="careers-work-card">
            <i data-lucide="school" aria-hidden="true"></i>
            <h3>Software schools rely on daily</h3>
            <p>EduPortal runs attendance, fees, exams and parent communication for <?= ep_h(ep_site_metric('total_clients')) ?> institutes and <?= ep_h(ep_site_metric('total_students')) ?> students. What you ship is in front of administrators, teachers and parents the same week.</p>
          </article>
          <article class="careers-work-card">
            <i data-lucide="layout-grid" aria-hidden="true"></i>
            <h3>A broad product, not one screen</h3>
            <p>Twenty-six modules span admissions, finance, hostel, transport, library, payroll and the parent and teacher apps. There is real depth to work on rather than a single feature to maintain.</p>
          </article>
          <article class="careers-work-card">
            <i data-lucide="users" aria-hidden="true"></i>
            <h3>A small team, close to the product</h3>
            <p>We are a product team, so decisions are made quickly and you talk to the people using the software instead of hearing about them second-hand.</p>
          </article>
        </div>
      </div>
    </section>

    <?php if ($showGeneralForm): ?>
    <?php
    $job = null;
    require __DIR__ . '/includes/partials/application-form.php';
    ?>
    <?php endif; ?>
  </main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
