<?php
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/faqs-data.php';

$pageTitle        = 'Frequently Asked Questions — School ERP Pakistan | EduPortal';
$pageDescription  = '56+ FAQs about EduPortal school ERP — pricing, attendance, face recognition, parent app, WhatsApp, exams, cloud hosting &amp; data security for schools in Pakistan.';
$canonicalUrl     = ep_canonical_url();
$navActive        = 'home';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/faqs.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js', 'js/faqs.js'];
$epTrackPage      = 'faqs';

$faqCategories      = ep_faq_categories();
$faqs               = ep_faqs();
$faqCategoryLabels  = array_column($faqCategories, 'label', 'id');

$jsonLdSchema = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'FAQPage',
            '@id' => 'https://eduportal.pk/faqs.php#faq',
            'mainEntity' => array_map(static function (array $faq): array {
                return [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['a'],
                    ],
                ];
            }, $faqs),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

$extraHeadHtml = <<<'HTML'
  <meta property="og:title" content="EduPortal FAQs — School Management Software Pakistan">
  <meta property="og:description" content="Answers for school owners about EduPortal ERP — fees, attendance, apps, WhatsApp, pricing &amp; cloud.">
HTML;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="faq-hero">
      <div class="container">
        <span class="section-label">Help Center</span>
        <h1>Frequently Asked Questions</h1>
        <p>Clear answers for school owners, principals, and administrators evaluating EduPortal school management software in Pakistan — optimized for Google and AI search.</p>
        <div class="faq-search-wrap">
          <i data-lucide="search"></i>
          <input type="search" id="faqSearch" class="faq-search" placeholder="Search FAQs — e.g. fee cost, WhatsApp, face attendance..." aria-label="Search FAQs">
        </div>
        <p class="faq-search-meta" id="faqCount"><?= count($faqs) ?> questions</p>
      </div>
    </section>

    <section class="faq-page-body">
      <div class="container">
        <div class="faq-tabs" id="faqTabs" role="tablist" aria-label="FAQ categories">
          <?php foreach ($faqCategories as $cat): ?>
          <button type="button" class="faq-tab<?= $cat['id'] === 'all' ? ' is-active' : '' ?>" data-cat="<?= ep_h($cat['id']) ?>">
            <?php if (!empty($cat['icon'])): ?><i data-lucide="<?= ep_h($cat['icon']) ?>"></i><?php endif; ?><?= ep_h($cat['label']) ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div class="faq-list" id="faqList" aria-live="polite">
          <?php foreach ($faqs as $faq): ?>
          <div class="faq-item" data-id="<?= ep_h($faq['id']) ?>" data-category="<?= ep_h($faq['category']) ?>" data-search="<?= ep_h(mb_strtolower($faq['q'] . ' ' . $faq['a'])) ?>">
            <h3><button type="button" class="faq-q" aria-expanded="false">
              <span><?= ep_h($faq['q']) ?></span><i data-lucide="chevron-down"></i>
            </button></h3>
            <div class="faq-a"><div class="faq-a-inner"><span class="faq-tag"><?= ep_h($faqCategoryLabels[$faq['category']] ?? $faq['category']) ?></span><p><?= ep_h($faq['a']) ?></p></div></div>
          </div>
          <?php endforeach; ?>
          <div class="faq-empty" id="faqEmpty" hidden>
            <i data-lucide="search-x"></i>
            <p>No FAQs match your search. Try different keywords or browse all categories.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="faq-cta">
      <div class="container">
        <h2>Still have questions?</h2>
        <p>Book a free demo with our team or contact sales — we will walk through EduPortal with your school structure and answer everything live.</p>
        <div class="faq-cta-btns">
          <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
          <a href="contact.php" class="btn-outline-light">Contact Sales</a>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
