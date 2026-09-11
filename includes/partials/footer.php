<?php
/** @deprecated Use includes/footer.php for full page end (footer + scripts + tracking). */
// The footer links to Careers; not every page that ends with this partial
// also renders includes/header.php, so load the helpers here as well.
require_once dirname(__DIR__) . '/careers.php';
$footerAbout = ep_setting('footer_about_text', 'Modern school management ERP trusted by educators worldwide.');
$copyright = ep_setting('copyright_text', '© ' . date('Y') . ' EduPortal. All rights reserved.');
$siteName = ep_setting('site_name', 'EduPortal');
$socialLinks = ep_get_social_links(true);
$footerContacts = ep_get_contact_items(false);
$siteLogo = trim((string) ep_setting('logo_path', ''));
if ($siteLogo === '' || !is_file(dirname(__DIR__, 2) . '/' . ltrim($siteLogo, '/'))) {
    $siteLogo = 'assets/logo_icon.jpg';
}
?>
<footer class="footer" id="contact">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= ep_h(ep_url('index.php#hero')) ?>" class="logo">
          <img src="<?= ep_h(ep_url($siteLogo)) ?>" alt="" class="logo-img" width="36" height="36">
          <span class="logo-text"><?= ep_h($siteName) ?></span>
        </a>
        <p><?= ep_h($footerAbout) ?></p>
        <?php if ($socialLinks): ?>
        <div class="social-links">
          <?php foreach ($socialLinks as $social): ?>
          <a href="<?= ep_h($social['url']) ?>" aria-label="<?= ep_h($social['label']) ?>" target="_blank" rel="noopener noreferrer">
            <?php
            // Footer icons are Bootstrap Icons, but the admin-entered names are
            // Lucide-style ("smartphone"), because the rest of the site uses Lucide.
            // Bootstrap has no "smartphone", so the Google Play link rendered as an
            // empty box on every page. Lucide cannot take over here: 1.43 ships no
            // brand icons at all (facebook, linkedin, instagram, youtube).
            // Platform is the more specific signal, so it is checked first.
            $biByPlatform = [
                'play_store' => 'google-play', 'app_store' => 'apple', 'x' => 'twitter-x',
                'whatsapp' => 'whatsapp', 'youtube' => 'youtube', 'tiktok' => 'tiktok',
            ];
            $biAlias = ['smartphone' => 'phone', 'mobile' => 'phone', 'mail' => 'envelope', 'map-pin' => 'geo-alt', 'x' => 'twitter-x'];
            $platformKey = strtolower((string) ($social['platform'] ?? ''));
            $iconKey = strtolower(trim((string) ($social['icon'] ?? '')));
            $biName = $biByPlatform[$platformKey] ?? ($biAlias[$iconKey] ?? $iconKey);
            ?>
            <i class="bi bi-<?= ep_h($biName) ?>" style="font-size:16px" aria-hidden="true"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="footer-col">
        <h2>Product</h2>
        <ul>
          <li><a href="<?= ep_h(ep_url('features.php')) ?>">Features</a></li>
          <li><a href="<?= ep_h(ep_url('pricing.php')) ?>">Pricing</a></li>
          <li><a href="<?= ep_h(ep_url('videos.php')) ?>">Video Testimonials</a></li>
          <li><a href="<?= ep_h(ep_url('reviews.php')) ?>">Google Reviews</a></li>
          <li><a href="<?= ep_h(ep_url('index.php#mobile-apps')) ?>">Mobile App</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h2>Company</h2>
        <ul>
          <li><a href="<?= ep_h(ep_url('about.php')) ?>">About Us</a></li>
          <li><a href="<?= ep_h(ep_url('contact.php')) ?>">Contact</a></li>
          <li><a href="<?= ep_h(ep_url('blog.php')) ?>">Blog</a></li>
          <li><a href="<?= ep_h(ep_careers_url()) ?>">Careers</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h2>Support</h2>
        <ul>
          <li><a href="<?= ep_h(ep_url('contact.php')) ?>">Contact</a></li>
          <li><a href="<?= ep_h(ep_url('pricing.php')) ?>">Pricing</a></li>
        </ul>
      </div>
      <?php if ($footerContacts): ?>
      <div class="footer-col footer-contact">
        <h2>Contact</h2>
        <ul>
          <?php foreach ($footerContacts as $item):
            if (in_array($item['item_type'], ['map_embed', 'hours'], true)) {
                continue;
            }
            $icon = $item['icon'] ?: ep_contact_icon($item['item_type']);
            $link = $item['link_url'] ?: '#';
            $value = str_replace("\n", ' ', $item['value']);
          ?>
          <li>
            <i data-lucide="<?= ep_h($icon) ?>" style="width:16px;flex-shrink:0;margin-top:2px"></i>
            <a href="<?= ep_h($link) ?>"<?= strpos($link, 'http') === 0 ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= ep_h($value) ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-bottom">
      <span><?= ep_h($copyright) ?></span>
      <div class="footer-bottom-links">
        <a href="<?= ep_h(ep_url('privacy-policy.html')) ?>">Privacy Policy</a>
        <?php // "Terms of Service" previously pointed at "#". There is no terms
              // page on the site yet, so the dead link is omitted rather than
              // aimed somewhere wrong — restore it once that page exists. ?>
      </div>
    </div>
  </div>
</footer>
