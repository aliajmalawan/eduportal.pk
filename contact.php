<?php
require_once __DIR__ . '/includes/cms.php';
$useDemoModal = true;

$navActive = 'contact';
$contactItems = ep_get_contact_items(true);
$phones = array_filter($contactItems, fn($i) => in_array($i['item_type'], ['phone', 'whatsapp'], true));
$emails = array_filter($contactItems, fn($i) => $i['item_type'] === 'email');
$addresses = array_filter($contactItems, fn($i) => $i['item_type'] === 'address');
$hours = array_filter($contactItems, fn($i) => $i['item_type'] === 'hours');
$mapEmbed = null;
$mapLink = null;
foreach ($contactItems as $item) {
    if ($item['item_type'] === 'map_embed') {
        $mapEmbed = $item['value'];
    }
    if ($item['item_type'] === 'map_link') {
        $mapLink = $item;
    }
}
$pageTitle = 'Contact Us — EduPortal School ERP | Shakargarh Office';
$pageDescription = 'Contact EduPortal — demos, support, and office details in Shakargarh, Pakistan.';
$canonicalUrl = ep_canonical_url();
$extraStylesheets = ['css/shared.css', 'css/contact.css'];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="section-label">Get in Touch</span>
        <h1>Contact <?= ep_h(ep_setting('site_name', 'EduPortal')) ?></h1>
        <p>Reach our team for demos, support, or partnership inquiries. We are here to help your school go digital.</p>
      </div>
    </section>

    <section class="contact-section section">
      <div class="container">
        <div class="contact-grid">
          <?php if ($phones): ?>
          <article class="contact-card">
            <div class="contact-card-icon"><i data-lucide="phone"></i></div>
            <h2><?= ep_h($phones[array_key_first($phones)]['label'] ?: 'Phone & WhatsApp') ?></h2>
            <?php foreach ($phones as $p): ?>
            <a href="<?= ep_h($p['link_url'] ?: '#') ?>" class="primary-link" target="_blank" rel="noopener noreferrer"><?= ep_h($p['value']) ?></a>
            <?php endforeach; ?>
            <p class="contact-sub">Tap a number to chat on WhatsApp</p>
          </article>
          <?php endif; ?>

          <?php if ($emails): foreach ($emails as $e): ?>
          <article class="contact-card">
            <div class="contact-card-icon"><i data-lucide="mail"></i></div>
            <h2><?= ep_h($e['label'] ?: 'Email') ?></h2>
            <a href="<?= ep_h($e['link_url'] ?: 'mailto:' . $e['value']) ?>" class="primary-link"><?= ep_h($e['value']) ?></a>
            <p class="contact-sub">We typically reply within one business day</p>
          </article>
          <?php endforeach; endif; ?>

          <?php if ($addresses): foreach ($addresses as $a): ?>
          <article class="contact-card">
            <div class="contact-card-icon"><i data-lucide="map-pin"></i></div>
            <h2><?= ep_h($a['label'] ?: 'Office Address') ?></h2>
            <p><?= nl2br(ep_h($a['value'])) ?></p>
            <?php if ($a['link_url']): ?>
            <a href="<?= ep_h($a['link_url']) ?>" class="primary-link" target="_blank" rel="noopener noreferrer" style="margin-top:0.75rem;display:inline-block">Open in Google Maps</a>
            <?php endif; ?>
          </article>
          <?php endforeach; endif; ?>

          <?php if ($hours): foreach ($hours as $h): ?>
          <article class="contact-card">
            <div class="contact-card-icon"><i data-lucide="clock"></i></div>
            <h2><?= ep_h($h['label'] ?: 'Business Hours') ?></h2>
            <p><?= nl2br(ep_h($h['value'])) ?></p>
            <?php if (ep_setting('business_hours_sunday')): ?>
            <p class="contact-sub"><?= ep_h(ep_setting('business_hours_sunday')) ?></p>
            <?php endif; ?>
          </article>
          <?php endforeach; endif; ?>
        </div>

        <?php if ($mapEmbed): ?>
        <div class="contact-map-wrap">
          <iframe title="EduPortal office location on Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= ep_h($mapEmbed) ?>" allowfullscreen></iframe>
          <div class="contact-map-caption">
            <p><strong><?= ep_h(ep_setting('site_name', 'EduPortal')) ?></strong> — <?= ep_h(str_replace("\n", ', ', ep_setting('office_street', ''))) ?></p>
            <?php if ($mapLink && $mapLink['link_url']): ?>
            <a href="<?= ep_h($mapLink['link_url']) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Get Directions</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="contact-cta">
          <h2>Ready to transform your school?</h2>
          <p>Book a free demo and see how EduPortal simplifies fees, attendance, exams, and parent communication.</p>
          <button type="button" class="btn btn-primary btn-lg js-open-modal">Book a Demo <i data-lucide="arrow-right" aria-hidden="true"></i></button>
        </div>
      </div>
    </section>
  </main>

<?php
$epTrackPage = 'contact';
require __DIR__ . '/includes/partials/demo-modal.php';
require __DIR__ . '/includes/footer.php';
