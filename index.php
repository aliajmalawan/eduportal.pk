<?php
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/faqs-data.php';
require_once __DIR__ . '/includes/google-reviews.php';
$homeTestimonials = ep_get_video_testimonials(true);
$homeFaqs = ep_faqs_featured();
// Reads only the local cache — never calls Google, and if the cache is
// empty (nothing synced yet, or Google's API has been down) the section
// below simply doesn't render, same as $homeTestimonials above when there
// are no videos. The rest of the homepage is unaffected either way.
// Homepage shows only 4/5-star reviews, newest first, capped at 6 — see
// ep_get_homepage_google_reviews(). The full /reviews.php page is
// unfiltered and shows every visible review.
$homeGoogleReviews = ep_get_homepage_google_reviews(6);
$homeGoogleReviewsSummary = ep_google_reviews_summary();
// Real, admin-verified Place ID (looked up from Google, never invented).
// Empty until configured, in which case the Google profile link below is
// simply not rendered rather than pointing somewhere guessed.
$homeGooglePlaceId = ep_google_place_id();

// NOTE: no aggregateRating is emitted anywhere in this graph, despite the
// real google_rating / google_review_count values being available. Google's
// review-snippet guidelines forbid it here — "Don't aggregate reviews or
// ratings from other websites", plus the self-serving rule that makes
// Organization markup carrying the entity's own Google Business reviews
// "ineligible for star review feature". Adding it would not render stars
// and risks a structured-data manual action. See reviews.php's $jsonLdSchema
// comment and GOOGLE_REVIEWS_SETUP.md for the full policy quotes.
$homeJsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ep_organization_schema(),
        [
            '@type' => 'WebSite',
            '@id' => 'https://eduportal.pk/#website',
            'name' => ep_setting('site_name', 'EduPortal'),
            'url' => 'https://eduportal.pk/',
            'description' => 'EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exams, parent apps and analytics. Trusted by ' . ep_site_metric('total_clients') . ' schools in Pakistan.',
            'publisher' => ['@id' => 'https://eduportal.pk/#organization'],
            'inLanguage' => 'en',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => 'https://eduportal.pk/faqs.php?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://eduportal.pk/#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://eduportal.pk/'],
            ],
        ],
        [
            '@type' => 'SoftwareApplication',
            '@id' => 'https://eduportal.pk/#software',
            'name' => ep_setting('site_name', 'EduPortal'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'School Management Software',
            'operatingSystem' => 'Web, Android',
            'description' => 'EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exam results, parent apps & analytics. Trusted by ' . ep_site_metric('total_clients') . ' schools in Pakistan.',
            'url' => 'https://eduportal.pk/',
            'image' => ['https://eduportal.pk/assets/dashboard.png', 'https://eduportal.pk/assets/parent-app.jpg'],
            'screenshot' => [
                ['@type' => 'ImageObject', 'url' => 'https://eduportal.pk/assets/dashboard.png'],
                ['@type' => 'ImageObject', 'url' => 'https://eduportal.pk/assets/parent-app.jpg'],
            ],
            'featureList' => [
                'Student Information System',
                'Attendance Management',
                'Fee Management',
                'Examination Management',
                'Timetable Management',
                'Lesson Plans & Syllabus Tracking',
                'Library Management',
                'Transport Management',
                'Hostel Management',
                'Canteen & POS System',
                'Parent Mobile App',
                'Teacher Mobile App',
                'SMS Messaging',
                'WhatsApp Communication',
                'ID Cards Generation',
                'Certificates & ID Cards',
                'Exam Datasheets & Roll Slips',
                'Accounting Management',
                'Payroll Management',
                'Expenses & Profit/Loss Tracking',
                'Inventory Management',
                'Multi-Campus Management',
                'Online Admissions',
                'Learning Management System (LMS)',
                'Customization Options',
                'Dedicated Customer Support',
            ],
            'offers' => [
                [
                    '@type' => 'Offer',
                    'name' => 'Silver',
                    'priceCurrency' => 'PKR',
                    'price' => 3600,
                    'availability' => 'https://schema.org/InStock',
                    'url' => 'https://eduportal.pk/pricing.php',
                ],
                [
                    '@type' => 'Offer',
                    'name' => 'Gold',
                    'priceCurrency' => 'PKR',
                    'price' => 6300,
                    'availability' => 'https://schema.org/InStock',
                    'url' => 'https://eduportal.pk/pricing.php',
                ],
                [
                    '@type' => 'Offer',
                    'name' => 'Diamond',
                    'priceCurrency' => 'PKR',
                    'price' => 10800,
                    'availability' => 'https://schema.org/InStock',
                    'url' => 'https://eduportal.pk/pricing.php',
                ],
            ],
            'provider' => ['@id' => 'https://eduportal.pk/#organization'],
        ],
        [
            '@type' => 'WebPage',
            '@id' => 'https://eduportal.pk/#webpage',
            'name' => 'EduPortal — School Management Software & ERP for Modern Schools',
            'description' => 'EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exam results, parent apps & analytics. Trusted by ' . ep_site_metric('total_clients') . ' schools in Pakistan.',
            'url' => 'https://eduportal.pk/',
            'isPartOf' => ['@id' => 'https://eduportal.pk/#website'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exam results, parent apps &amp; analytics. Trusted by <?= ep_h(ep_site_metric('total_clients')) ?> schools in Pakistan &amp; worldwide.">
  <meta name="keywords" content="school management software, school ERP, EduPortal, student information system, fee management software, school attendance system, parent portal app, school management system Pakistan, edtech ERP, online school software">
  <meta name="robots" content="index, follow">
  <meta name="author" content="EduPortal">
  <link rel="canonical" href="<?= ep_h(ep_canonical_url()) ?>">
  <script type="application/ld+json" id="eduportal-schema"><?= json_encode($homeJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <title>EduPortal — School Management Software &amp; ERP for Modern Schools</title>
  <meta property="og:type" content="website">
  <meta property="og:title" content="EduPortal — AI-Powered School Management ERP">
  <meta property="og:description" content="Run your entire school smarter with EduPortal — attendance, fees, exams, parent apps &amp; real-time analytics in one platform.">
  <meta property="og:image" content="assets/dashboard.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="EduPortal — School Management Software">
  <meta name="twitter:description" content="AI-powered school ERP trusted by <?= ep_h(ep_site_metric('total_clients')) ?> schools. Automate fees, attendance, results &amp; parent communication.">
  <link rel="icon" href="assets/logo_icon.jpg" type="image/jpeg">
  <link rel="apple-touch-icon" href="assets/logo_icon.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url('css/index.css')) ?>">
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url('css/video-cards.css')) ?>">
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url('css/lead-toast.css')) ?>">
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url('css/faqs.css')) ?>">
  <link rel="stylesheet" href="<?= ep_h(ep_asset_url('css/reviews.css')) ?>">
  <?php // Mirrors the noscript block in includes/head.php — index.php builds
        // its own <head> rather than including that file, so the fallback has
        // to be repeated here. Below 992px the desktop nav is display:none and
        // the mobile drawer only opens via JS, leaving no navigation at all
        // with JS disabled. ?>
  <noscript><style>.faq-a,.home-faq-a,.fd-faq-a{max-height:none!important}.reveal,.reveal-fd{opacity:1!important;transform:none!important}@media(max-width:991px){.nav-toggle{display:none!important}.mobile-menu{position:static!important;transform:none!important;top:auto!important;inset:auto!important;border-top:1px solid var(--color-border)}}</style></noscript>
  <script src="<?= ep_h(ep_asset_url('js/country-codes.js')) ?>" defer></script>
  <script src="<?= ep_h(ep_asset_url('js/lead-form.js')) ?>" defer></script>
  <script src="<?= ep_h(ep_asset_url('js/faqs.js')) ?>" defer></script>
  <script src="<?= ep_h(ep_asset_url('js/video-thumbs.js')) ?>" defer></script>
  <script src="<?= ep_h(ep_asset_url('js/reviews-slider.js')) ?>" defer></script>
  <?php // GA4 conversions. index.php includes partials/footer.php directly
        // rather than includes/footer.php, so it does not pick up
        // site-scripts.php — the tag has to be added here too, and the demo
        // form lives on this page. ?>
  <script src="<?= ep_h(ep_asset_url('js/conversions.js')) ?>" defer></script>
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
<body>
<?php
require_once __DIR__ . '/includes/cms.php';
$navActive = 'home';
$useDemoModal = true;
// Homepage demo video, set from Settings -> Home Page (Task 6).
$demoVideo = ep_demo_video();
require __DIR__ . '/includes/header.php';
?>

  <!-- Hero -->
  <section class="hero" id="hero">
    <div class="container">
      <div class="hero-stage">
        <div class="hero-float hero-float--1" aria-hidden="true"><i data-lucide="file-text"></i></div>
        <div class="hero-float hero-float--2" aria-hidden="true"><i data-lucide="pencil"></i></div>
        <div class="hero-float hero-float--3" aria-hidden="true"><i data-lucide="graduation-cap"></i></div>
        <div class="hero-float hero-float--4" aria-hidden="true"><i data-lucide="calendar"></i></div>
        <div class="hero-float hero-float--5" aria-hidden="true"><i data-lucide="book-open"></i></div>
        <div class="hero-float hero-float--6" aria-hidden="true"><i data-lucide="school"></i></div>
        <div class="hero-copy">
          <span class="hero-badge"><i data-lucide="sparkles" aria-hidden="true"></i> Top AI-Powered School Management System</span>
          <h1>Run Your Entire School Smarter, Faster &amp; Better.</h1>
          <p class="hero-sub">Experience powerful automation for fee management, results, attendance, communication, scheduling. Trusted by <?= ep_h(ep_site_metric('total_clients')) ?> Schools Across Pakistan &amp; Beyond.</p>
          <div class="hero-social">
            <div class="hero-avatars" aria-hidden="true">
              <span></span><span></span><span></span><span></span><span></span>
            </div>
            <span class="hero-social-text"><?= ep_h(ep_site_metric('total_clients')) ?> Trusted users</span>
            <?php
            // Google rating + review count for the hero pill. Both values come
            // from ep_site_settings, written only by the Business Profile sync
            // in includes/google-reviews.php -- this reads the cached numbers,
            // it never calls Google on a page load.
            //
            // This replaced a hardcoded "5.0" with five always-filled stars.
            // That number was not tied to any Google data, so it was an
            // invented rating on the most-visited page of the site. If no real
            // rating has synced yet the whole block is skipped rather than
            // falling back to a made-up score.
            $heroSummary = ep_google_reviews_summary();
            $heroRating = trim((string) ($heroSummary['average_rating'] ?? ''));
            $heroCount = trim((string) ($heroSummary['total_count'] ?? ''));
            $heroPlaceId = ep_google_place_id();
            $heroHasRating = $heroRating !== '' && is_numeric($heroRating) && (float) $heroRating > 0;
            ?>
            <?php if ($heroHasRating):
              $heroRatingValue = (float) $heroRating;
              // Floor + half star, never round(). round() would turn a real
              // 4.6 into five solid stars -- visually claiming a perfect score
              // the business does not have. Filling down is the only direction
              // that cannot overstate the rating.
              $heroRatingValue = max(0.0, min(5.0, $heroRatingValue));
              $heroFilled = (int) floor($heroRatingValue);
              $heroHalf = ($heroRatingValue - $heroFilled) >= 0.25 && $heroFilled < 5;
              $heroLabel = number_format($heroRatingValue, 1) . ' out of 5 stars on Google'
                . ($heroCount !== '' ? ' from ' . $heroCount . ' reviews' : '');
            ?>
            <span class="hero-rating">
              <span class="hero-stars" role="img" aria-label="<?= ep_h($heroLabel) ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?><?php if ($i <= $heroFilled): ?><i data-lucide="star"></i><?php elseif ($heroHalf && $i === $heroFilled + 1): ?><i data-lucide="star-half"></i><?php else: ?><i data-lucide="star" class="is-empty"></i><?php endif; ?><?php endfor; ?>
              </span>
              <?= ep_h(number_format($heroRatingValue, 1)) ?>
              <?php if ($heroCount !== ''): ?>
                <?php if ($heroPlaceId !== ''): ?>
              <a class="hero-rating-link" href="<?= ep_h(ep_google_maps_place_url($heroPlaceId)) ?>" target="_blank" rel="noopener noreferrer"><?= ep_h($heroCount) ?> Google reviews</a>
                <?php else: ?>
              <span class="hero-rating-count"><?= ep_h($heroCount) ?> Google reviews</span>
                <?php endif; ?>
              <?php endif; ?>
            </span>
            <?php endif; ?>
          </div>
          <div class="hero-ctas">
            <button type="button" class="btn btn-primary btn-lg js-open-modal">Get Started <i data-lucide="arrow-right" aria-hidden="true"></i></button>
            <a href="pricing.php" class="btn btn-outline btn-lg">Pricing <i data-lucide="tag" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>
      <div class="hero-dashboard">
        <?php // Demo video comes from Settings -> Home Page (Task 6). The same
              // data-* attributes the video-testimonial cards use, so the
              // existing player script handles it with no special casing. ?>
        <?php // Same no-JS fallback as the testimonial cards below.
              $demoHref = $demoVideo['type'] === 'upload' && $demoVideo['file_url'] !== ''
                  ? $demoVideo['file_url']
                  : (!empty($demoVideo['youtube_id'])
                      ? 'https://www.youtube.com/watch?v=' . $demoVideo['youtube_id']
                        . ((int) $demoVideo['start'] > 0 ? '&t=' . (int) $demoVideo['start'] : '')
                      : ''); ?>
        <a<?= $demoHref !== '' ? ' href="' . ep_h($demoHref) . '"' : ' role="button" tabindex="0"' ?> class="dashboard-mockup js-open-video"
             aria-label="Play video: <?= ep_h($demoVideo['title']) ?>"
             data-video-type="<?= ep_h($demoVideo['type']) ?>"
             data-video-src="<?= ep_h($demoVideo['file_url']) ?>"
             data-youtube-id="<?= ep_h($demoVideo['youtube_id']) ?>"
             data-youtube-start="<?= (int) $demoVideo['start'] ?>">
          <img
            src="assets/dashboard.png"
            alt="EduPortal school ERP dashboard — attendance, fees, analytics, and live activity"
            class="dashboard-img"
            width="1400"
            height="900"
            fetchpriority="high"
            decoding="async"
          >
          <span class="dashboard-play-btn" aria-hidden="true">
            <span class="play-pulse"></span>
            <i data-lucide="play"></i>
          </span>
        </a>
      </div>
    </div>
  </section>

  <!-- Video Modal -->
  <div class="video-modal-overlay" id="videoModal" role="dialog" aria-modal="true" aria-label="Testimonial video" aria-hidden="true">
    <div class="video-modal">
      <button type="button" class="video-close" id="videoClose" aria-label="Close video">&times;</button>
      <iframe id="youtubeFrame" title="<?= ep_h($demoVideo['title']) ?>" frameborder="0" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      <video id="html5VideoPlayer" controls playsinline preload="metadata" style="display:none;width:100%;height:100%;background:#000;border-radius:inherit"></video>
    </div>
  </div>

  <!-- Get Started Modal -->
  <div class="modal-overlay" id="getStartedModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal">
      <h2 id="modalTitle">Get Started with EduPortal</h2>
      <p class="modal-desc">Share a few details about your institute and we'll set up a quick, personalized demo for you.</p>
      <form id="getStartedForm" novalidate>
        <div class="form-row">
          <div class="form-group form-group--grow">
            <label for="instituteName">Institute Name</label>
            <input type="text" id="instituteName" name="instituteName" placeholder="Institute name" required>
          </div>
          <div class="form-group form-group--side">
            <label for="studentCount">Strength</label>
            <input type="text" id="studentCount" name="studentCount" placeholder="e.g. 500" inputmode="numeric">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group form-group--grow">
            <label for="fullName">Contact Person Name</label>
            <input type="text" id="fullName" name="fullName" placeholder="Full name" required>
          </div>
          <div class="form-group form-group--side">
            <label for="designation">Designation / Role</label>
            <input type="text" id="designation" name="designation" placeholder="Role" required>
          </div>
        </div>
        <div class="form-group">
          <label for="whatsapp">WhatsApp Number</label>
          <div class="phone-input">
            <select id="countryCode" name="country_code" class="phone-country-select" aria-label="Country code"></select>
            <input type="tel" id="whatsapp" name="whatsapp" placeholder="300 1234567" inputmode="tel" autocomplete="tel-national" required>
          </div>
          <input type="hidden" id="whatsappFull" name="whatsapp_full" value="">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" id="modalCancel">Cancel</button>
          <button type="submit" class="btn-modal-submit">Book now</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Trusted By -->
  <section class="trusted">
    <div class="container reveal">
      <p>Trusted by <?= ep_h(ep_site_metric('total_clients')) ?> schools worldwide</p>
      <div class="trusted-logos">
        <span>Green Valley</span>
        <span>Northwood Academy</span>
        <span>Sunrise Public</span>
        <span>Heritage Intl</span>
        <span>Maple Creek</span>
        <span>Westfield Prep</span>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="section" id="features">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">Features</span>
        <h2 class="section-title">Powerful Tools for Parents &amp; Teachers</h2>
        <p class="section-subtitle">Everything your school needs — attendance, fees, communication, and reports in one unified ERP.</p>
        <p style="margin-top:1rem"><a href="features.php" class="feature-link">Explore all 22+ features <i data-lucide="arrow-right" style="width:14px"></i></a></p>
      </div>
      <div class="features-grid">
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#e0f2fe;color:#0284c7"><i data-lucide="clipboard-check"></i></div>
          <h3>Attendance Tracking</h3>
          <p>Real-time attendance for students and staff with instant parent notifications and daily reports.</p>
          <a href="<?= ep_h(ep_url('features/attendance')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#dcfce7;color:#16a34a"><i data-lucide="wallet"></i></div>
          <h3>Fee Management</h3>
          <p>Automate invoicing, online payments, reminders, and reconciliation with full audit trails.</p>
          <a href="<?= ep_h(ep_url('features/fee-management')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#fef3c7;color:#d97706"><i data-lucide="message-circle"></i></div>
          <h3>Parent Communication</h3>
          <p>Broadcast announcements, chat with teachers, and share homework — all from one secure app.</p>
          <a href="<?= ep_h(ep_url('features/parent-app')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#ede9fe;color:#7c3aed"><i data-lucide="bar-chart-3"></i></div>
          <h3>Academic Analytics</h3>
          <p>Track grades, performance trends, and class rankings with beautiful, exportable dashboards.</p>
          <a href="<?= ep_h(ep_url('features/examinations')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#ffe4e6;color:#e11d48"><i data-lucide="calendar-days"></i></div>
          <h3>Timetable &amp; Scheduling</h3>
          <p>Build conflict-free schedules, manage substitutions, and sync events to parent calendars.</p>
          <a href="<?= ep_h(ep_url('features/timetable')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
        <article class="feature-card reveal">
          <div class="feature-icon" style="background:#f3e8ff;color:#9333ea"><i data-lucide="shield-check"></i></div>
          <h3>Secure Cloud ERP</h3>
          <p>Enterprise-grade security, role-based access, and automatic backups for peace of mind.</p>
          <a href="<?= ep_h(ep_url('features')) ?>" class="feature-link">Learn More <i data-lucide="arrow-right" style="width:14px"></i></a>
        </article>
      </div>
    </div>
  </section>

  <!-- Analytics -->
  <section class="section analytics" id="analytics">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">Analytics</span>
        <h2 class="section-title">Real-time Analytics for Better Decisions</h2>
        <p class="section-subtitle">Monitor attendance, revenue, and performance at a glance with live dashboards built for school leaders.</p>
      </div>
      <div class="analytics-grid reveal">
        <div class="analytics-card">
          <div class="analytics-card-head">
            <h4>Student Attendance</h4>
            <div class="analytics-metric">78.6%<small class="down">↓ 6.4% today</small></div>
          </div>
          <div class="chart-line">
            <svg viewBox="0 0 280 100" width="100%" height="100%" preserveAspectRatio="none">
              <defs><linearGradient id="ag1" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#6366f1" stop-opacity=".3"/><stop offset="100%" stop-color="#6366f1" stop-opacity="0"/></linearGradient></defs>
              <path d="M0,70 L40,55 L80,60 L120,40 L160,45 L200,25 L240,30 L280,15 L280,100 L0,100 Z" fill="url(#ag1)"/>
              <path d="M0,70 L40,55 L80,60 L120,40 L160,45 L200,25 L240,30 L280,15" fill="none" stroke="#6366f1" stroke-width="2.5"/>
            </svg>
          </div>
        </div>
        <div class="analytics-card">
          <div class="analytics-card-head">
            <h4>Monthly Revenue</h4>
            <div class="analytics-metric">72%<small>Collected</small></div>
          </div>
          <div class="chart-donut">
            <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.9" fill="none" stroke="#e8eaed" stroke-width="3"/><circle cx="18" cy="18" r="15.9" fill="none" stroke="#f28c28" stroke-width="3" stroke-dasharray="72 100" stroke-linecap="round"/></svg>
            <div class="chart-donut-label">72%<small>Collected</small></div>
          </div>
        </div>
        <div class="analytics-card">
          <div class="analytics-card-head">
            <h4>Fee Collection</h4>
            <div class="analytics-metric">Rs. 672K<small>Today</small></div>
          </div>
          <div class="chart-bars">
            <span style="height:45%"></span><span style="height:70%"></span><span style="height:55%"></span><span style="height:90%"></span><span style="height:65%"></span><span style="height:80%"></span>
          </div>
        </div>
        <div class="analytics-card analytics-card-wide analytics-activity">
          <div class="activity-top">
            <h4>Recent Activities</h4>
            <span class="activity-viewall" aria-hidden="true">View all</span>
          </div>
          <div class="activity-layout">
            <div class="activity-list">
              <div class="activity-item">
                <div class="activity-avatar activity-avatar--fee"><i data-lucide="wallet" style="width:16px"></i></div>
                <div><strong>Fee payment received</strong><span>Parent — Grade 8 — 2 min ago</span></div>
              </div>
              <div class="activity-item">
                <div class="activity-avatar activity-avatar--att"><i data-lucide="clipboard-check" style="width:16px"></i></div>
                <div><strong>Attendance marked</strong><span>Mr. Davis — Class 10A — 15 min ago</span></div>
              </div>
              <div class="activity-item">
                <div class="activity-avatar activity-avatar--adm"><i data-lucide="user-plus" style="width:16px"></i></div>
                <div><strong>New admission request</strong><span>Admissions Portal — 1 hr ago</span></div>
              </div>
              <div class="activity-item">
                <div class="activity-avatar activity-avatar--rep"><i data-lucide="file-text" style="width:16px"></i></div>
                <div><strong>Report card published</strong><span>Term 2 — All Grades — 3 hrs ago</span></div>
              </div>
            </div>
            <aside class="activity-summary">
              <div class="activity-stat"><strong>17</strong><span>Pending alerts today</span></div>
              <div class="activity-stat activity-stat--blue"><strong>118</strong><span>New admissions this month</span></div>
              <div class="activity-stat"><strong>Live</strong><span>Synced with EduPortal ERP</span></div>
            </aside>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Automation -->
  <section class="section automation" id="automation">
    <div class="automation-blob"></div>
    <div class="container">
      <div class="split reveal">
        <div class="split-text">
          <span class="section-label">Automation</span>
          <h2 class="section-title">Automate Routine Tasks &amp; Save Time</h2>
          <p class="section-subtitle" style="margin-left:0">Let EduPortal handle repetitive workflows so your team can focus on teaching and student success.</p>
          <div class="check-list">
            <div class="check-item">
              <span class="check-icon"><i data-lucide="check" style="width:14px"></i></span>
              <div><h4>Smart fee reminders</h4><p>Auto-send payment nudges via SMS, email, and in-app alerts before due dates.</p></div>
            </div>
            <div class="check-item">
              <span class="check-icon"><i data-lucide="check" style="width:14px"></i></span>
              <div><h4>Attendance workflows</h4><p>Trigger alerts to parents when students are absent or late without manual follow-up.</p></div>
            </div>
            <div class="check-item">
              <span class="check-icon"><i data-lucide="check" style="width:14px"></i></span>
              <div><h4>Report generation</h4><p>Schedule PDF report cards, payroll summaries, and compliance exports on autopilot.</p></div>
            </div>
          </div>
        </div>
        <div class="split-visual automation-visual">
          <div class="automation-glow" aria-hidden="true"></div>
          <div class="automation-panel">
            <div class="automation-panel-head">
              <div class="automation-panel-title">
                <i data-lucide="workflow"></i>
                Live Automation Queue
              </div>
              <div class="automation-live">
                <span class="automation-live-dot"></span>
                Running
              </div>
            </div>
            <div class="automation-stats">
              <div class="automation-stat"><strong>5</strong><span>Tasks today</span></div>
              <div class="automation-stat"><strong>248</strong><span>Notified</span></div>
              <div class="automation-stat"><strong>78%</strong><span>Complete</span></div>
            </div>
            <div class="workflow-list">
              <div class="workflow-item">
                <div class="workflow-icon workflow-icon--fee"><i data-lucide="bell-ring"></i></div>
                <strong>Fee reminder sent</strong>
                <span>248 parents via SMS &amp; app</span>
                <span class="workflow-badge workflow-badge--done">Done</span>
              </div>
              <div class="workflow-item">
                <div class="workflow-icon workflow-icon--att"><i data-lucide="clipboard-check"></i></div>
                <strong>Daily attendance synced</strong>
                <span>All classes — 9:15 AM</span>
                <span class="workflow-badge workflow-badge--done">Done</span>
              </div>
              <div class="workflow-item">
                <div class="workflow-icon workflow-icon--rep"><i data-lucide="file-output"></i></div>
                <strong>Weekly report generated</strong>
                <span>Delivered to Principal inbox</span>
                <span class="workflow-badge workflow-badge--done">Done</span>
              </div>
              <div class="workflow-item">
                <div class="workflow-icon workflow-icon--adm"><i data-lucide="user-check"></i></div>
                <strong>New student onboarding</strong>
                <span>Documents &amp; portal setup</span>
                <span class="workflow-badge workflow-badge--progress">Step 3/4</span>
              </div>
              <div class="workflow-item workflow-item--pending">
                <div class="workflow-icon workflow-icon--exam"><i data-lucide="calendar-clock"></i></div>
                <strong>Exam schedule draft</strong>
                <span>Awaiting admin approval</span>
                <span class="workflow-badge workflow-badge--pending">Pending</span>
              </div>
            </div>
            <div class="automation-panel-foot">
              <strong><i data-lucide="zap"></i> EduPortal AutoPilot</strong>
              <div class="automation-progress" aria-hidden="true"><span></span></div>
              <span>Next run in 12 min</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose Us -->
  <section class="section" id="why">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">Why EduPortal</span>
        <h2 class="section-title">Why Schools Choose EduPortal</h2>
        <p class="section-subtitle">Built for scale, security, and simplicity — from single campuses to multi-branch districts.</p>
      </div>
      <div class="why-grid reveal">
        <div class="why-card"><i data-lucide="lock" style="width:28px"></i><h4>Bank-level Security</h4><p>Encrypted data at rest and in transit with SOC 2 practices.</p></div>
        <div class="why-card"><i data-lucide="zap" style="width:28px"></i><h4>Lightning Fast</h4><p>Optimized cloud infrastructure for sub-second load times.</p></div>
        <div class="why-card"><i data-lucide="headphones" style="width:28px"></i><h4>24/7 Support</h4><p>Dedicated onboarding and round-the-clock help desk.</p></div>
        <div class="why-card"><i data-lucide="layers" style="width:28px"></i><h4>Multi-campus</h4><p>Manage branches, roles, and reporting from one dashboard.</p></div>
        <div class="why-card"><i data-lucide="smartphone" style="width:28px"></i><h4>Mobile-first</h4><p>Native iOS &amp; Android apps for parents and teachers.</p></div>
        <div class="why-card"><i data-lucide="puzzle" style="width:28px"></i><h4>Easy Integrations</h4><p>Connect with Google, Zoom, payment gateways, and more.</p></div>
        <div class="why-card"><i data-lucide="globe" style="width:28px"></i><h4>Cloud Anywhere</h4><p>Access your ERP securely from any device, anywhere.</p></div>
        <div class="why-card"><i data-lucide="trending-up" style="width:28px"></i><h4>Scalable Plans</h4><p>Grow from 100 to 10,000+ students without switching tools.</p></div>
        <div class="why-card"><i data-lucide="award" style="width:28px"></i><h4>Proven Results</h4><p>98% customer satisfaction across <?= ep_h(ep_site_metric('total_clients')) ?> institutions.</p></div>
      </div>
    </div>
  </section>

  <!-- Mobile Apps -->
  <section class="section mobile-apps" id="mobile-apps">
    <div class="container">
      <div class="mobile-apps-grid reveal">
        <div class="mobile-apps-content">
          <span class="section-label">Mobile Apps</span>
          <h2 class="section-title">Powerful Mobile Apps for Students, Teachers &amp; Executives</h2>
          <p class="section-subtitle">Stay connected anywhere with native Android apps — push alerts, live dashboards, and role-based access built for every stakeholder in your school ecosystem.</p>
          <div class="mobile-roles">
            <span class="mobile-role mobile-role--accent">Parents</span>
            <span class="mobile-role">Teachers</span>
            <span class="mobile-role">Students</span>
            <span class="mobile-role">Executives</span>
          </div>
          <div class="mobile-perks">
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="bell-ring"></i></div>
              <div><h4>Push Notifications</h4><p>Instant alerts for fees, homework, results, events, and emergency announcements.</p></div>
            </div>
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="bar-chart-3"></i></div>
              <div><h4>Real-time Analytics</h4><p>Live attendance, fee status, and performance insights on your phone.</p></div>
            </div>
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="message-circle"></i></div>
              <div><h4>Two-way Communication</h4><p>Chat with teachers, receive diary updates, and share feedback securely.</p></div>
            </div>
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="shield-check"></i></div>
              <div><h4>Secure &amp; Synced</h4><p>Bank-level security synced with your EduPortal ERP in real time.</p></div>
            </div>
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="video"></i></div>
              <div><h4>Online Lectures</h4><p>Access video lectures, study material, and date sheets on the go.</p></div>
            </div>
            <div class="mobile-perk">
              <div class="mobile-perk-icon"><i data-lucide="wallet"></i></div>
              <div><h4>Fee &amp; Results</h4><p>View fee history, download vouchers, and check exam results instantly.</p></div>
            </div>
          </div>
          <a href="https://play.google.com/store/apps/details?id=com.educationportal" target="_blank" rel="noopener noreferrer" class="feature-link" style="margin-top:1.25rem;display:inline-flex">Download on Google Play <i data-lucide="arrow-right" style="width:14px"></i></a>
        </div>
        <div class="app-showcase app-showcase--lg">
          <img src="assets/parent-app.jpg" alt="EduPortal mobile app for parents, teachers, and school executives" width="2452" height="4857" loading="lazy" decoding="async">
        </div>
      </div>
    </div>
  </section>
  <!-- Testimonials -->
  <section class="section testimonials" id="testimonials">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">Testimonials</span>
        <h2 class="section-title">What Our Users Say</h2>
        <p class="section-subtitle">Real stories from principals, directors, and school owners across Pakistan.</p>
      </div>
      <?php if ($homeTestimonials): ?>
      <div class="testimonial-grid reveal">
        <?php foreach ($homeTestimonials as $v):
          $vType     = ep_video_type($v);
          $thumbUrl  = ep_video_thumbnail_url($v);
          $fileUrl   = ep_video_file_url($v);
          if ($vType === 'upload' && $fileUrl === '') { $vType = 'youtube'; }
          $subtitle  = ep_video_subtitle($v);
          $ytId      = ep_h($v['youtube_video_id'] ?? '');
          $ytStart   = (int) ($v['youtube_start_seconds'] ?? 0);
          // Real destination for the card, mirroring videos.php:83-90. With JS
          // the inline openVideo() handler calls preventDefault() and opens the
          // modal, so this href never fires; without JS the card becomes an
          // ordinary link to the video rather than a dead <article
          // role="button"> that did nothing when clicked.
          $cardHref = '';
          if ($vType === 'youtube' && !empty($v['youtube_video_id'])) {
              $cardHref = 'https://www.youtube.com/watch?v=' . $v['youtube_video_id']
                  . ($ytStart > 0 ? '&t=' . $ytStart : '');
          } elseif ($vType === 'upload' && $fileUrl !== '') {
              $cardHref = $fileUrl;
          }
        ?>
        <a<?= $cardHref !== '' ? ' href="' . ep_h($cardHref) . '"' : ' role="button" tabindex="0"' ?> class="testimonial-card js-open-video"
          aria-label="<?= ep_h('Play testimonial from ' . $v['person_name']) ?>"
          data-video-type="<?= ep_h($vType) ?>"
          data-youtube-id="<?= $ytId ?>"
          data-youtube-start="<?= $ytStart ?>"
          data-video-src="<?= ep_h($fileUrl) ?>">
          <div class="testimonial-bg"<?= $thumbUrl ? ' data-custom-thumb="1"' : '' ?>>
            <?php if ($thumbUrl): ?>
            <img src="<?= ep_h($thumbUrl) ?>" alt="<?= ep_h($v['person_name']) ?>" width="480" height="320" loading="lazy" decoding="async">
            <?php endif; ?>
          </div>
          <span class="testimonial-play" aria-hidden="true"><span class="play-pulse"></span><i data-lucide="play"></i></span>
          <div class="testimonial-info"><strong><?= ep_h($v['person_name']) ?></strong><span><?= ep_h($subtitle) ?></span><?php if (!empty($v['description'])): ?><span class="testimonial-quote"><?= ep_h($v['description']) ?></span><?php endif; ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="testimonials-cta reveal">
        <p><strong>See 100+ school leaders</strong> share real video feedback on how EduPortal transformed their institutions.</p>
        <a href="videos.php" class="btn-videos">Watch All Video Reviews <i data-lucide="arrow-right" style="width:18px"></i></a>
        <p style="margin-top:1rem;font-size:0.875rem"><a href="pricing.php" class="feature-link">View pricing plans <i data-lucide="arrow-right" style="width:14px"></i></a></p>
      </div>
    </div>
  </section>

  <?php if ($homeGoogleReviews): ?>
  <!-- Google Reviews -->
  <section class="section home-google-reviews" id="google-reviews">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">Google Reviews</span>
        <h2 class="section-title">What Schools Say on Google</h2>
        <?php if ($homeGoogleReviewsSummary['average_rating'] !== '' && $homeGoogleReviewsSummary['total_count'] !== ''): ?>
        <p class="section-subtitle"><?= ep_h($homeGoogleReviewsSummary['average_rating']) ?> / 5 from <?= ep_h($homeGoogleReviewsSummary['total_count']) ?> Google reviews.</p>
        <?php endif; ?>
      </div>
      <p class="reviews-attribution-note reveal">Showing our 4- and 5-star Google reviews, newest first. <a href="<?= ep_h(ep_url('reviews.php')) ?>">See every review, including all ratings, on our full reviews page</a>.</p>
      <!--
        All review text below is server-rendered PHP, present in the raw
        HTML response exactly as it would be without JavaScript — the
        slider script (js/reviews-slider.js) only ever moves/animates this
        already-present markup (transform/visibility), it never fetches or
        injects review content. See STEP 20.
      -->
      <div class="reviews-slider reveal" data-reviews-slider>
        <div class="reviews-slider-viewport">
          <div class="reviews-slider-track">
            <?php foreach ($homeGoogleReviews as $r): ?>
            <article class="review-card reviews-slider-slide">
              <div class="review-card-header">
                <?php $avatarInitial = ep_h(mb_strtoupper(mb_substr($r['author_name'], 0, 1))); ?>
                <?php if (!empty($r['author_photo'])): ?>
                <?php // Falls back to the initial-letter avatar if Google's image
                      // URL has expired or is blocked, rather than showing a broken image. ?>
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
        </div>
        <div class="reviews-slider-nav">
          <button type="button" class="reviews-slider-arrow reviews-slider-prev" aria-label="Previous reviews">
            <i data-lucide="chevron-left" aria-hidden="true"></i>
          </button>
          <div class="reviews-slider-dots" role="tablist" aria-label="Review slides"></div>
          <button type="button" class="reviews-slider-arrow reviews-slider-next" aria-label="Next reviews">
            <i data-lucide="chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <div class="testimonials-cta reveal">
        <a href="<?= ep_h(ep_url('reviews.php')) ?>" class="btn-videos">See All Reviews <i data-lucide="arrow-right" style="width:18px"></i></a>
        <?php if ($homeGooglePlaceId !== ''): ?>
        <p style="margin-top:1rem;font-size:0.875rem">
          <a href="<?= ep_h(ep_google_maps_place_url($homeGooglePlaceId)) ?>" class="feature-link" target="_blank" rel="noopener noreferrer">View us on Google <i data-lucide="external-link" style="width:14px"></i></a>
        </p>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FAQs preview -->
  <section class="section home-faq" id="faqs">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-label">FAQs</span>
        <h2 class="section-title">Questions School Owners Ask</h2>
        <p class="section-subtitle">Quick answers about EduPortal school ERP in Pakistan — fees, apps, attendance, WhatsApp &amp; cloud hosting.</p>
      </div>
      <div class="home-faq-list reveal" id="homeFaqList">
        <?php foreach ($homeFaqs as $faq): ?>
        <div class="home-faq-item" data-id="<?= ep_h($faq['id']) ?>" data-category="<?= ep_h($faq['category']) ?>">
          <h3><button type="button" class="home-faq-q" aria-expanded="false">
            <span><?= ep_h($faq['q']) ?></span><i data-lucide="chevron-down"></i>
          </button></h3>
          <div class="home-faq-a"><div class="home-faq-a-inner"><p><?= ep_h($faq['a']) ?></p></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="home-faq-cta reveal">
        <a href="faqs.php" class="btn btn-primary">View All FAQs <i data-lucide="arrow-right" style="width:18px"></i></a>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/includes/partials/footer.php'; ?>

  <!-- Pricing anchor (minimal) -->
  <div id="pricing" style="height:0;overflow:hidden" aria-hidden="true"></div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof lucide !== 'undefined') lucide.createIcons();
      if (window.initEduportalVideoThumbs) window.initEduportalVideoThumbs();
      if (window.initEduportalReviewsSlider) window.initEduportalReviewsSlider();

      const navbar = document.getElementById('navbar');
      const navToggle = document.getElementById('navToggle');
      const mobileMenu = document.getElementById('mobileMenu');

      window.addEventListener('scroll', () => navbar?.classList.toggle('scrolled', window.scrollY > 20));

      navToggle?.addEventListener('click', () => {
        mobileMenu?.classList.toggle('open');
        const icon = navToggle.querySelector('[data-lucide]');
        if (icon) {
          icon.setAttribute('data-lucide', mobileMenu?.classList.contains('open') ? 'x' : 'menu');
          lucide?.createIcons();
        }
      });
      mobileMenu?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.remove('open')));

      /* Scroll lock helper (fixes hero popup scrollbar) */
      let savedScrollY = 0;
      const lockScroll = () => {
        savedScrollY = window.scrollY;
        document.body.classList.add('modal-open');
        document.body.style.top = `-${savedScrollY}px`;
      };
      const unlockScroll = () => {
        if (modal.classList.contains('open') || videoModal.classList.contains('open')) return;
        document.body.classList.remove('modal-open');
        document.body.style.top = '';
        window.scrollTo(0, savedScrollY);
      };

      /* Get Started modal */
      const modal = document.getElementById('getStartedModal');
      const form = document.getElementById('getStartedForm');
      const countrySelect = document.getElementById('countryCode');
      const whatsappInput = document.getElementById('whatsapp');
      const whatsappFull = document.getElementById('whatsappFull');

      const phonePlaceholders = {
        PK: '300 1234567',
        US: '555 123 4567',
        GB: '7700 900123',
        AE: '50 123 4567',
        SA: '50 123 4567',
        IN: '98765 43210',
      };

      function changeCountryCode() {
        const iso = countrySelect?.value || 'PK';
        const dial = window.getSelectedDialCode?.(countrySelect) || '92';
        if (whatsappInput) {
          whatsappInput.placeholder = phonePlaceholders[iso] || 'Phone number';
        }
        if (whatsappFull && whatsappInput) {
          const num = whatsappInput.value.replace(/\D/g, '');
          whatsappFull.value = num ? '+' + dial + num : '';
        }
      }

      function initCountrySelect() {
        if (!countrySelect || !window.populateCountryCodeSelect) return;
        const applyDefault = (iso) => {
          window.populateCountryCodeSelect(countrySelect, iso);
          changeCountryCode();
        };
        applyDefault(window.detectDefaultCountryIso?.() || 'PK');
        countrySelect.addEventListener('change', changeCountryCode);
        whatsappInput?.addEventListener('input', changeCountryCode);
      }

      initCountrySelect();

      const openModal = (e) => {
        e?.preventDefault();
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        lockScroll();
        mobileMenu.classList.remove('open');
        lucide?.createIcons();
        requestAnimationFrame(() => document.getElementById('instituteName')?.focus());
      };
      const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        unlockScroll();
      };
      document.querySelectorAll('.js-open-modal').forEach(btn => {
        btn.addEventListener('click', openModal);
      });
      document.getElementById('modalCancel')?.addEventListener('click', closeModal);
      modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
      form?.addEventListener('submit', (e) => {
        e.preventDefault();
        changeCountryCode();
        if (!window.handleLeadFormSubmit) return;
        window.handleLeadFormSubmit(form, {
          onSuccess: closeModal,
          onReset: initCountrySelect,
        });
      });

      /* Video reviews modal */
      const videoModal    = document.getElementById('videoModal');
      const youtubeFrame  = document.getElementById('youtubeFrame');
      const html5Player   = document.getElementById('html5VideoPlayer');
      const openVideo = (e) => {
        e?.preventDefault();
        const card      = e?.currentTarget;
        const vType     = card?.dataset.videoType || 'youtube';
        const ytId      = card?.dataset.youtubeId  || <?= json_encode($demoVideo['youtube_id']) ?>;
        const ytStart   = parseInt(card?.dataset.youtubeStart || '0', 10);
        const videoSrc  = card?.dataset.videoSrc   || '';
        if (vType === 'upload' && videoSrc) {
          youtubeFrame.style.display = 'none';
          youtubeFrame.src = '';
          html5Player.style.display = '';
          html5Player.src = videoSrc;
          html5Player.play?.();
        } else {
          if (html5Player) { html5Player.style.display = 'none'; html5Player.src = ''; }
          youtubeFrame.style.display = '';
          youtubeFrame.src = `https://www.youtube-nocookie.com/embed/${ytId}?start=${ytStart}&autoplay=1&rel=0&playsinline=1`;
        }
        videoModal.classList.add('open');
        videoModal.setAttribute('aria-hidden', 'false');
        lockScroll();
      };
      const closeVideo = () => {
        videoModal.classList.remove('open');
        videoModal.setAttribute('aria-hidden', 'true');
        youtubeFrame.src = '';
        if (html5Player) { html5Player.pause?.(); html5Player.src = ''; html5Player.style.display = 'none'; }
        youtubeFrame.style.display = '';
        unlockScroll();
      };
      document.querySelectorAll('.js-open-video').forEach(el => {
        el.addEventListener('click', openVideo);
        el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openVideo(e); } });
      });
      document.getElementById('videoClose')?.addEventListener('click', closeVideo);
      videoModal?.addEventListener('click', (e) => { if (e.target === videoModal) closeVideo(); });

      document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (videoModal.classList.contains('open')) closeVideo();
        else if (modal.classList.contains('open')) closeModal();
      });

      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
      }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
      document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    });
  </script>
<?php $epTrackPage = 'home'; require __DIR__ . '/includes/partials/public-track.php'; ?>
</body>
</html>
