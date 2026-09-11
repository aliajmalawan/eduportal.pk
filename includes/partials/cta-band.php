<?php
/**
 * Closing call-to-action band.
 *
 * One component, contextual copy. Five content pages (blog, videos, case
 * studies, reviews, docs) previously ended with no in-page action at all — a
 * reader who got to the bottom had only the nav to fall back on.
 *
 * The ask is matched to where the visitor is in the journey rather than
 * repeating the same demand everywhere: someone browsing the blog is not at
 * the same point as someone who has just read a case study.
 *
 * Set before including:
 *   $ctaLabel      short eyebrow (default "Next step")
 *   $ctaHeading    the ask, as a sentence
 *   $ctaText       one supporting line (optional)
 *   $ctaPrimary    'demo' opens the booking form | or ['href' => ..., 'label' => ...]
 *   $ctaSecondary  ['href' => ..., 'label' => ...] (optional)
 *   $ctaNote       reassurance line (optional)
 *
 * Built from the existing system — .section--dark carries the motivated band
 * and grain, .cta-row sets the primary/secondary relationship — so it adds no
 * styling of its own.
 */
$ctaLabel     = $ctaLabel     ?? 'Next step';
$ctaHeading   = $ctaHeading   ?? 'See EduPortal running your institution';
$ctaText      = $ctaText      ?? '';
$ctaPrimary   = $ctaPrimary   ?? 'demo';
$ctaSecondary = $ctaSecondary ?? null;
$ctaNote      = $ctaNote      ?? '';
?>
<section class="section section--dark ep-cta-band">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-label"><?= ep_h($ctaLabel) ?></span>
      <h2 class="section-title"><?= ep_h($ctaHeading) ?></h2>
      <?php if ($ctaText !== ''): ?>
      <p class="section-subtitle"><?= ep_h($ctaText) ?></p>
      <?php endif; ?>
    </div>
    <div class="cta-row cta-row--center reveal">
      <?php if ($ctaPrimary === 'demo'): ?>
      <button type="button" class="btn btn-primary btn-lg js-open-modal">Book a Demo <i data-lucide="arrow-right" aria-hidden="true"></i></button>
      <?php else: ?>
      <a href="<?= ep_h($ctaPrimary['href']) ?>" class="btn btn-primary btn-lg"><?= ep_h($ctaPrimary['label']) ?> <i data-lucide="arrow-right" aria-hidden="true"></i></a>
      <?php endif; ?>
      <?php if ($ctaSecondary): ?>
      <a href="<?= ep_h($ctaSecondary['href']) ?>" class="btn btn-outline-light btn-lg"><?= ep_h($ctaSecondary['label']) ?></a>
      <?php endif; ?>
      <?php if ($ctaNote !== ''): ?>
      <p class="cta-note"><?= ep_h($ctaNote) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
