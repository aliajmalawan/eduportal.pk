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
?>
<footer class="footer" id="contact">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= ep_h(ep_url('index.php#hero')) ?>" class="logo">
          <img src="assets/logo_icon.jpg" alt="" class="logo-img" width="36" height="36">
          <span class="logo-text"><?= ep_h($siteName) ?></span>
        </a>
        <p><?= ep_h($footerAbout) ?></p>
        <?php if ($socialLinks): ?>
        <div class="social-links">
          <?php foreach ($socialLinks as $social): ?>
          <a href="<?= ep_h($social['url']) ?>" aria-label="<?= ep_h($social['label']) ?>" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-<?= ep_h($social['icon']) ?>" style="font-size:16px"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="footer-col">
        <h5>Product</h5>
        <ul>
          <li><a href="<?= ep_h(ep_url('features.php')) ?>">Features</a></li>
          <li><a href="<?= ep_h(ep_url('pricing.php')) ?>">Pricing</a></li>
          <li><a href="<?= ep_h(ep_url('videos.php')) ?>">Video Testimonials</a></li>
          <li><a href="<?= ep_h(ep_url('reviews.php')) ?>">Google Reviews</a></li>
          <li><a href="<?= ep_h(ep_url('index.php#mobile-apps')) ?>">Mobile App</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Company</h5>
        <ul>
          <li><a href="<?= ep_h(ep_url('about.php')) ?>">About Us</a></li>
          <li><a href="<?= ep_h(ep_url('contact.php')) ?>">Contact</a></li>
          <li><a href="<?= ep_h(ep_url('blog.php')) ?>">Blog</a></li>
          <li><a href="<?= ep_h(ep_careers_url()) ?>">Careers</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Support</h5>
        <ul>
          <li><a href="<?= ep_h(ep_url('contact.php')) ?>">Contact</a></li>
          <li><a href="<?= ep_h(ep_url('pricing.php')) ?>">Pricing</a></li>
        </ul>
      </div>
      <?php if ($footerContacts): ?>
      <div class="footer-col footer-contact">
        <h5>Contact</h5>
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
