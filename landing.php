<?php
require_once __DIR__ . '/includes/cms.php';
$lpVideos = ep_get_video_testimonials(true);

$pageTitle        = 'Special Offer — EduPortal School ERP | Free Demo';
$pageDescription  = 'Limited offer: EduPortal school ERP — digital attendance, fee automation, parent app &amp; ' . ep_site_metric('total_clients') . ' schools trust us. Book free demo today.';
$navActive        = '';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/landing.css', 'css/video-cards.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/video-thumbs.js', 'js/landing.js'];
$epTrackPage      = 'landing';

$extraHeadHtml = <<<'HTML'
  <meta name="robots" content="noindex, nofollow">
  <meta property="og:title" content="EduPortal — Digitize Your School This Week">
  <meta property="og:description" content="Digital attendance, fee automation &amp; parent app. 500+ schools. Limited-time free setup offer.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;
$extraHeadHtml = str_replace('500+ schools', ep_h(ep_site_metric('total_clients')) . ' schools', $extraHeadHtml);

// Organization JSON-LD is emitted automatically by includes/head.php for every page.

// Override robots meta: head.php outputs "index, follow" by default, but extraHeadHtml overrides with noindex
// We need to suppress the default robots meta. Use $inlineStyles trick is not available.
// Instead, override via $extraHeadHtml which is output after the default meta robots.
// The noindex in extraHeadHtml will be the last robots meta, which crawlers honor.

