<?php
require_once __DIR__ . '/includes/cms.php';

$navActive = 'videos';
$videos = ep_get_video_testimonials(false);
$count = count($videos);
$pageTitle = 'Video Reviews — Schools Using EduPortal ERP';
$pageDescription = 'Watch EduPortal video reviews from school principals, owners and directors across Pakistan.';
$canonicalUrl = ep_canonical_url();
$extraStylesheets = ['css/shared.css', 'css/video-cards.css'];
$extraHeadScripts = [ep_versioned_asset('js/video-thumbs.js')];
$extraBodyScripts = [ep_versioned_asset('js/videos-page.js'), ep_versioned_asset('js/reveal.js')];
$videoPlayback = [];
foreach ($videos as $v) {
    $vid = (int) ($v['id'] ?? 0);
    $playUrl = ep_video_file_url($v);
    if ($vid > 0 && $playUrl !== '') {
        $videoPlayback[(string) $vid] = $playUrl;
    }
}
$inlineStyles = <<<'CSS'
.nav-links a.active, .mobile-menu a.active { color: var(--color-primary); font-weight: 600; }
.page-hero { position: relative; }
.page-hero::before {
  content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
  width: min(600px, 90%); height: 200px;
  background: radial-gradient(ellipse at center, rgba(242,140,40,.12) 0%, transparent 70%);
  pointer-events: none;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem;
  background: var(--color-primary-light); color: var(--color-primary-dark);
  font-size: 0.8125rem; font-weight: 600; border-radius: 999px; margin-bottom: 1rem;
}
.videos-section { padding: 0 0 4rem; }
CSS;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="hero-badge"><i data-lucide="video" style="width:14px;height:14px"></i> Video Reviews</span>
        <h1><?= $count ?>+ Real Video Reviews</h1>
        <p>Hear directly from school leaders who transformed their schools with EduPortal</p>
      </div>
    </section>

    <section class="videos-section section">
      <div class="container">
        <div class="video-filters" role="tablist" aria-label="Filter reviews">
          <button type="button" class="filter-pill active" data-filter="all" aria-pressed="true">All</button>
          <button type="button" class="filter-pill" data-filter="principal" aria-pressed="false">Principals</button>
          <button type="button" class="filter-pill" data-filter="owner" aria-pressed="false">Owners</button>
          <button type="button" class="filter-pill" data-filter="director" aria-pressed="false">Directors</button>
        </div>

        <div class="video-grid" id="videoGrid">
          <?php if (!$videos): ?>
          <p style="grid-column:1/-1;text-align:center;color:var(--color-text-muted);">No video reviews published yet.</p>
          <?php else: foreach ($videos as $v):
            $role = $v['role_filter'] ?: 'other';
            $vType = ep_video_type($v);
            $thumbUrl = ep_video_thumbnail_url($v);
            $fileUrl = ep_video_file_url($v);
            $fileRel = ep_video_file_path_relative($v);
            if ($vType === 'upload' && $fileUrl === '') {
                $vType = 'youtube';
            }
            $subtitle = ep_video_subtitle($v);
            $label = 'Play video review from ' . $v['person_name'];
            $recordId = (int) ($v['id'] ?? 0);
          ?>
          <?php
              // Real destination when one exists, so the card works without JS
              // (the modal script preventDefault()s over the top). Uploaded
              // files are only linked when the file is actually on disk —
              // several rows reference files that are missing, and pointing at
              // those would emit a 404 link. With no target the card stays a
              // JS-only control rather than a dead "#".
              $cardHref = '';
              if ($vType === 'youtube' && !empty($v['youtube_video_id'])) {
                  $cardHref = 'https://www.youtube.com/watch?v=' . $v['youtube_video_id'];
              } elseif ($fileUrl !== '' && $fileRel !== '' && is_file(__DIR__ . '/' . ltrim($fileRel, '/'))) {
                  $cardHref = $fileUrl;
              }
            ?>
            <a<?= $cardHref !== '' ? ' href="' . ep_h($cardHref) . '"' : ' role="button" tabindex="0"' ?> class="video-card reveal"
             data-video-id="<?= $recordId ?>"
             data-role="<?= ep_h($role) ?>"
             data-video-type="<?= ep_h($vType) ?>"
             data-youtube-id="<?= ep_h($v['youtube_video_id'] ?? '') ?>"
             data-youtube-start="<?= (int) ($v['youtube_start_seconds'] ?? 0) ?>"
             data-video-src="<?= ep_h($fileUrl) ?>"
             data-video-path="<?= ep_h($fileRel) ?>"
             data-has-thumb="<?= $thumbUrl ? '1' : '0' ?>"
             aria-label="<?= ep_h($label) ?>">
            <div class="video-thumb"<?= $thumbUrl ? ' data-custom-thumb="1"' : '' ?>>
              <?php if ($thumbUrl): ?>
              <img src="<?= ep_h($thumbUrl) ?>" alt="<?= ep_h($v['person_name']) ?>" width="480" height="320" loading="lazy" decoding="async">
              <?php endif; ?>
            </div>
            <span class="video-play" aria-hidden="true"><span class="play-pulse"></span><i data-lucide="play"></i></span>
            <div class="video-meta">
              <strong><?= ep_h($v['person_name']) ?></strong>
              <span><?= ep_h($subtitle) ?></span>
              <?php if (!empty($v['description'])): ?>
              <span class="testimonial-quote"><?= ep_h($v['description']) ?></span>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </section>
  </main>

  <div class="video-modal-overlay" id="videoModal" role="dialog" aria-modal="true" aria-label="Video review" aria-hidden="true">
    <div class="video-modal">
      <button type="button" class="video-modal-close" id="videoClose" aria-label="Close video"><i data-lucide="x"></i></button>
      <iframe id="youtubeFrame" title="EduPortal video review" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      <video id="html5VideoPlayer" class="ep-html5-video" controls playsinline preload="metadata" style="display:none"></video>
    </div>
  </div>

  <script>
    window.EP_SITE_BASE = <?= json_encode(rtrim(defined('DOMAIN') ? DOMAIN : '', '/') . '/', JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script type="application/json" id="epVideoPlaybackData"><?= json_encode($videoPlayback, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php
$epTrackPage = 'videos-list';
require __DIR__ . '/includes/footer.php';
