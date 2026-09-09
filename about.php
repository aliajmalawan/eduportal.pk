<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle       = 'About Us — EduPortal | School Management Software Since 2018';
$pageDescription = 'Learn about EduPortal — Pakistan\'s trusted school management ERP since 2018, proudly serving ' . ep_site_metric('total_clients') . ' institutes across the country with AI-powered tools for fees, attendance, and parent communication.';
$canonicalUrl    = ep_canonical_url();
$navActive       = 'about';
$useDemoModal    = true;
$extraStylesheets = ['css/shared.css', 'css/about.css'];
$epTrackPage     = 'about';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main>

  <!-- Hero -->
  <section class="page-hero about-hero">
    <div class="container">
      <span class="section-label">Our Story</span>
      <h1>Built in Pakistan.<br>Trusted Across the Nation.</h1>
      <p>Since 2018, EduPortal has been helping schools work smarter — automating fees, attendance, results, and parent communication so educators can focus on what truly matters.</p>
    </div>
  </section>

  <!-- Stats strip -->
  <section class="about-stats-strip">
    <div class="container">
      <div class="about-stats-grid">
        <div class="about-stat">
          <strong>2018</strong>
          <span>Founded</span>
        </div>
        <div class="about-stat">
          <strong><?= ep_h(ep_site_metric('total_clients')) ?></strong>
          <span>Institutes Served</span>
        </div>
        <div class="about-stat">
          <strong><?= ep_h(ep_setting('total_cities', '') !== '' ? ep_setting('total_cities') : 'Nationwide') ?></strong>
          <span>Coverage Across Pakistan</span>
        </div>
        <div class="about-stat">
          <strong><?= ep_h(ep_site_metric('total_students')) ?></strong>
          <span>Students Managed Daily</span>
        </div>
      </div>
      <?php $metricsDate = ep_setting('metrics_updated_date', ''); ?>
      <?php if ($metricsDate !== ''): ?>
      <p class="about-stats-note">Verified as of <?= ep_h($metricsDate) ?></p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Mission -->
  <section class="section about-mission">
    <div class="container">
      <div class="about-split">
        <div class="about-split-text">
          <span class="section-label">Who We Are</span>
          <h2 class="section-title">Empowering Schools with Smart Technology</h2>
          <p>EduPortal was founded in 2018 with a single mission: to make world-class school management technology accessible to every institute in Pakistan — from small community schools to large multi-campus networks.</p>
          <p>We started small, with a passionate team and a belief that schools deserve better tools. Today, we proudly serve <strong><?= ep_h(ep_site_metric('total_clients')) ?> institutes</strong> across the country, helping principals, teachers, parents, and students stay connected and informed every day.</p>
          <p>Our platform brings together fee management, digital attendance, exam results, parent communication, timetabling, and much more under one unified ERP — built for the realities of Pakistani education.</p>
        </div>
        <div class="about-split-visual">
          <div class="about-visual-card">
            <div class="about-vc-row">
              <div class="about-vc-icon" style="background:#e0f2fe;color:#0284c7"><i data-lucide="graduation-cap"></i></div>
              <div><strong>Student-First Design</strong><p>Every feature is built around real classroom needs.</p></div>
            </div>
            <div class="about-vc-row">
              <div class="about-vc-icon" style="background:#dcfce7;color:#16a34a"><i data-lucide="shield-check"></i></div>
              <div><strong>Trusted & Secure</strong><p>Bank-level data security with role-based access control.</p></div>
            </div>
            <div class="about-vc-row">
              <div class="about-vc-icon" style="background:#fef3c7;color:#d97706"><i data-lucide="headphones"></i></div>
              <div><strong>Dedicated Support</strong><p>Our team is always a call or message away.</p></div>
            </div>
            <div class="about-vc-row">
              <div class="about-vc-icon" style="background:#ede9fe;color:#7c3aed"><i data-lucide="zap"></i></div>
              <div><strong>Continuous Innovation</strong><p>AI-powered features released regularly.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Journey timeline -->
  <section class="section about-journey">
    <div class="container">
      <div class="section-header">
        <span class="section-label">Our Journey</span>
        <h2 class="section-title">7 Years of Growth</h2>
        <p class="section-subtitle">From a small startup idea to Pakistan's leading school ERP platform.</p>
      </div>
      <div class="timeline">
        <div class="timeline-item">
          <div class="timeline-year">2018</div>
          <div class="timeline-content">
            <h4>Founded</h4>
            <p>EduPortal was established with a core focus on fee management and student records for schools in Punjab.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2019</div>
          <div class="timeline-content">
            <h4>First 50 Schools</h4>
            <p>Reached 50 partner institutes. Launched digital attendance tracking and SMS notification features.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2020</div>
          <div class="timeline-content">
            <h4>Mobile App Launch</h4>
            <p>Launched the parent and teacher mobile apps on Android, enabling real-time communication during challenging times.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2021</div>
          <div class="timeline-content">
            <h4>100+ Institutes</h4>
            <p>Crossed the 100-institute milestone. Expanded to multi-campus management and WhatsApp integration.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2022</div>
          <div class="timeline-content">
            <h4>AI Features Introduced</h4>
            <p>Introduced smart analytics, face-recognition attendance, and automated report generation powered by AI.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2023</div>
          <div class="timeline-content">
            <h4>500+ Institutes Nationwide</h4>
            <p>Expanded operations beyond Punjab to serve schools across Khyber Pakhtunkhwa, Sindh, and Azad Kashmir.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-year">2024–25</div>
          <div class="timeline-content">
            <h4>700+ and Growing</h4>
            <p>Proudly serving 700+ institutes. Launched advanced LMS, exam datasheets, canteen POS, and hostel management.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Values -->
  <section class="section about-values">
    <div class="container">
      <div class="section-header section-header--left">
        <span class="section-label">Our Values</span>
        <h2 class="section-title">What Drives Us Every Day</h2>
      </div>
      <div class="values-grid">
        <div class="value-card">
          <div class="value-icon"><i data-lucide="heart"></i></div>
          <h4>Passion for Education</h4>
          <p>We believe technology can unlock the full potential of every student, teacher, and school leader in Pakistan.</p>
        </div>
        <div class="value-card">
          <div class="value-icon"><i data-lucide="users"></i></div>
          <h4>Community First</h4>
          <p>Built by people who understand local schools — our solutions are shaped by feedback from real educators.</p>
        </div>
        <div class="value-card">
          <div class="value-icon"><i data-lucide="shield-check"></i></div>
          <h4>Trust & Transparency</h4>
          <p>We keep data safe, pricing clear, and communication honest. No hidden fees, no surprises.</p>
        </div>
        <div class="value-card">
          <div class="value-icon"><i data-lucide="trending-up"></i></div>
          <h4>Relentless Improvement</h4>
          <p>We ship updates every month based on what our schools actually need — not what looks good on paper.</p>
        </div>
        <div class="value-card">
          <div class="value-icon"><i data-lucide="globe"></i></div>
          <h4>Made for Pakistan</h4>
          <p>Urdu support, PKR billing, local bank integrations, and features tuned for how Pakistani schools operate.</p>
        </div>
        <div class="value-card">
          <div class="value-icon"><i data-lucide="award"></i></div>
          <h4>Proven Track Record</h4>
          <p>With <?= ep_h(ep_site_metric('total_clients')) ?> schools and 98% satisfaction, our results speak for themselves — and we're just getting started.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <?php
  // Company facts, stated as plain labelled text. This is deliberately not a
  // graphic: it is the form a search engine or an AI assistant can quote, and
  // it answers the questions buyers actually ask about who they are dealing
  // with. Facts that have not been filled in are omitted, never guessed.
  $companyFacts = ep_company_facts();
  ?>
  <?php if ($companyFacts): ?>
  <section class="section about-facts" id="company-facts" aria-labelledby="company-facts-heading">
    <div class="container">
      <div class="section-header section-header--split">
        <span class="section-label">Company information</span>
        <h2 id="company-facts-heading">EduPortal at a glance</h2>
        <p>The details buyers ask for, in one place.</p>
      </div>

      <dl class="about-facts-list">
        <?php foreach ($companyFacts as $fact): ?>
        <div class="about-fact">
          <dt><?= ep_h($fact['label']) ?></dt>
          <dd><?= ep_h($fact['value']) ?></dd>
        </div>
        <?php endforeach; ?>
      </dl>

      <?php
      $street = trim((string) ep_setting('office_street', ''));
      $city = trim((string) ep_setting('office_city', ''));
      $region = trim((string) ep_setting('office_region', ''));
      $postal = trim((string) ep_setting('office_postal', ''));
      $email = trim((string) ep_setting('support_email', ''));
      $addressLine = implode(', ', array_filter([$street, $city, $region, $postal]));
      ?>
      <?php if ($addressLine !== '' || $email !== ''): ?>
      <p class="about-facts-address">
        <?php if ($addressLine !== ''): ?>
        <strong>Head office address:</strong> <?= ep_h($addressLine) ?>.
        <?php endif; ?>
        <?php if ($email !== ''): ?>
        <strong>Contact:</strong> <a href="mailto:<?= ep_h($email) ?>"><?= ep_h($email) ?></a>.
        <?php endif; ?>
      </p>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="about-cta">
    <div class="container">
      <h2>Ready to Join <?= ep_h(ep_site_metric('total_clients')) ?> Schools?</h2>
      <p>Let us show you how EduPortal can transform your institute — book a free personalized demo today.</p>
      <button type="button" class="btn btn-primary btn-lg js-open-modal">
        Book a Free Demo <i data-lucide="arrow-right"></i>
      </button>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
