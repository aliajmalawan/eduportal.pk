<?php
require_once __DIR__ . '/includes/cms.php';

$navActive   = 'case-studies';
$useDemoModal = true;
$studies     = ep_get_case_studies();
$pageTitle   = 'Case Studies — School ERP Success Stories | EduPortal Pakistan';
$pageDescription = 'Real EduPortal case studies from schools across Pakistan — fees, attendance, parent apps and measurable ERP results.';
$canonicalUrl    = 'https://eduportal.pk/case-studies';
$extraStylesheets = ['css/shared.css', 'css/case-studies.css'];
$extraHeadScripts = ['js/country-codes.js', 'js/lead-form.js'];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="cs-hero">
      <div class="container">
        <span class="section-label">Case Studies</span>
        <h1>Real Results from Real Schools</h1>
        <p>Discover how schools across Pakistan use EduPortal to streamline operations, collect fees faster, and engage parents — with measurable impact.</p>
      </div>
    </section>

    <section class="cs-grid-section">
      <div class="container">
        <?php if (!$studies): ?>
        <p class="cs-empty">No case studies published yet.</p>
        <?php else: ?>
        <div class="cs-grid">
          <?php foreach ($studies as $cs):
            $url      = ep_case_study_url($cs);
            $thumbUrl = ep_case_study_thumbnail_url($cs);
            $excerpt  = ep_case_study_excerpt($cs, 140);
            $pubDate  = !empty($cs['published_at']) ? date('M Y', strtotime((string) $cs['published_at'])) : '';
          ?>
          <article class="cs-card reveal-cs">
            <a href="<?= ep_h($url) ?>" class="cs-card-thumb" aria-hidden="true" tabindex="-1">
              <?php if ($thumbUrl): ?>
              <img src="<?= ep_h($thumbUrl) ?>" alt="<?= ep_h($cs['name']) ?>" width="600" height="360" loading="lazy" decoding="async">
              <?php else: ?>
              <div class="cs-card-thumb-placeholder">
                <i data-lucide="book-open"></i>
              </div>
              <?php endif; ?>
              <?php if (!empty($cs['video_youtube_id'])): ?>
              <span class="cs-card-video-badge"><i data-lucide="play-circle"></i> Video</span>
              <?php endif; ?>
            </a>
            <div class="cs-card-body">
              <?php if ($pubDate): ?><span class="cs-card-date"><?= ep_h($pubDate) ?></span><?php endif; ?>
              <h2><a href="<?= ep_h($url) ?>"><?= ep_h($cs['name']) ?></a></h2>
              <?php if ($excerpt): ?><p class="cs-card-excerpt"><?= ep_h($excerpt) ?></p><?php endif; ?>
              <a href="<?= ep_h($url) ?>" class="cs-card-link">
                Read case study <i data-lucide="arrow-right"></i>
              </a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php
$epTrackPage = 'case-studies-list';
$extraBodyScripts = ['js/cs-reveal.js'];
require __DIR__ . '/includes/footer.php';