require __DIR__ . '/includes/head.php';
// NOTE: Do NOT include header.php — landing has its own custom header
?>

  <!-- Urgency countdown -->
  <div class="lp-urgency" role="status" aria-live="polite">
    <div class="lp-container lp-urgency-inner">
      <span><strong>Facebook Exclusive:</strong> Free onboarding + setup support ends in</span>
      <div class="lp-countdown" id="lpCountdown" aria-label="Offer countdown timer"></div>
      <button type="button" class="lp-header-cta js-open-modal" style="padding:0.4rem 0.9rem;font-size:0.75rem">Get Started</button>
    </div>
  </div>

  <header class="lp-header">
    <div class="lp-container lp-header-inner">
      <a href="#" class="lp-logo">
        <?php
        $lpLogo = trim((string) ep_setting('logo_path', ''));
        if ($lpLogo === '' || !is_file(__DIR__ . '/' . ltrim($lpLogo, '/'))) {
            $lpLogo = 'assets/logo_icon.jpg';
        }
        ?>
        <img src="<?= ep_h($lpLogo) ?>" alt="EduPortal" width="222" height="223">
        <span>EduPortal</span>
      </a>
      <button type="button" class="lp-header-cta js-open-modal">Get Started</button>
    </div>
  </header>

  <!-- Hero -->
  <section class="lp-hero">
    <div class="lp-container lp-hero-grid">
      <div>
        <span class="lp-badge"><i data-lucide="zap"></i> Trusted by <?= ep_h(ep_site_metric('total_clients')) ?> Schools in Pakistan</span>
        <h1>Stop Losing Time on <em>Paper Registers &amp; Fee Chaos</em></h1>
        <p class="lp-hero-lead">EduPortal replaces manual attendance, fee slips, and endless parent calls with one modern ERP — plus a parents app your families will actually use.</p>
        <div class="lp-hero-ctas">
          <button type="button" class="lp-btn lp-btn--primary lp-btn--lg js-open-modal">Get Started <i data-lucide="arrow-right"></i></button>
          <button type="button" class="lp-btn lp-btn--video lp-btn--lg js-open-video"><i data-lucide="play"></i> Watch Demo</button>
        </div>
        <ul class="lp-trust-row">
          <li><i data-lucide="shield-check"></i> Secure cloud ERP</li>
          <li><i data-lucide="smartphone"></i> Parent mobile app</li>
          <li><i data-lucide="clock"></i> Go live in days, not months</li>
        </ul>
      </div>
      <div class="lp-hero-visual">
        <img src="assets/dashboard.png" alt="EduPortal school management dashboard" width="1200" height="750" fetchpriority="high">
      </div>
    </div>
  </section>

  <!-- Pain points -->
  <section class="lp-section lp-section--alt" id="solutions">
    <div class="lp-container">
      <div class="lp-section-head">
        <span class="lp-label">Your Problems, Solved</span>
        <h2>Every Pain Point School Owners Tell Us — Fixed</h2>
        <p>Principals and owners switch to EduPortal because it solves daily headaches that cost time, money, and parent trust.</p>
      </div>
      <div class="lp-pain-grid">
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="clipboard-list"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Paper attendance, errors, no parent alerts</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>Digital attendance</strong> — manual or biometric, instant SMS/app alerts to parents</p>
        </article>
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="banknote"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Long fee queues, defaulters, lost receipts</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>Auto fee vouchers</strong>, reminders, online payment &amp; clean accounts reports</p>
        </article>
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="phone-off"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Office flooded with parent phone calls</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>ParentsConnect app</strong> — fees, results, homework &amp; notices on their phone</p>
        </article>
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="file-x"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Result cards take weeks, retyping marks</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>Exams &amp; report cards</strong> generated in clicks, publish to parents instantly</p>
        </article>
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="folder-x"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Student files lost, no single profile</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>Digital student records</strong> — admission to alumni in one searchable profile</p>
        </article>
        <article class="lp-pain-card">
          <div class="lp-pain-icon"><i data-lucide="eye-off"></i></div>
          <p class="lp-pain-before"><i data-lucide="x-circle"></i> Principal has no real-time visibility</p>
          <p class="lp-pain-after"><i data-lucide="check-circle"></i> <strong>Live dashboards</strong> — attendance %, collections, and activity at a glance</p>
        </article>
      </div>
      <div class="lp-guarantee">
        <span><i data-lucide="headphones"></i> Free training for your staff</span>
        <span><i data-lucide="refresh-cw"></i> Dedicated support team</span>
        <span><i data-lucide="map-pin"></i> Local team in Shakargarh, Pakistan</span>
      </div>
    </div>
  </section>

  <!-- AI-Powered Features -->
  <section class="lp-section" id="ai-powered">
    <div class="lp-container">
      <div class="lp-section-head">
        <span class="lp-label" style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);color:#fff;padding:0.35rem 1.1rem;border-radius:2rem">Only AI-Powered School ERP in Pakistan</span>
        <h2>The Only School Software That <em>Genuinely</em> Uses AI</h2>
        <p>Not just a label — EduPortal's AI automates the tasks your staff spend hours on every week, built right into the features you already use.</p>
      </div>
      <div class="lp-pain-grid" style="margin-top:2rem">
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="calendar-clock"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">AI Timetable in Minutes</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">Generates clash-free class timetables for all sections and teachers — a task that used to take days, done in minutes.</p>
        </article>
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="scan-face"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">Smart Digital Attendance</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">AI flags chronic absentees, sends automatic parent alerts, and generates monthly attendance analytics — all without manual effort.</p>
        </article>
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="calendar-range"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">Automated Datesheet</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">AI builds balanced exam datesheets with zero subject clashes — and publishes to students and parents instantly.</p>
        </article>
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="file-badge"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">Instant Report Cards</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">Marks entered once — AI aggregates results, calculates grades, and generates formatted report cards shared with parents in one click.</p>
        </article>
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="bell-dot"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">Smart Fee Reminders</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">AI identifies defaulters and auto-sends personalized SMS &amp; WhatsApp fee reminders — better collection without awkward calls.</p>
        </article>
        <article class="lp-pain-card" style="border-top:3px solid #6366f1">
          <div class="lp-pain-icon" style="background:#ede9fe;color:#6366f1"><i data-lucide="message-square-text"></i></div>
          <h4 style="font-weight:700;margin-bottom:0.4rem">AI Notice Generator</h4>
          <p style="font-size:0.875rem;color:var(--lp-muted)">Draft school circulars and announcements in seconds. AI writes them — staff just review and publish to all parents instantly.</p>
        </article>
      </div>
      <div style="text-align:center;margin-top:2.5rem">
        <button type="button" class="lp-btn lp-btn--primary lp-btn--lg js-open-modal">See AI Features in Action <i data-lucide="arrow-right"></i></button>
      </div>
    </div>
  </section>

  <!-- Digital attendance spotlight -->
  <section class="lp-section">
    <div class="lp-container">
      <div class="lp-spotlight">
        <div>
          <span class="lp-label">#1 Requested Feature</span>
          <h2 style="font-size:1.75rem;font-weight:800;margin-bottom:1rem;letter-spacing:-0.02em">Digital Attendance That Parents Trust</h2>
          <p style="color:var(--lp-muted);margin-bottom:1.25rem">Mark attendance in seconds. Parents get notified immediately — fewer disputes, better punctuality, complete audit trail for inspections.</p>
          <ul class="lp-spotlight-list">
            <li><i data-lucide="check"></i> Class-wise daily &amp; monthly reports</li>
            <li><i data-lucide="check"></i> SMS + app push to guardians</li>
            <li><i data-lucide="check"></i> Teacher app for quick marking</li>
            <li><i data-lucide="check"></i> Absentee alerts for admin office</li>
          </ul>
          <button type="button" class="lp-btn lp-btn--primary js-open-modal" style="margin-top:0.5rem">Get Started</button>
        </div>
        <img src="assets/parent-app.jpg" alt="EduPortal parent app showing attendance and school updates" class="lp-spotlight-img" width="2452" height="4857" loading="lazy" decoding="async">
      </div>
    </div>
  </section>

  <!-- Video testimonials -->
  <section class="lp-section lp-section--alt" id="videos">
    <div class="lp-container">
      <div class="lp-section-head">
        <span class="lp-label">Real Schools, Real Stories</span>
        <h2>100+ Video Testimonials from Principals &amp; Owners</h2>
        <p>Hear why school leaders across Pakistan chose EduPortal — not actors, not scripts.</p>
      </div>
      <div class="lp-video-grid">
        <?php foreach ($lpVideos as $v):
          $thumbUrl  = ep_video_thumbnail_url($v);
          $vType     = ep_video_type($v);
          $ytId      = $v['youtube_video_id'] ?? '';
          // Only link to a target that actually exists. Several uploaded
          // testimonial rows reference files that are no longer on disk, and
          // this is a paid-traffic landing page — a card that 404s wastes a
          // click, so those cards are skipped entirely.
          $videoHref = '';
          if ($vType === 'youtube' && $ytId) {
              $videoHref = 'https://www.youtube.com/watch?v=' . urlencode($ytId);
          } else {
              $relPath = trim((string) ($v['video_file_path'] ?? ''));
              if ($relPath !== '' && is_file(__DIR__ . '/' . ltrim($relPath, '/'))) {
                  $videoHref = ep_video_file_url($v);
              }
          }
          if ($videoHref === '') { continue; }
          $subtitle  = ep_video_subtitle($v);
        ?>
        <a href="<?= ep_h($videoHref) ?>" class="lp-video-card" target="_blank" rel="noopener noreferrer">
          <div class="lp-video-bg"<?= $thumbUrl ? ' data-custom-thumb="1"' : '' ?>>
            <?php if ($thumbUrl): ?>
            <img src="<?= ep_h($thumbUrl) ?>" alt="<?= ep_h($v['person_name']) ?>" width="480" height="320" loading="lazy" decoding="async">
            <?php endif; ?>
          </div>
          <span class="lp-video-play" aria-hidden="true"><span class="play-pulse"></span><i data-lucide="play"></i></span>
          <div class="lp-video-meta">
            <strong><?= ep_h($v['person_name']) ?></strong>
            <span><?= ep_h($subtitle) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <p style="text-align:center;margin-top:1.5rem">
        <a href="videos.php" style="color:var(--lp-primary);font-weight:600">Watch all 100+ testimonials &rarr;</a>
      </p>
    </div>
  </section>

  <!-- Pricing -->
  <section class="lp-section" id="lp-offer">
    <div class="lp-container">
      <div class="lp-section-head">
        <span class="lp-label">Transparent Pricing</span>
        <h2>Plans That Scale With Your School</h2>
        <p>Start small or go full ERP — every plan includes core attendance, fees &amp; student records. <strong style="color:var(--lp-primary)">Save 10% on yearly billing.</strong></p>
      </div>
      <div class="lp-billing-wrap">
        <div class="lp-billing-toggle" role="group" aria-label="Billing period">
          <button type="button" class="lp-billing-option" data-billing="monthly" aria-pressed="false">Monthly</button>
          <button type="button" class="lp-billing-option is-active" data-billing="yearly" aria-pressed="true">
            Yearly <span class="lp-billing-save">Save 10%</span>
          </button>
        </div>
        <p class="lp-billing-hint" id="lpBillingHint">Yearly billing: 10% less per month — pay once per year (equivalent monthly price shown).</p>
      </div>
      <div class="lp-pricing-grid">
        <article class="lp-price-card">
          <div class="lp-price-head">
            <h3>Silver</h3>
            <p class="lp-price-amount">
              <span class="lp-price-currency">Rs.</span>
              <span class="lp-price-value" data-monthly="4000" data-yearly="3600">3,600</span>
            </p>
            <p class="lp-price-students">/ month · up to 300 students</p>
            <p class="lp-price-yearly-total">Billed Rs. 43,200 / year</p>
          </div>
          <div class="lp-price-body">
            <p style="font-size:0.875rem;color:var(--lp-muted)">Perfect for smaller schools starting digital operations.</p>
            <ul>
              <li><i data-lucide="check"></i> Student records &amp; attendance</li>
              <li><i data-lucide="check"></i> Fee management &amp; vouchers</li>
              <li><i data-lucide="check"></i> Exams &amp; report cards</li>
              <li><i data-lucide="check"></i> SMS &amp; WhatsApp alerts</li>
            </ul>
            <button type="button" class="lp-btn lp-btn--outline lp-btn-block js-open-modal">Get Started</button>
          </div>
        </article>
        <article class="lp-price-card lp-price-card--featured">
          <span class="lp-price-tag">Most Popular</span>
          <div class="lp-price-head">
            <h3>Gold</h3>
            <p class="lp-price-amount">
              <span class="lp-price-currency">Rs.</span>
              <span class="lp-price-value" data-monthly="7000" data-yearly="6300">6,300</span>
            </p>
            <p class="lp-price-students">/ month · up to 800 students</p>
            <p class="lp-price-yearly-total">Billed Rs. 75,600 / year</p>
          </div>
          <div class="lp-price-body">
            <p style="font-size:0.875rem;color:var(--lp-muted)">Best for growing schools — includes parent &amp; teacher apps.</p>
            <ul>
              <li><i data-lucide="check"></i> Everything in Silver</li>
              <li><i data-lucide="check"></i> Parents &amp; teacher mobile app</li>
              <li><i data-lucide="check"></i> Timetable &amp; academic planning</li>
              <li><i data-lucide="check"></i> Canteen POS system</li>
            </ul>
            <button type="button" class="lp-btn lp-btn--primary lp-btn-block js-open-modal">Get Started</button>
          </div>
        </article>
        <article class="lp-price-card">
          <div class="lp-price-head">
            <h3>Diamond</h3>
            <p class="lp-price-amount">
              <span class="lp-price-currency">Rs.</span>
              <span class="lp-price-value" data-monthly="12000" data-yearly="10800">10,800</span>
            </p>
            <p class="lp-price-students">/ month · up to 1200 students</p>
            <p class="lp-price-yearly-total">Billed Rs. 129,600 / year</p>
          </div>
          <div class="lp-price-body">
            <p style="font-size:0.875rem;color:var(--lp-muted)">Enterprise suite for multi-campus &amp; large institutions.</p>
            <ul>
              <li><i data-lucide="check"></i> Everything in Gold</li>
              <li><i data-lucide="check"></i> Hostel management</li>
              <li><i data-lucide="check"></i> Multi-campus dashboard</li>
              <li><i data-lucide="check"></i> Premium customization</li>
            </ul>
            <button type="button" class="lp-btn lp-btn--outline lp-btn-block js-open-modal">Get Started</button>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- CTA strip -->
  <section class="lp-section lp-form-section" id="lp-form">
    <div class="lp-container">
      <div class="lp-form-box">
        <h2>Ready to Get Started?</h2>
        <p>Book a free personalized demo — see digital attendance, fees, and parent app live for your school.</p>
        <button type="button" class="lp-btn lp-btn--primary lp-btn-block lp-btn--lg js-open-modal">Get Started <i data-lucide="arrow-right"></i></button>
        <button type="button" class="lp-btn lp-btn--video lp-btn-block js-open-video" style="margin-top:0.75rem"><i data-lucide="play"></i> Watch Demo First</button>
        <p style="text-align:center;font-size:0.8125rem;color:var(--lp-muted);margin-top:1rem">
          Or call <a href="tel:+923091920336" style="color:var(--lp-primary)">+92 309 1920336</a> ·
          <a href="https://wa.me/923091920336" target="_blank" rel="noopener noreferrer" style="color:var(--lp-primary)">WhatsApp</a>
        </p>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="lp-final-cta">
    <div class="lp-container">
      <div class="lp-countdown" id="lpCountdownBottom" aria-hidden="true"></div>
      <h2>Every Day on Paper Costs You Money</h2>
      <p>Join <?= ep_h(ep_site_metric('total_clients')) ?> schools that already digitized attendance, fees, and parent communication. This week's onboarding offer ends Sunday.</p>
      <div style="display:flex;flex-wrap:wrap;gap:0.75rem;justify-content:center">
        <button type="button" class="lp-btn lp-btn--primary lp-btn--lg js-open-modal">Get Started</button>
        <button type="button" class="lp-btn lp-btn--video lp-btn--lg js-open-video"><i data-lucide="play"></i> Watch Demo</button>
      </div>
    </div>
  </section>

  <a href="https://wa.me/923091920336?text=Hi%2C%20I%20saw%20EduPortal%20on%20Facebook" class="lp-fab" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
    <i data-lucide="message-circle"></i>
  </a>

  <!-- Watch Demo Modal -->
  <div class="video-modal-overlay" id="videoModal" role="dialog" aria-modal="true" aria-label="Product demo video" aria-hidden="true">
    <div class="video-modal">
      <button type="button" class="video-close" id="videoClose" aria-label="Close video">&times;</button>
      <iframe id="youtubeFrame" title="EduPortal demo video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
