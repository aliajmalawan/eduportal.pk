<?php
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/careers.php';

$pageTitle       = 'Team Members — EduPortal';
$pageDescription = 'Meet the people building EduPortal, the school ERP trusted by hundreds of institutes across Pakistan.';
$canonicalUrl    = ep_canonical_url();
$navActive       = 'careers';
$useDemoModal    = true;
$extraStylesheets = ['css/shared.css', 'css/team.css'];
$extraBodyScripts = [ep_versioned_asset('js/parallax.js'), ep_versioned_asset('js/reveal.js')];
$epTrackPage     = 'team';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$teamMembers = ep_get_team_members(true);

$officeCity   = trim((string) ep_setting('office_city', ''));
$officeRegion = trim((string) ep_setting('office_region', ''));

/** First-letter avatar fallback for a member with no photo uploaded yet. */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_filter($parts);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr((string) reset($parts), 0, 1);
    $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};
?>

  <main>

    <section class="page-hero team-hero">
      <?php // The image is parallaxed, so it needs overscan: js/parallax.js
            // translates it by up to 60px either way, and with the image sized
            // exactly to its frame that movement exposed a strip of the white
            // hero background at one edge. The wrapper does the clipping (still
            // starting below the navbar, so the nav keeps a plain backdrop) while
            // the image inside overscans past it. ?>
      <span class="team-hero-media" aria-hidden="true">
        <img class="team-hero-bg" src="<?= ep_h(ep_url('assets/careers-team.jpg')) ?>" alt="" loading="eager" decoding="async" data-parallax="0.12">
      </span>
      <span class="team-hero-overlay" aria-hidden="true"></span>
      <div class="container">
        <span class="section-label">Our People</span>
        <h1>The People Behind EduPortal</h1>
        <p>A small, product-focused team building the school ERP trusted by hundreds of institutes across Pakistan — and the people you'll actually talk to if you join us.</p>
        <?php if ($officeCity !== ''): ?>
        <span class="team-hero-tag">
          <i data-lucide="map-pin" aria-hidden="true"></i>
          <?= ep_h($officeRegion !== '' ? $officeCity . ', ' . $officeRegion : $officeCity) ?>
        </span>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($teamMembers): ?>
    <section class="section team-grid-section">
      <div class="container">
        <div class="section-heading reveal">
          <span class="section-label">Meet the team</span>
          <h2 class="section-title">The people making it happen</h2>
        </div>
        <div class="team-grid">
          <?php foreach ($teamMembers as $person):
            $personName = (string) $person['name'];
            $personRole = trim((string) $person['designation']);
            $personPhoto = trim((string) $person['photo_path']);
          ?>
          <div class="team-card reveal">
            <div class="team-card-photo">
              <?php if ($personPhoto !== ''): ?>
              <img src="<?= ep_h(ep_url($personPhoto)) ?>" alt="<?= ep_h($personName) ?>" loading="lazy" decoding="async">
              <?php else: ?>
              <span class="team-card-initials" aria-hidden="true"><?= ep_h($initials($personName)) ?></span>
              <?php endif; ?>
              <span class="team-card-scrim" aria-hidden="true"></span>
              <div class="team-card-info">
                <h3><?= ep_h($personName) ?></h3>
                <?php if ($personRole !== ''): ?>
                <p><?= ep_h($personRole) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section team-cta">
      <div class="container">
        <span class="section-label">Join us</span>
        <h2>Want to work with us?</h2>
        <p>We are always looking for people who care about clean software and real-world impact in education.</p>
        <a href="<?= ep_h(ep_careers_url()) ?>" class="btn btn-primary btn-lg">
          View Open Positions
          <i data-lucide="arrow-right" aria-hidden="true"></i>
        </a>
      </div>
    </section>

  </main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
