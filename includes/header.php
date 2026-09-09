<?php
/** @var string $navActive home|features|pricing|blog|case-studies|videos|reviews|careers|contact */
// The navbar links to Careers on every page, so the careers helpers must be
// loaded here rather than only on the careers pages themselves.
require_once __DIR__ . '/careers.php';
$navActive = $navActive ?? '';
$siteName = ep_setting('site_name', 'EduPortal');
?>
<header class="navbar" id="navbar">
  <div class="container navbar-inner">
    <a href="<?= ep_h(ep_url('index.php#hero')) ?>" class="logo">
      <img src="<?= ep_h(ep_url('assets/logo_icon.jpg')) ?>" alt="" class="logo-img" width="36" height="36">
      <span class="logo-text"><?= ep_h($siteName) ?></span>
    </a>
    <nav class="nav-links">
      <a href="<?= ep_h(ep_url('index.php#hero')) ?>"<?= $navActive === 'home' ? ' class="active"' : '' ?>>Home</a>
      <a href="<?= ep_h(ep_url('features.php')) ?>"<?= $navActive === 'features' ? ' class="active"' : '' ?>>Features</a>
      <a href="<?= ep_h(ep_url('pricing.php')) ?>"<?= $navActive === 'pricing' ? ' class="active"' : '' ?>>Pricing</a>
      <a href="<?= ep_h(ep_url('blog.php')) ?>"<?= $navActive === 'blog' ? ' class="active"' : '' ?>>Blogs</a>
      <a href="<?= ep_h(ep_url('about.php')) ?>"<?= $navActive === 'about' ? ' class="active"' : '' ?>>About Us</a>
      <a href="<?= ep_h(ep_url('videos.php')) ?>"<?= $navActive === 'videos' ? ' class="active"' : '' ?>>Video Reviews</a>
      <a href="<?= ep_h(ep_careers_url()) ?>"<?= $navActive === 'careers' ? ' class="active"' : '' ?>>Careers</a>
      <a href="<?= ep_h(ep_url('contact.php')) ?>"<?= $navActive === 'contact' ? ' class="active"' : '' ?>>Contact</a>
    </nav>
    <div class="nav-actions">
      <?php if (!empty($useDemoModal)): ?>
      <button type="button" class="btn btn-dark js-open-modal">Get Started</button>
      <?php else: ?>
      <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-dark">Get Started</a>
      <?php endif; ?>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Open menu">
      <i data-lucide="menu" style="width:24px;height:24px"></i>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="<?= ep_h(ep_url('index.php#hero')) ?>">Home</a>
    <a href="<?= ep_h(ep_url('features.php')) ?>">Features</a>
    <a href="<?= ep_h(ep_url('pricing.php')) ?>">Pricing</a>
    <a href="<?= ep_h(ep_url('blog.php')) ?>"<?= $navActive === 'blog' ? ' class="active"' : '' ?>>Blogs</a>
    <a href="<?= ep_h(ep_url('about.php')) ?>"<?= $navActive === 'about' ? ' class="active"' : '' ?>>About Us</a>
    <a href="<?= ep_h(ep_url('videos.php')) ?>"<?= $navActive === 'videos' ? ' class="active"' : '' ?>>Video Reviews</a>
    <a href="<?= ep_h(ep_careers_url()) ?>"<?= $navActive === 'careers' ? ' class="active"' : '' ?>>Careers</a>
    <a href="<?= ep_h(ep_url('contact.php')) ?>"<?= $navActive === 'contact' ? ' class="active"' : '' ?>>Contact</a>
    <?php if (!empty($useDemoModal)): ?>
    <button type="button" class="btn btn-dark js-open-modal">Get Started</button>
    <?php else: ?>
    <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-dark">Get Started</a>
    <?php endif; ?>
  </div>
</header>
