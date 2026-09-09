<?php
require_once __DIR__ . '/includes/cms.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$cs   = $slug ? ep_get_case_study_by_slug($slug) : null;

if (!$cs) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><title>Not found</title></head><body><h1>Case study not found</h1><p><a href="' . ep_h(ep_url('case-studies')) . '">All case studies</a></p></body></html>';
    exit;
}

$navActive    = 'case-studies';
$useDemoModal = true;
$thumbUrl     = ep_case_study_thumbnail_url($cs);
$excerpt      = ep_case_study_excerpt($cs, 200);
$pubDate      = !empty($cs['published_at']) ? date('d M Y', strtotime((string) $cs['published_at'])) : '';
$pageTitle    = $cs['name'] . ' — Case Study | EduPortal';
$pageDescription  = $excerpt ?: ($cs['name'] . ' case study — EduPortal');
$canonicalUrl     = ep_case_study_canonical($cs);
$extraStylesheets = ['css/shared.css', 'css/case-studies.css', 'css/blog-post.css'];
$extraHeadScripts = ['js/country-codes.js', 'js/lead-form.js'];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main class="cs-post">
    <div class="container">

      <a href="<?= ep_h(ep_url('case-studies')) ?>" class="cs-post-back">
        <i data-lucide="arrow-left"></i> All case studies
      </a>

      <header class="cs-post-header">
        <span class="section-label">Case Study</span>
        <h1><?= ep_h($cs['name']) ?></h1>
        <?php
        // Who the school is, stated plainly up front — the facts a reader
        // needs before any claim about results means anything.
        $facts = array_filter([
            'map-pin'      => trim((string) ($cs['city'] ?? '')),
            'users'        => trim((string) ($cs['students_label'] ?? '')) !== ''
                                ? $cs['students_label'] . ' students' : '',
            'building-2'   => trim((string) ($cs['school_type'] ?? '')),
        ]);
        ?>
        <?php if ($facts || $pubDate): ?>
        <div class="cs-post-meta">
          <?php foreach ($facts as $icon => $value): ?>
          <span><i data-lucide="<?= ep_h($icon) ?>"></i> <?= ep_h($value) ?></span>
          <?php endforeach; ?>
          <?php if ($pubDate): ?>
          <span><i data-lucide="calendar"></i> <?= ep_h($pubDate) ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </header>

      <?php if ($thumbUrl): ?>
      <div class="cs-post-hero">
        <img src="<?= ep_h($thumbUrl) ?>" alt="<?= ep_h($cs['name']) ?>" width="1200" height="630" loading="eager" decoding="async">
      </div>
      <?php endif; ?>

      <?php if (!empty($cs['video_youtube_id'])): ?>
      <div class="cs-post-video">
        <div class="cs-video-wrap">
          <iframe
            src="https://www.youtube.com/embed/<?= ep_h($cs['video_youtube_id']) ?>?rel=0"
            title="<?= ep_h($cs['name']) ?> — EduPortal Case Study"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            loading="lazy"
          ></iframe>
        </div>
      </div>
      <?php endif; ?>

      <?php
      // Results with real numbers. Only rendered when the school has actually
      // confirmed figures — an empty metrics band is better than an invented one.
      $metrics = $cs['metrics'] ?? [];
      if ($metrics):
      ?>
      <section class="cs-metrics" aria-label="Results">
        <?php foreach ($metrics as $metric): ?>
        <div class="cs-metric">
          <span class="cs-metric-value"><?= ep_h($metric['metric_value']) ?></span>
          <span class="cs-metric-label"><?= ep_h($metric['metric_label']) ?></span>
        </div>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>

      <?php
      // The three story blocks. Each is optional: a study with only some of
      // them written still renders cleanly rather than showing empty headings.
      $story = [
          ['title' => 'The challenge', 'icon' => 'alert-circle',  'html' => trim((string) ($cs['challenges_html'] ?? ''))],
          ['title' => 'What we set up', 'icon' => 'settings',     'html' => trim((string) ($cs['why_chosen_html'] ?? ''))],
          ['title' => 'The results',    'icon' => 'trending-up',  'html' => trim((string) ($cs['results_html'] ?? ''))],
      ];
      $story = array_filter($story, static fn(array $b): bool => $b['html'] !== '');
      ?>
      <?php if ($story): ?>
      <div class="cs-story">
        <?php foreach ($story as $block): ?>
        <section class="cs-story-block">
          <h2><i data-lucide="<?= ep_h($block['icon']) ?>" aria-hidden="true"></i> <?= ep_h($block['title']) ?></h2>
          <div class="cs-story-body"><?= $block['html'] ?></div>
        </section>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php
      $quote = trim((string) ($cs['quote'] ?? ''));
      $quoteName = trim((string) ($cs['contact_name'] ?? ''));
      $quoteRole = trim((string) ($cs['contact_role'] ?? ''));
      if ($quote !== ''):
      ?>
      <figure class="cs-quote">
        <i data-lucide="quote" class="cs-quote-mark" aria-hidden="true"></i>
        <blockquote><?= ep_h($quote) ?></blockquote>
        <?php if ($quoteName !== ''): ?>
        <figcaption>
          <strong><?= ep_h($quoteName) ?></strong>
          <?php if ($quoteRole !== ''): ?><span><?= ep_h($quoteRole) ?></span><?php endif; ?>
        </figcaption>
        <?php endif; ?>
      </figure>
      <?php endif; ?>

      <?php if (!empty($cs['description'])): ?>
      <article class="cs-post-content blog-post-content" id="csArticle">
        <?= $cs['description'] ?>
      </article>
      <script>
        (function () {
          var article = document.getElementById('csArticle');
          if (!article) return;
          article.querySelectorAll('table').forEach(function (tbl) {
            if (tbl.parentElement.classList.contains('table-wrap')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-wrap';
            tbl.parentNode.insertBefore(wrap, tbl);
            wrap.appendChild(tbl);
          });
        })();
      </script>
      <?php endif; ?>

      <div class="cs-post-cta">
        <h3>See EduPortal in action</h3>
        <p>Want similar results for your school? Get a live demo today.</p>
        <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-primary">Get Started Free</a>
      </div>

    </div>
  </main>

<?php
$epTrackPage = 'case-study:' . ($cs['slug'] ?? 'unknown');
require __DIR__ . '/includes/footer.php';
