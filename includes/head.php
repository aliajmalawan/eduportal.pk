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
  <?php // Marks the document as JS-capable BEFORE first paint, so the
        // .ep-pull reveal only ever parks content at opacity:0 when there is
        // JavaScript able to un-park it. With JS off the class is absent and
        // every revealed element renders at rest -- the site stays readable
        // for AI crawlers and no-JS visitors. ?>
  <script>document.documentElement.className += ' js';</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
  <noscript><style>.faq-a,.home-faq-a,.fd-faq-a{max-height:none!important}.reveal,.reveal-fd{opacity:1!important;transform:none!important}@media(max-width:991px){.nav-toggle{display:none!important}.mobile-menu{position:static!important;transform:none!important;visibility:visible!important;overflow:visible!important;top:auto!important;inset:auto!important;border-top:1px solid var(--color-border)}}</style></noscript>
  <script src="https://unpkg.com/lucide@1.43.0/dist/umd/lucide.min.js" defer></script>
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
<?php
// ---------------------------------------------------------------------
// Social preview (Open Graph + Twitter Card).
//
// Emitted centrally so every page including this file gets a complete card.
// Previously only 30 pages carried og:title/og:description of their own and
// none carried an image, so the rest -- About, Contact, Careers, Blog, Videos,
// Reviews, Case Studies, Docs -- shared as a bare link with no preview.
//
// Those 30 pages pass their tags through $extraHeadHtml. Their wording is
// honoured, but the tags are emitted from here and stripped from
// $extraHeadHtml, so nothing is duplicated and every page also gains an image,
// a card type and a URL.
$ogTitle = $pageTitle;
$ogDesc  = $pageDescription;
if ($extraHeadHtml !== '') {
    if (preg_match('~property="og:title"\s+content="([^"]*)"~i', $extraHeadHtml, $mt)) {
        $ogTitle = html_entity_decode($mt[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('~property="og:description"\s+content="([^"]*)"~i', $extraHeadHtml, $md)) {
        $ogDesc = html_entity_decode($md[1], ENT_QUOTES, 'UTF-8');
    }
    // 27 of those pages also pass og:type and a RELATIVE og:image
    // ("assets/dashboard.png"), which Facebook and WhatsApp discard outright.
    // Take the value, let the absolute-URL resolution below fix it, and strip
    // the originals so no tag is emitted twice.
    if (preg_match('~property="og:image"\s+content="([^"]*)"~i', $extraHeadHtml, $mi)) {
        $pageOgImage = html_entity_decode($mi[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('~property="og:type"\s+content="([^"]*)"~i', $extraHeadHtml, $mty)) {
        $ogType = html_entity_decode($mty[1], ENT_QUOTES, 'UTF-8');
    }
    $extraHeadHtml = (string) preg_replace(
        '~\s*<meta\s+property="og:(?:title|description|image|type|url)"[^>]*>~i',
        '',
        $extraHeadHtml
    );
}
// A page may set $ogImage for a more specific picture (a blog cover, a case
// study). Anything relative is resolved against the site root.
$ogImage = trim((string) ($ogImage ?? $pageOgImage ?? 'assets/og-share.jpg'));
// The shared product screenshot is 1536x1024 and 1.37 MB; the purpose-built
// card is 1200x630 and 100 KB, so prefer it wherever a page only ever named
// the screenshot by default.
if ($ogImage === 'assets/dashboard.png') {
    $ogImage = 'assets/og-share.jpg';
}
if ($ogImage !== '' && !preg_match('~^https?://~i', $ogImage)) {
    $ogImage = ep_url(ltrim($ogImage, '/'));
}
$ogUrl  = $canonicalUrl !== '' ? $canonicalUrl : ep_url(ltrim((string) ($_SERVER['REQUEST_URI'] ?? ''), '/'));
$ogType = trim((string) ($ogType ?? 'website'));
?>
<meta property="og:type" content="<?= ep_h($ogType) ?>">
<meta property="og:site_name" content="EduPortal">
<meta property="og:locale" content="en_PK">
<meta property="og:title" content="<?= ep_h($ogTitle) ?>">
<?php if ($ogDesc !== ''): ?>
<meta property="og:description" content="<?= ep_h($ogDesc) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= ep_h($ogUrl) ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= ep_h($ogImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="EduPortal school management software dashboard">
<?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= ep_h($ogTitle) ?>">
<?php if ($ogDesc !== ''): ?>
<meta name="twitter:description" content="<?= ep_h($ogDesc) ?>">
<?php endif; ?>
<?php if ($ogImage !== ''): ?>
<meta name="twitter:image" content="<?= ep_h($ogImage) ?>">
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
