<?php
/** @var string $navActive home|features|pricing|blog|case-studies|videos|reviews|careers|contact */
// The navbar links to Careers on every page, so the careers helpers must be
// loaded here rather than only on the careers pages themselves.
require_once __DIR__ . '/careers.php';
$navActive = $navActive ?? '';
$siteName = ep_setting('site_name', 'EduPortal');
$siteLogo = trim((string) ep_setting('logo_path', ''));
if ($siteLogo === '' || !is_file(dirname(__DIR__) . '/' . ltrim($siteLogo, '/'))) {
    $siteLogo = 'assets/logo_icon.jpg';
}

// One source for both menus. Previously the desktop nav and the mobile menu
// were two hand-maintained lists, and they had already drifted: Home, Features
// and Pricing carried no active state on mobile while every other link did.
$navItems = [
    ['home',     ep_url('index.php#hero'), 'Home'],
    ['features', ep_url('features.php'),   'Features'],
    ['pricing',  ep_url('pricing.php'),    'Pricing'],
    ['blog',     ep_url('blog.php'),       'Blogs'],
    ['about',    ep_url('about.php'),      'About Us'],
    ['videos',   ep_url('videos.php'),     'Video Reviews'],
    ['careers',  ep_careers_url(),         'Careers'],
    ['contact',  ep_url('contact.php'),    'Contact'],
];
?>
<?php // Lets a keyboard or screen-reader user reach the content without
      // tabbing through eight nav links on every page. Visible only on focus. ?>
<a class="skip-link" href="#content">Skip to content</a>
<header class="navbar" id="navbar">
  <div class="container navbar-inner">
    <a href="<?= ep_h(ep_url('index.php#hero')) ?>" class="logo">
      <img src="<?= ep_h(ep_url($siteLogo)) ?>" alt="" class="logo-img" width="36" height="36">
      <span class="logo-text"><?= ep_h($siteName) ?></span>
    </a>

    <nav class="nav-links" aria-label="Main">
      <?php foreach ($navItems as [$key, $href, $label]): ?>
      <a href="<?= ep_h($href) ?>"<?= $navActive === $key ? ' class="active" aria-current="page"' : '' ?>><?= ep_h($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-actions">
      <?php if (!empty($useDemoModal)): ?>
      <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
      <?php else: ?>
      <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-dark">Book a Demo</a>
      <?php endif; ?>
    </div>

    <?php // aria-expanded and aria-controls are managed by js/nav.js; they are
          // rendered in the closed state so the markup is correct before any
          // script runs. ?>
    <button class="nav-toggle" id="navToggle" type="button"
            aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
      <i data-lucide="menu" style="width:24px;height:24px" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile">
    <?php foreach ($navItems as [$key, $href, $label]): ?>
    <a href="<?= ep_h($href) ?>"<?= $navActive === $key ? ' class="active" aria-current="page"' : '' ?>><?= ep_h($label) ?></a>
    <?php endforeach; ?>
    <?php if (!empty($useDemoModal)): ?>
    <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
    <?php else: ?>
    <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-dark">Book a Demo</a>
    <?php endif; ?>
  </nav>
</header>
<?php // Skip-link target. Emitted here rather than relying on a page-level
      // <main id="main">: only 12 of the public pages have a <main> landmark
      // at all (index, pricing and landing have none), so a link to #main
      // would have gone nowhere on the most-visited page on the site.
      // tabindex="-1" lets focus land here when the fragment is followed. ?>
<span id="content" tabindex="-1" class="skip-target"></span>
