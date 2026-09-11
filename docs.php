<?php
/**
 * Public documentation — /docs and /docs/{slug}
 *
 * Step-by-step help articles, one per feature module. Each published guide
 * emits HowTo structured data for its steps and FAQPage data for its
 * questions, so the answers can surface in search directly — which is the
 * point: a parent or a school clerk who finds the answer in Google never
 * rings the support line.
 */
require_once __DIR__ . '/includes/cms.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

/* ---------------------------------------------------------------------
 * Index
 * ------------------------------------------------------------------ */
if ($slug === '') {
    $docs = ep_get_docs();

    $byCategory = [];
    foreach ($docs as $doc) {
        $byCategory[(string) $doc['category'] ?: 'Guides'][] = $doc;
    }

    $pageTitle        = 'EduPortal documentation — step-by-step guides';
    $pageDescription  = 'How to use every EduPortal module, written as step-by-step guides for school office staff, teachers and administrators.';
    $canonicalUrl     = 'https://eduportal.pk/docs';
    $navActive        = 'features';
    $useDemoModal     = true;
    $extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/docs.css'];
    $epTrackPage      = 'docs-index';
    if (!$docs) {
        $metaRobots = 'noindex, follow';
    }

    require __DIR__ . '/includes/head.php';
    require __DIR__ . '/includes/header.php';
    ?>
    <main>
      <section class="page-hero">
        <div class="container">
          <span class="section-label">Documentation</span>
          <h1>EduPortal guides</h1>
          <p>Step-by-step instructions for every module, written for the people who actually use them — office staff, teachers and administrators.</p>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <?php if (!$docs): ?>
          <div class="careers-empty">
            <p>The guides are being written. In the meantime, every module is documented on its feature page, and our team will walk you through anything you need.</p>
            <a href="<?= ep_h(ep_url('features.php')) ?>" class="btn btn-primary">Browse the modules</a>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-ghost">Ask our team</a>
          </div>
          <?php else: ?>
          <?php foreach ($byCategory as $category => $items): ?>
          <div class="docs-group">
            <h2><?= ep_h($category) ?></h2>
            <ul class="docs-list">
              <?php foreach ($items as $doc): ?>
              <li>
                <a href="<?= ep_h(ep_doc_url($doc)) ?>">
                  <span class="docs-list-title"><?= ep_h($doc['title']) ?></span>
                  <?php if (!empty($doc['summary'])): ?>
                  <span class="docs-list-summary"><?= ep_h($doc['summary']) ?></span>
                  <?php endif; ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>
    <?php
    $ctaLabel     = 'Need a hand';
    $ctaHeading   = 'Talk to someone who sets these up daily';
    $ctaText      = 'If the guides do not cover your case, our team will walk through it with you directly.';
    $ctaPrimary   = 'demo';
    $ctaSecondary = ['href' => ep_url('contact.php'), 'label' => 'Contact support'];
    $ctaNote      = '';
    require __DIR__ . '/includes/partials/cta-band.php';
    require __DIR__ . '/includes/partials/demo-modal.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---------------------------------------------------------------------
 * Article
 * ------------------------------------------------------------------ */
$doc = ep_get_doc_by_slug($slug);

if (!$doc) {
    http_response_code(404);
    $pageTitle        = 'Guide not found — EduPortal';
    $pageDescription  = 'This guide is not available. Browse all EduPortal documentation.';
    $canonicalUrl     = 'https://eduportal.pk/docs';
    $navActive        = 'features';
    $useDemoModal     = true;
    $extraStylesheets = ['css/shared.css', 'css/docs.css'];
    $metaRobots       = 'noindex, follow';
    $epTrackPage      = 'docs-not-found';
    require __DIR__ . '/includes/head.php';
    require __DIR__ . '/includes/header.php';
    ?>
    <main>
      <section class="page-hero">
        <div class="container">
          <h1>That guide is not available</h1>
          <p>It may not be written yet. Everything we have is listed on the documentation index.</p>
          <a href="<?= ep_h(ep_url('docs')) ?>" class="btn btn-primary">All guides</a>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/partials/demo-modal.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$steps = $doc['steps'];
$faqs = $doc['faqs'];

$pageTitle = trim((string) $doc['meta_title']) !== ''
    ? (string) $doc['meta_title']
    : (string) $doc['title'] . ' — EduPortal documentation';
$pageDescription = trim((string) $doc['meta_description']) !== ''
    ? (string) $doc['meta_description']
    : ((string) $doc['summary'] ?: 'Step-by-step guide to ' . $doc['title'] . ' in EduPortal.');
$canonicalUrl     = ep_doc_canonical($doc);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/docs.css'];
$extraBodyScripts = ['js/feature-detail.js'];
$epTrackPage      = 'docs:' . $doc['slug'];

// HowTo for the steps and FAQPage for the questions, in one graph.
$graph = [];
$howTo = ep_doc_howto_schema($doc, $steps);
if ($howTo) {
    $graph[] = $howTo;
}
$faqNode = ep_faq_page_schema(
    array_map(static fn(array $f): array => ['q' => $f['q'] ?? '', 'a' => $f['a'] ?? ''], $faqs),
    $canonicalUrl
);
if ($faqNode) {
    $graph[] = $faqNode;
}
if ($graph) {
    $jsonLdSchema = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );
}

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero docs-hero">
      <div class="container">
        <nav class="job-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= ep_h(ep_url('docs')) ?>">Documentation</a>
          <span aria-hidden="true">/</span>
          <span><?= ep_h($doc['title']) ?></span>
        </nav>
        <h1><?= ep_h($doc['title']) ?></h1>
        <?php if (!empty($doc['summary'])): ?>
        <p><?= ep_h($doc['summary']) ?></p>
        <?php endif; ?>
      </div>
    </section>

    <section class="section docs-body">
      <div class="container docs-layout">
        <article class="docs-article">
          <?php if (!empty($doc['intro_html'])): ?>
          <div class="docs-intro"><?= $doc['intro_html'] ?></div>
          <?php endif; ?>

          <?php if ($steps): ?>
          <h2>Step by step</h2>
          <ol class="docs-steps">
            <?php foreach ($steps as $step): ?>
            <li>
              <h3><?= ep_h((string) ($step['title'] ?? '')) ?></h3>
              <?php if (trim((string) ($step['body'] ?? '')) !== ''): ?>
              <div class="docs-step-body"><?= $step['body'] ?></div>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
          <?php endif; ?>

          <?php if (!empty($doc['tips_html'])): ?>
          <div class="docs-tips">
            <h2>Things worth knowing</h2>
            <?= $doc['tips_html'] ?>
          </div>
          <?php endif; ?>

          <?php if ($faqs): ?>
          <h2>Common questions</h2>
          <div class="fd-faq-list">
            <?php ep_render_faq_accordion(array_map(
                static fn(array $f): array => ['q' => $f['q'] ?? '', 'a' => $f['a'] ?? ''],
                $faqs
            )); ?>
          </div>
          <?php endif; ?>
        </article>

        <aside class="docs-aside">
          <?php
          $featureScript = (string) ($doc['feature_script'] ?? '');
          $featureSlug = $featureScript !== '' ? ep_feature_slug($featureScript) : '';
          ?>
          <?php if ($featureSlug !== ''): ?>
          <div class="docs-aside-card">
            <h2>About this module</h2>
            <p>Full details of what this module does, who it is for, and what it costs.</p>
            <a href="<?= ep_h(ep_feature_url($featureScript)) ?>" class="btn btn-outline btn-sm">Read the module page</a>
          </div>
          <?php endif; ?>

          <div class="docs-aside-card">
            <h2>Still stuck?</h2>
            <p>If this guide did not answer your question, our support team would rather hear from you than have you guess.</p>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-primary btn-sm">Contact support</a>
          </div>
        </aside>
      </div>
    </section>
  </main>

<?php
require __DIR__ . '/includes/partials/demo-modal.php';
require __DIR__ . '/includes/footer.php';
