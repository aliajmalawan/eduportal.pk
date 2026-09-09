<?php
/**
 * Comparison pages — /compare and /compare/eduportal-vs-{competitor}
 *
 * Renders an honest side-by-side table from ep_comparisons + ep_comparison_rows.
 * Rows where the competitor wins are marked as such and are not hidden: a
 * comparison that concedes nothing reads as advertising, which is exactly what
 * these pages exist to avoid. Every published comparison also states when its
 * competitor facts were last verified, and links the source.
 */
require_once __DIR__ . '/includes/cms.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

/* ---------------------------------------------------------------------
 * Index: /compare
 * ------------------------------------------------------------------ */
if ($slug === '') {
    $comparisons = ep_get_comparisons();

    $pageTitle        = 'Compare EduPortal with other school ERP software | EduPortal';
    $pageDescription  = 'Side-by-side comparisons of EduPortal against other school management systems used in Pakistan, including where the alternative is the better choice.';
    $canonicalUrl     = 'https://eduportal.pk/compare';
    $navActive        = 'features';
    $useDemoModal     = true;
    $extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/compare.css'];
    $epTrackPage      = 'compare-index';
    if (!$comparisons) {
        // Nothing verified yet — keep the empty page out of the index.
        $metaRobots = 'noindex, follow';
    }

    require __DIR__ . '/includes/head.php';
    require __DIR__ . '/includes/header.php';
    ?>
    <main>
      <section class="page-hero">
        <div class="container">
          <span class="section-label">Compare</span>
          <h1>EduPortal compared with other school ERPs</h1>
          <p>Straight side-by-side tables, including the points where the other system is the better fit. Every comparison shows when we last checked the other product.</p>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <?php if (!$comparisons): ?>
          <div class="careers-empty">
            <p>No comparisons are published yet. We only publish one once we have verified what the other product actually does.</p>
            <a href="<?= ep_h(ep_url('features.php')) ?>" class="btn btn-primary">See what EduPortal does</a>
          </div>
          <?php else: ?>
          <div class="cmp-index">
            <?php foreach ($comparisons as $cmp): ?>
            <a class="cmp-index-card" href="<?= ep_h(ep_comparison_url($cmp)) ?>">
              <h2>EduPortal vs <?= ep_h($cmp['competitor_name']) ?></h2>
              <?php if (!empty($cmp['competitor_summary'])): ?>
              <p><?= ep_h(mb_substr((string) $cmp['competitor_summary'], 0, 160)) ?></p>
              <?php endif; ?>
              <span class="cmp-index-cta">Read the comparison <i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/partials/demo-modal.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---------------------------------------------------------------------
 * Detail: /compare/{slug}
 * ------------------------------------------------------------------ */
$cmp = ep_get_comparison_by_slug($slug);

if (!$cmp) {
    http_response_code(404);
    $pageTitle        = 'Comparison not found — EduPortal';
    $pageDescription  = 'This comparison is not available. See all EduPortal comparisons.';
    $canonicalUrl     = 'https://eduportal.pk/compare';
    $navActive        = 'features';
    $useDemoModal     = true;
    $extraStylesheets = ['css/shared.css', 'css/compare.css'];
    $metaRobots       = 'noindex, follow';
    $epTrackPage      = 'compare-not-found';
    require __DIR__ . '/includes/head.php';
    require __DIR__ . '/includes/header.php';
    ?>
    <main>
      <section class="page-hero">
        <div class="container">
          <h1>That comparison is not available</h1>
          <p>We publish a comparison only once we have checked the other product ourselves.</p>
          <a href="<?= ep_h(ep_url('compare')) ?>" class="btn btn-primary">All comparisons</a>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/partials/demo-modal.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$rows = $cmp['rows'];
$competitor = (string) $cmp['competitor_name'];

// Group rows by their optional section heading, preserving order.
$sections = [];
foreach ($rows as $row) {
    $sections[(string) $row['section']][] = $row;
}

$wins = ['eduportal' => 0, 'competitor' => 0, 'tie' => 0];
foreach ($rows as $row) {
    $key = (string) $row['advantage'];
    if (isset($wins[$key])) {
        $wins[$key]++;
    }
}

$pageTitle = trim((string) $cmp['meta_title']) !== ''
    ? (string) $cmp['meta_title']
    : 'EduPortal vs ' . $competitor . ' — honest comparison | EduPortal';
$pageDescription = trim((string) $cmp['meta_description']) !== ''
    ? (string) $cmp['meta_description']
    : 'Side-by-side comparison of EduPortal and ' . $competitor . ' for Pakistani schools, including where ' . $competitor . ' is the better choice.';
$canonicalUrl     = ep_comparison_canonical($cmp);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/compare.css'];
$epTrackPage      = 'compare:' . $cmp['slug'];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="job-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= ep_h(ep_url('compare')) ?>">Compare</a>
          <span aria-hidden="true">/</span>
          <span>EduPortal vs <?= ep_h($competitor) ?></span>
        </nav>
        <h1>EduPortal vs <?= ep_h($competitor) ?></h1>
        <?php if (!empty($cmp['intro_html'])): ?>
        <div class="cmp-intro"><?= $cmp['intro_html'] ?></div>
        <?php endif; ?>

        <?php if (!empty($cmp['verified_at'])): ?>
        <p class="cmp-verified">
          <i data-lucide="shield-check" aria-hidden="true"></i>
          <?= ep_h($competitor) ?> details last checked <?= ep_h(ep_format_date((string) $cmp['verified_at'])) ?>.
          <?php if (!empty($cmp['source_url'])): ?>
          <a href="<?= ep_h((string) $cmp['source_url']) ?>" target="_blank" rel="nofollow noopener">Source</a>.
          <?php endif; ?>
        </p>
        <?php endif; ?>
      </div>
    </section>

    <?php if (!empty($cmp['competitor_summary'])): ?>
    <section class="section cmp-about">
      <div class="container">
        <h2>What <?= ep_h($competitor) ?> is</h2>
        <p><?= ep_h((string) $cmp['competitor_summary']) ?></p>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($rows): ?>
    <section class="section" id="table">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Side by side</span>
          <h2>EduPortal compared with <?= ep_h($competitor) ?></h2>
          <p class="cmp-tally">
            Of <?= count($rows) ?> points compared:
            EduPortal ahead on <?= (int) $wins['eduportal'] ?>,
            <?= ep_h($competitor) ?> ahead on <?= (int) $wins['competitor'] ?>,
            level on <?= (int) $wins['tie'] ?>.
          </p>
        </div>

        <div class="cmp-table-wrap">
          <table class="cmp-table">
            <thead>
              <tr>
                <th scope="col">Feature</th>
                <th scope="col">EduPortal</th>
                <th scope="col"><?= ep_h($competitor) ?></th>
              </tr>
            </thead>
            <?php foreach ($sections as $sectionName => $sectionRows): ?>
            <tbody>
              <?php if ($sectionName !== ''): ?>
              <tr class="cmp-section-row"><th colspan="3" scope="colgroup"><?= ep_h($sectionName) ?></th></tr>
              <?php endif; ?>
              <?php foreach ($sectionRows as $row):
                $adv = (string) $row['advantage'];
              ?>
              <tr>
                <th scope="row">
                  <?= ep_h((string) $row['feature_label']) ?>
                  <?php if (!empty($row['note'])): ?>
                  <span class="cmp-note"><?= ep_h((string) $row['note']) ?></span>
                  <?php endif; ?>
                </th>
                <td class="<?= $adv === 'eduportal' ? 'cmp-win' : '' ?>">
                  <?= ep_h((string) $row['eduportal_value']) ?>
                  <?php if ($adv === 'eduportal'): ?><span class="cmp-flag">Better</span><?php endif; ?>
                </td>
                <td class="<?= $adv === 'competitor' ? 'cmp-win' : '' ?>">
                  <?= ep_h((string) $row['competitor_value']) ?>
                  <?php if ($adv === 'competitor'): ?><span class="cmp-flag">Better</span><?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($cmp['where_they_win_html'])): ?>
    <section class="section cmp-their-win">
      <div class="container">
        <h2>Where <?= ep_h($competitor) ?> is the better choice</h2>
        <div class="cmp-their-win-body"><?= $cmp['where_they_win_html'] ?></div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($cmp['verdict_html'])): ?>
    <section class="section cmp-verdict">
      <div class="container">
        <h2>Which should you pick?</h2>
        <div><?= $cmp['verdict_html'] ?></div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section cmp-cta">
      <div class="container">
        <h2>See EduPortal for yourself</h2>
        <p>The fastest way to compare is to look at both. Our pricing is published in full, so you can check the numbers before you talk to anyone.</p>
        <div class="cmp-cta-actions">
          <button type="button" class="btn btn-primary js-open-modal">Book a demo</button>
          <a href="<?= ep_h(ep_url('pricing')) ?>" class="btn btn-outline">See pricing</a>
        </div>
      </div>
    </section>
  </main>

<?php
require __DIR__ . '/includes/partials/demo-modal.php';
require __DIR__ . '/includes/footer.php';
