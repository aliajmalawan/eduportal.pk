<?php
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/google-reviews.php';

// Public page: reads only from the local cache table, never calls Google.
// If the cache is empty or Google's API is down, this still renders a
// normal page — it never blanks or breaks (see STEP 10).
$reviews = ep_get_google_reviews(false);
$reviewsSummary = ep_google_reviews_summary();
$avgRating = $reviewsSummary['average_rating'];
$totalCount = $reviewsSummary['total_count'];
$placeId = ep_google_place_id();

$pageTitle       = 'Client Reviews — EduPortal School ERP Pakistan';
$pageDescription = 'Real Google reviews from schools and institutes using EduPortal school management software in Pakistan.';
$canonicalUrl    = ep_canonical_url();
$navActive       = 'reviews';
$useDemoModal    = true;
$extraStylesheets = ['css/shared.css', 'css/reviews.css'];
$extraBodyScripts = [ep_versioned_asset('js/reveal.js')];
$epTrackPage     = 'reviews';

/**
 * Structured data for this page.
 *
 * Deliberately contains NO aggregateRating / Review markup, even though
 * the real google_rating and google_review_count values are available.
 * Google's review-snippet guidelines
 * (developers.google.com/search/docs/appearance/structured-data/review-snippet,
 * verified current 2026-08-19) prohibit it here on two counts:
 *
 *   1. "Don't aggregate reviews or ratings from other websites." — these
 *      ratings come from Google, i.e. another website.
 *   2. Self-serving reviews: "If the entity that's being reviewed controls
 *      the reviews about itself, their pages that use LocalBusiness or any
 *      other type of Organization structured data are ineligible for star
 *      review feature" — which the docs illustrate with the exact case of
 *      "Google Business reviews" shown on the reviewed entity's own site.
 *
 * Marking it up anyway would not render stars and risks a structured-data
 * manual action, so this emits only page-level schema that makes no
 * rating claim. The ratings remain visible to human readers on the page.
 */
$jsonLdSchema = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => 'https://eduportal.pk/reviews.php#webpage',
            'name' => 'Client Reviews',
            'description' => $pageDescription,
            'url' => 'https://eduportal.pk/reviews.php',
            'isPartOf' => ['@id' => 'https://eduportal.pk/#website'],
            'publisher' => ['@id' => 'https://eduportal.pk/#organization'],
            'inLanguage' => 'en',
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://eduportal.pk/reviews.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://eduportal.pk/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Client Reviews', 'item' => 'https://eduportal.pk/reviews.php'],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="section-label">Client Reviews</span>
        <h1>What Schools Say on Google</h1>
        <p>Real Google reviews from schools and institutes using EduPortal.</p>
      </div>
    </section>

    <section class="reviews-section section">
      <div class="container">
        <?php if ($avgRating !== '' || $totalCount !== ''): ?>
        <div class="reviews-summary">
          <?php if ($avgRating !== ''): ?>
          <span class="reviews-summary-score"><?= ep_h($avgRating) ?> / 5</span>
          <span class="reviews-summary-stars" aria-hidden="true"><?= ep_google_star_display((float) $avgRating) ?></span>
          <?php endif; ?>
          <?php if ($totalCount !== ''): ?>
          <span>from <?= ep_h($totalCount) ?> Google reviews</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($placeId !== ''): ?>
        <div class="reviews-cta-row">
          <a class="btn btn-ghost" href="<?= ep_h(ep_google_maps_place_url($placeId)) ?>" target="_blank" rel="noopener noreferrer">See all reviews on Google</a>
          <a class="btn btn-primary" href="<?= ep_h(ep_google_write_review_url($placeId)) ?>" target="_blank" rel="noopener noreferrer">Write a Google Review</a>
        </div>
        <?php endif; ?>

        <?php if (!$reviews): ?>
        <p class="reviews-empty">Reviews are being synced from Google. Please check back soon.</p>
        <?php else: ?>
        <p class="reviews-attribution-note">Showing all reviews left by real customers on our Google Business Profile, newest first.</p>
        <div class="reviews-grid">
          <?php foreach ($reviews as $r): ?>
          <article class="review-card reveal">
            <div class="review-card-header">
              <?php $avatarInitial = ep_h(mb_strtoupper(mb_substr($r['author_name'], 0, 1))); ?>
              <?php if (!empty($r['author_photo'])): ?>
              <?php // If Google's avatar URL has expired or is blocked, swap in the
                    // initial-letter fallback instead of leaving a broken image. ?>
              <img class="review-card-avatar" src="<?= ep_h($r['author_photo']) ?>" alt="" width="44" height="44" loading="lazy" referrerpolicy="no-referrer"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <span class="review-card-avatar-fallback" aria-hidden="true" style="display:none"><?= $avatarInitial ?></span>
              <?php else: ?>
              <span class="review-card-avatar-fallback" aria-hidden="true"><?= $avatarInitial ?></span>
              <?php endif; ?>
              <div>
                <div class="review-card-author"><?= ep_h($r['author_name']) ?></div>
                <div class="review-card-stars" aria-label="<?= (int) $r['rating'] ?> out of 5 stars">
                  <?= ep_google_star_display((float) $r['rating']) ?>
                </div>
                <?php $reviewDateDisplay = ep_google_format_review_date($r['review_date'] ?? null); ?>
                <?php if ($reviewDateDisplay !== ''): ?>
                <div class="review-card-date"><?= ep_h($reviewDateDisplay) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($r['review_text'])): ?>
            <p><?= nl2br(ep_h($r['review_text'])) ?></p>
            <?php endif; ?>
            <span class="review-card-google-badge"><i data-lucide="badge-check" style="width:14px;height:14px" aria-hidden="true"></i> Google Review</span>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php
$ctaLabel     = 'Next step';
$ctaHeading   = 'See what they are reviewing';
$ctaText      = 'Book a walkthrough and judge EduPortal against how your office works today.';
$ctaPrimary   = 'demo';
$ctaSecondary = ['href' => ep_url('videos.php'), 'label' => 'Watch principals using it'];
$ctaNote      = '';
require __DIR__ . '/includes/partials/cta-band.php';
?>
<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
