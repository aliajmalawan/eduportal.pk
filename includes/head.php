<?php
/** @var string $pageTitle */
/** @var string|null $pageDescription */
/** @var string|null $canonicalUrl */
/** @var array<int, string> $extraStylesheets */
/** @var array<int, string> $extraHeadScripts */
/** @var string $inlineStyles */
/** @var string $bodyClass */
$pageDescription = $pageDescription ?? '';
$canonicalUrl = $canonicalUrl ?? '';
$extraStylesheets = $extraStylesheets ?? ['css/shared.css'];
$extraHeadScripts = $extraHeadScripts ?? [];
$inlineStyles = $inlineStyles ?? '';
$bodyClass = trim($bodyClass ?? '');
$jsonLdSchema = $jsonLdSchema ?? '';
$extraHeadHtml = $extraHeadHtml ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($pageDescription !== ''): ?>
  <meta name="description" content="<?= ep_h($pageDescription) ?>">
  <?php endif; ?>
  <?php // Pages that must not be indexed set $metaRobots before including this. ?>
  <meta name="robots" content="<?= ep_h($metaRobots ?? 'index, follow') ?>">
  <?php if ($canonicalUrl !== ''): ?>
  <link rel="canonical" href="<?= ep_h($canonicalUrl) ?>">
  <?php endif; ?>
  <title><?= ep_h($pageTitle) ?></title>
  <link rel="icon" href="<?= ep_h(ep_url('assets/logo_icon.jpg')) ?>" type="image/jpeg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <?php
  // Any page that renders the Get Started modal needs its styles. Loading it
  // here, off the same flag that renders the partial, means a page can never
  // again show the modal as unstyled markup just because its own stylesheet
  // happened not to carry the rules. Skipped when a stylesheet that already
  // contains them is present, so nothing is loaded twice.
  if (!empty($useDemoModal)) {
      $hasModalCss = false;
      foreach ($extraStylesheets as $sheet) {
          if (preg_match('#(index|feature-detail|landing|pricing|demo-modal)\.(min\.)?css#', (string) $sheet)) {
              $hasModalCss = true;
              break;
          }
      }
      if (!$hasModalCss) {
          $extraStylesheets[] = 'css/demo-modal.css';
      }
  }
  ?>
  <?php foreach ($extraStylesheets as $href): ?>
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url($href)) ?>">
  <?php endforeach; ?>
  <?php // Without JavaScript: open the FAQ accordions, cancel the scroll-reveal
        // animations, and — below 992px — lay the mobile menu out inline. The
        // desktop nav is display:none under that width and the mobile drawer
        // only slides in when JS adds .open, so with JS off there was no way
        // to navigate the site on a phone at all. ?>
  <noscript><style>.faq-a,.home-faq-a,.fd-faq-a{max-height:none!important}.reveal,.reveal-fd{opacity:1!important;transform:none!important}@media(max-width:991px){.nav-toggle{display:none!important}.mobile-menu{position:static!important;transform:none!important;top:auto!important;inset:auto!important;border-top:1px solid var(--color-border)}}</style></noscript>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <?php foreach ($extraHeadScripts as $src): ?>
  <script src="<?= ep_h(ep_asset_url($src)) ?>" defer></script>
  <?php endforeach; ?>
  <?php if ($inlineStyles !== ''): ?>
  <style><?= $inlineStyles ?></style>
  <?php endif; ?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => [ep_organization_schema()]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php if (!empty($jsonLdSchema)): ?>
<script type="application/ld+json"><?= $jsonLdSchema ?></script>
<?php endif; ?>
<?php if (!empty($extraHeadHtml)) echo $extraHeadHtml; ?>
<?php // Search Console / Bing Webmaster ownership tokens. Paste the content
      // value from each tool into Admin → Settings; the tag only renders once
      // a token is present, so nothing empty is ever emitted. ?>
<?php $gsv = trim((string) ep_setting('google_site_verification', '')); if ($gsv !== ''): ?>
<meta name="google-site-verification" content="<?= ep_h($gsv) ?>">
<?php endif; ?>
<?php $bsv = trim((string) ep_setting('bing_site_verification', '')); if ($bsv !== ''): ?>
<meta name="msvalidate.01" content="<?= ep_h($bsv) ?>">
<?php endif; ?>
<?php $gaId = ep_setting('google_analytics_id'); if (!empty($gaId)): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= ep_h($gaId) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?= ep_h($gaId) ?>');
</script>
<?php endif; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . ep_h($bodyClass) . '"' : '' ?>>
