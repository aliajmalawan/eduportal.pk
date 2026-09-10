<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'All School ERP Features — Student, Fees, Exams &amp; Parent App | EduPortal';
$pageDescription  = 'Explore 22+ EduPortal features: student records, digital attendance, fee management, exams, parent mobile app, finance, hostel and multi-campus ERP.';
$canonicalUrl     = ep_canonical_url();
$navActive        = 'features';
$useDemoModal     = false;
$extraStylesheets = ['css/shared.css'];
$epTrackPage      = 'features';
$inlineStyles     = '
    .nav-links a.active { font-weight: 600; color: var(--color-text); }

    .social-links { display: flex; gap: 0.75rem; }
    .social-links a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      border: 1px solid var(--color-border);
      color: var(--color-text-muted);
      transition: color var(--transition), border-color var(--transition);
    }
    .social-links a:hover { color: var(--color-primary); border-color: var(--color-primary); }
    .footer-contact li {
      display: flex;
      gap: 0.5rem;
      font-size: 0.875rem;
      color: var(--color-text-muted);
    }
    .footer-bottom-links { display: flex; gap: 1.5rem; }
    .footer-bottom-links a:hover { color: var(--color-primary); }

    /* Category sections */
    .feat-category { padding: 2rem 0 1rem; }
    .feat-category:nth-child(even) { background: var(--color-bg-alt); }
    .feat-category-header {
      text-align: center;
      max-width: 720px;
      margin: 0 auto 2.5rem;
    }
    .feat-category-header h2 {
      font-size: clamp(1.5rem, 3vw, 2rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 0.5rem;
    }
    .feat-category-header p {
      color: var(--color-text-muted);
      font-size: 1rem;
    }

    /* Individual feature blocks */
    .feat-block {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      align-items: center;
      padding: 2.75rem 0;
      border-bottom: 1px solid var(--color-border);
    }
    .feat-category .feat-block:last-child { border-bottom: none; }
    @media (min-width: 992px) {
      .feat-block {
        grid-template-columns: 1fr 1fr;
        gap: 3.5rem;
        padding: 3.25rem 0;
      }
      .feat-block--right .feat-visual { order: 2; }
      .feat-block--right .feat-content { order: 1; text-align: right; }
    }

    .feat-visual {
      display: flex;
      justify-content: center;
      align-items: stretch;
      width: 100%;
    }
    .feat-showcase {
      position: relative;
      width: 100%;
      max-width: 520px;
      min-height: 300px;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(15,23,42,.12);
      border: 1px solid rgba(255,255,255,.6);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.75rem;
      transition: transform var(--transition), box-shadow var(--transition);
    }
    .feat-block:hover .feat-showcase {
      transform: translateY(-6px);
      box-shadow: 0 28px 60px rgba(15,23,42,.16);
    }
    .feat-showcase-bg {
      position: absolute;
      inset: 0;
      z-index: 0;
    }
    .feat-showcase-bg::after {
      content: "";
      position: absolute;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,.15);
      top: -60px;
      right: -40px;
    }
    .feat-showcase-icon {
      position: absolute;
      top: 1.5rem;
      left: 1.5rem;
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: rgba(255,255,255,.92);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(0,0,0,.08);
      z-index: 2;
    }
    .feat-showcase-icon i { width: 28px; height: 28px; }
    .feat-showcase-mock {
      position: relative;
      z-index: 1;
      background: rgba(255,255,255,.94);
      backdrop-filter: blur(8px);
      border-radius: 14px;
      padding: 1rem 1.15rem;
      box-shadow: 0 4px 20px rgba(0,0,0,.06);
    }
    .feat-showcase-mock span {
      display: block;
      height: 8px;
      border-radius: 4px;
      background: #e8eaed;
      margin-bottom: 8px;
    }
    .feat-showcase-mock span:first-child { width: 55%; background: var(--color-primary); opacity: .7; }
    .feat-showcase-mock span:nth-child(2) { width: 80%; }
    .feat-showcase-mock span:nth-child(3) { width: 65%; margin-bottom: 0; }
    .feat-showcase--orange .feat-showcase-bg { background: linear-gradient(145deg, #f28c28, #ffb347); }
    .feat-showcase--orange .feat-showcase-icon { color: #e07a10; }
    .feat-showcase--blue .feat-showcase-bg { background: linear-gradient(145deg, #2563eb, #60a5fa); }
    .feat-showcase--blue .feat-showcase-icon { color: #2563eb; }
    .feat-showcase--green .feat-showcase-bg { background: linear-gradient(145deg, #059669, #34d399); }
    .feat-showcase--green .feat-showcase-icon { color: #059669; }
    .feat-showcase--purple .feat-showcase-bg { background: linear-gradient(145deg, #7c3aed, #a78bfa); }
    .feat-showcase--purple .feat-showcase-icon { color: #7c3aed; }
    .feat-showcase--indigo .feat-showcase-bg { background: linear-gradient(145deg, #4f46e5, #818cf8); }
    .feat-showcase--indigo .feat-showcase-icon { color: #4f46e5; }
    .feat-showcase--teal .feat-showcase-bg { background: linear-gradient(145deg, #0d9488, #2dd4bf); }
    .feat-showcase--teal .feat-showcase-icon { color: #0d9488; }
    .feat-showcase--coral .feat-showcase-bg { background: linear-gradient(145deg, #e11d48, #fb7185); }
    .feat-showcase--coral .feat-showcase-icon { color: #e11d48; }
    .feat-showcase--amber .feat-showcase-bg { background: linear-gradient(145deg, #d97706, #fbbf24); }
    .feat-showcase--amber .feat-showcase-icon { color: #d97706; }
    .feat-showcase--img {
      padding: 0;
      min-height: auto;
      background: #f8fafc;
    }
    .feat-showcase--img img {
      width: 100%;
      height: auto;
      display: block;
      border-radius: 24px;
    }
    /* Real-screenshot crop: zooms the actual dashboard.png (the same asset
       used on the homepage product tour) into the panel that matches this
       feature, instead of the generic icon + fake mock-bars placeholder.
       Only applied where the dashboard genuinely shows that feature\'s data
       -- the transform values match the pre-verified homepage tour crops,
       not new guesses. Static (no JS): each card always shows its own
       region, there is nothing to scroll-drive here. */
    .feat-showcase--crop {
      padding: 0;
      min-height: auto;
      background: #fff;
      aspect-ratio: 1536 / 1024;
    }
    .feat-showcase--crop img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform-origin: 0 0;
    }

    .feat-content h3 {
      font-size: clamp(1.25rem, 2.5vw, 1.5rem);
      font-weight: 700;
      letter-spacing: -0.02em;
      margin-bottom: 0.75rem;
      line-height: 1.3;
    }
    .feat-content p {
      color: var(--color-text-muted);
      font-size: 1rem;
      line-height: 1.7;
      margin-bottom: 1.25rem;
    }

    /* Bottom CTA */
    .feat-cta {
      padding: 5rem 0;
      text-align: center;
      background: linear-gradient(180deg, var(--color-bg-alt), #fff);
    }
    .feat-cta-inner {
      max-width: 640px;
      margin: 0 auto;
    }
    .feat-cta h2 {
      font-size: clamp(1.75rem, 4vw, 2.25rem);
      font-weight: 800;
      letter-spacing: -0.03em;
      margin-bottom: 0.75rem;
    }
    .feat-cta p {
      color: var(--color-text-muted);
      font-size: 1.0625rem;
      margin-bottom: 2rem;
      line-height: 1.65;
    }
    .feat-cta-btns {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }
    .btn-outline {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      border-radius: 999px;
      border: 2px solid var(--color-border);
      color: var(--color-text);
      transition: border-color var(--transition), color var(--transition), transform var(--transition);
    }
    .btn-outline:hover {
      border-color: var(--color-primary);
      color: var(--color-primary);
      transform: translateY(-2px);
    }
    .feat-learn-more {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: var(--color-primary);
      margin-top: 0.25rem;
      transition: gap var(--transition);
    }
    .feat-learn-more:hover { gap: 0.55rem; }
    .feat-content h3 a:hover { color: var(--color-primary); }
';

$jsonLdSchema = <<<'JSON'
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "WebSite",
            "@id": "https://eduportal.pk/#website",
            "name": "EduPortal",
            "url": "https://eduportal.pk",
            "publisher": {
                "@id": "https://eduportal.pk/#organization"
            }
        },
        {
            "@type": "BreadcrumbList",
            "@id": "https://eduportal.pk/features.php#breadcrumb",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "https://eduportal.pk/"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Features",
                    "item": "https://eduportal.pk/features.php"
                }
            ]
        },
        {
            "@type": "CollectionPage",
            "@id": "https://eduportal.pk/features.php#collection",
            "name": "EduPortal Features",
            "description": "Explore 22+ EduPortal features: student records, digital attendance, fee management, exams, parent mobile app, finance, hostel and multi-campus ERP.",
            "url": "https://eduportal.pk/features.php",
            "isPartOf": {
                "@id": "https://eduportal.pk/#website"
            }
        }
    ]
}
JSON;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="section-label">EduPortal ERP</span>
        <h1>All Features</h1>
        <p>Essential modules every school needs — from admissions to daily operations, built for Pakistani and international campuses.</p>
      </div>
    </section>

    <!-- 1. EduPortal Key Features -->
    <section class="feat-category" id="key-features">
      <div class="container">

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
            <div class="feat-showcase feat-showcase--img">
              <img src="assets/dashboard.png" alt="EduPortal student records and school dashboard" width="1536" height="1024" loading="lazy" decoding="async">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('student-information-system.php')) ?>">Student Information &amp; Profile Management</a></h3>
            <p>Maintain complete digital student records — admission details, guardians, medical notes, documents, and class history in one searchable profile. Principals and admins get instant visibility across every section without digging through paper files.</p>
            <a href="<?= ep_h(ep_feature_url('student-information-system.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
            <a href="<?= ep_h(ep_feature_url('online-admissions.php')) ?>" class="feat-learn-more" style="margin-left:1rem">Online admissions <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--crop">
              <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>"
                   alt="EduPortal class attendance heatmap showing attendance per section across the week"
                   width="1536" height="1024" loading="lazy" decoding="async"
                   style="transform: translate(-21.28%, -180%) scale(2.8);">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>">Digital Attendance — Face Recognition &amp; QR</a></h3>
            <p>AI-based face recognition machine with <strong>1-second scanning</strong> — auto-syncs from software photos, no manual student registration on device. Works for students &amp; staff. Or choose affordable <strong>QR card scanning</strong> on ID cards auto-printed from EduPortal. Manual &amp; app marking also included.</p>
            <a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>" class="feat-learn-more">Explore digital attendance <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--crop">
              <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>"
                   alt="EduPortal fee collection and outstanding receivables panel"
                   width="1536" height="1024" loading="lazy" decoding="async"
                   style="transform: translate(-18.18%, 0%) scale(2.2);">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('fee-management.php')) ?>">Fee Management &amp; Automated Fee Vouchers</a></h3>
            <p>Define fee structures, concessions, and installments per class. Generate printable or digital fee vouchers in bulk, track collections, defaulters, and send payment reminders via SMS or app — reducing front-desk workload.</p>
            <a href="<?= ep_h(ep_feature_url('fee-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--purple">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="award"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('examination-management.php')) ?>">Exams Results &amp; Report Cards</a></h3>
            <p>Configure exam types, grading scales, and subject-wise marks entry. Publish results securely to the parent portal and print professional report cards with school branding — ready for board compliance and parent meetings.</p>
            <a href="<?= ep_h(ep_feature_url('examination-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--indigo">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="calendar-days"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('timetable-management.php')) ?>">TimeTable &amp; Academic Planning</a></h3>
            <p>Build clash-free timetables for teachers, rooms, and sections with drag-and-drop simplicity. Share live schedules with staff and students so substitutions and period changes stay coordinated across the campus.</p>
            <a href="<?= ep_h(ep_feature_url('timetable-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--teal">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="book-open"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('lesson-plans-syllabus.php')) ?>">Daily Lesson Plans &amp; Syllabus Tracking</a></h3>
            <p>Teachers log daily lesson plans aligned to your syllabus coverage targets. Academic coordinators monitor progress by subject and class, ensuring curriculum completion before board exams and inspections.</p>
            <a href="<?= ep_h(ep_feature_url('lesson-plans-syllabus.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
            <a href="<?= ep_h(ep_feature_url('lms-management.php')) ?>" class="feat-learn-more" style="margin-left:1rem">LMS module <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--amber">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="library"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('library-management.php')) ?>">Library &amp; Book Issuance System</a></h3>
            <p>Catalog books, manage ISBNs, and track issuance and returns per student or staff member. Overdue reminders and fine calculations keep your library organized without manual registers.</p>
            <a href="<?= ep_h(ep_feature_url('library-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--coral">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="bus"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('transport-management.php')) ?>">Transport &amp; Route Management</a></h3>
            <p>Assign students to routes and vehicles, manage driver details, and link transport fees to billing. Parents see pickup points and route info in the app — improving safety and communication for daily commutes.</p>
            <a href="<?= ep_h(ep_feature_url('transport-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--purple">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="home"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('hostel-management.php')) ?>">Hostel &amp; Accommodation Management</a></h3>
            <p>Allocate rooms and beds, track boarding students, and manage hostel-specific fees and attendance. Wardens get dashboards for occupancy, leave requests, and discipline notes in residential campuses.</p>
            <a href="<?= ep_h(ep_feature_url('hostel-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--orange">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="shopping-cart"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('canteen-pos.php')) ?>">Canteen &amp; Point of Sale (POS) System</a></h3>
            <p>Run canteen sales with a fast POS tied to student wallets or cash. Track daily revenue, stock consumption, and popular items — ideal for schools offering prepaid meal accounts or snack counters.</p>
            <a href="<?= ep_h(ep_feature_url('canteen-pos.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>
      </div>
    </section>

    <!-- 2. Communication & Engagement -->
    <section class="feat-category" id="communication">
      <div class="container">
        <div class="feat-category-header">
          <span class="section-label">Stay Connected</span>
          <h2>Communication &amp; Engagement</h2>
          <p>Reach parents and staff instantly — mobile apps, messaging, and branded documents that strengthen trust with your school community.</p>
        </div>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
            <div class="feat-showcase feat-showcase--img">
              <img src="assets/parent-app.jpg" alt="EduPortal ParentsConnect mobile app for parents and teachers" width="2452" height="4857" loading="lazy" decoding="async">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('parent-mobile-app.php')) ?>">Parents Portal &amp; Mobile App</a></h3>
            <p>Dedicated apps for parents, teachers, and admins — view attendance, fees, results, homework, and school notices on the go. Push notifications keep families engaged without phone calls to the office.</p>
            <a href="<?= ep_h(ep_feature_url('parent-mobile-app.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
            <a href="<?= ep_h(ep_feature_url('teacher-mobile-app.php')) ?>" class="feat-learn-more" style="margin-left:1rem">Teacher app <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--green">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="message-square"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('sms-messaging.php')) ?>">Mobile SIM Messaging</a></h3>
            <p>Send bulk SMS for fee reminders, holidays, exam schedules, and emergencies using your school's SIM integration. Reach thousands of parents in minutes with delivery logs for accountability.</p>
            <a href="<?= ep_h(ep_feature_url('sms-messaging.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--teal">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="message-circle"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('whatsapp-communication.php')) ?>">WhatsApp Messaging</a></h3>
            <p>Deliver notices and alerts on WhatsApp where parents already chat daily. Template-based messages for fee dues, events, and results — SMS not included, so you control channel costs separately.</p>
            <a href="<?= ep_h(ep_feature_url('whatsapp-communication.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--coral">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="id-card"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('id-cards-generation.php')) ?>">ID Cards Generation</a></h3>
            <p>Auto-design &amp; batch-print student and staff ID cards with <strong>QR codes embedded automatically</strong>. Photos pull from software profiles — cards link directly to QR attendance scanning. Perfect for admission season and budget-friendly digital check-in.</p>
            <a href="<?= ep_h(ep_feature_url('id-cards-generation.php')) ?>" class="feat-learn-more">Explore ID cards <i data-lucide="arrow-right"></i></a>
            <a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>" class="feat-learn-more" style="margin-left:1rem">QR attendance <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--amber">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="award"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('certificates-id-cards.php')) ?>">Customized Certificates</a></h3>
            <p>Design and print leaving certificates, character certificates, and achievement awards with your logo and layout. Batch generation saves hours during graduation seasons.</p>
            <a href="<?= ep_h(ep_feature_url('certificates-id-cards.php')) ?>" class="feat-learn-more">Explore certificates <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--indigo">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="file-spreadsheet"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('exam-datasheets.php')) ?>">Datasheets &amp; Roll No. Slips</a></h3>
            <p>Export exam datasheets, seating plans, and roll number slips formatted for board exams and internal assessments. Coordinators print accurate room-wise lists in one click before exam day.</p>
            <a href="<?= ep_h(ep_feature_url('exam-datasheets.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>
      </div>
    </section>

    <!-- 3. Finance & Administration -->
    <section class="feat-category" id="finance">
      <div class="container">
        <div class="feat-category-header">
          <span class="section-label">Back Office</span>
          <h2>Finance &amp; Administration</h2>
          <p>Full visibility into school finances, payroll, inventory, and multi-branch operations — built for owners and accountants.</p>
        </div>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--purple">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="landmark"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('accounting-management.php')) ?>">Accounts Finance &amp; Financial Statements</a></h3>
            <p>General ledger, chart of accounts, and automated financial statements — balance sheet, P&amp;L, and cash flow — aligned with how schools report to owners and auditors. No separate accounting software required.</p>
            <a href="<?= ep_h(ep_feature_url('accounting-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--crop">
              <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>"
                   alt="EduPortal staff productivity panel tracking attendance marking, diary completion and result submission"
                   width="1536" height="1024" loading="lazy" decoding="async"
                   style="transform: translate(-81.52%, -180%) scale(2.8);">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('payroll-management.php')) ?>">Payroll &amp; Staff Salary Management</a></h3>
            <p>Manage staff profiles, salary structures, deductions, and monthly payroll runs. Generate payslips and payment summaries while linking attendance and leave data for accurate compensation.</p>
            <a href="<?= ep_h(ep_feature_url('payroll-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--crop">
              <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>"
                   alt="EduPortal revenue overview chart plotting income and expense against the collection target"
                   width="1536" height="1024" loading="lazy" decoding="async"
                   style="transform: translate(-17.47%, -70.48%) scale(2.1);">
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('expenses-profit-loss.php')) ?>">Expenses Profit &amp; Loss Tracking</a></h3>
            <p>Record daily expenses by category — utilities, maintenance, supplies — and see real-time profit and loss against fee income. School owners make informed budget decisions with clear monthly dashboards.</p>
            <a href="<?= ep_h(ep_feature_url('expenses-profit-loss.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--blue">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="package"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('inventory-management.php')) ?>">Inventory &amp; Stock Management</a></h3>
            <p>Track uniforms, books, lab supplies, and stationery stock with inward/outward logs. Low-stock alerts and vendor-wise purchase history prevent shortages before the new academic session.</p>
            <a href="<?= ep_h(ep_feature_url('inventory-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--amber">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="building-2"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('multi-campus-management.php')) ?>">Multi-Campus Management</a></h3>
            <p>Operate multiple branches or franchises from a single owner dashboard — consolidated reports, per-campus data isolation, and shared policies. Scale your school group without duplicate systems.</p>
            <a href="<?= ep_h(ep_feature_url('multi-campus-management.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>
      </div>
    </section>

    <!-- 4. Premium Support & Customization -->
    <section class="feat-category" id="premium">
      <div class="container">
        <div class="feat-category-header">
          <span class="section-label">Enterprise</span>
          <h2>Premium Support &amp; Customization</h2>
          <p>White-glove onboarding and tailored workflows for schools that need more than out-of-the-box software.</p>
        </div>

        <article class="feat-block feat-block--left">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--teal">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="headphones"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('customer-support.php')) ?>">Dedicated Customer Support</a></h3>
            <p>Priority support from EduPortal specialists who understand school operations — setup help, training sessions, and fast resolution when exams or fee season cannot wait. Your success team stays with you year-round.</p>
            <a href="<?= ep_h(ep_feature_url('customer-support.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>

        <article class="feat-block feat-block--right">
          <div class="feat-visual">
                        <div class="feat-showcase feat-showcase--coral">
              <div class="feat-showcase-bg"></div>
              <div class="feat-showcase-icon"><i data-lucide="settings-2"></i></div>
              <div class="feat-showcase-mock"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="feat-content">
            <h3><a href="<?= ep_h(ep_feature_url('customization-options.php')) ?>">Customization Options Available</a></h3>
            <p>Adapt reports, workflows, and fields to match your school's unique processes — custom fee heads, report layouts, and role permissions. Schools get flexible configuration without changing core stability.</p>
            <a href="<?= ep_h(ep_feature_url('customization-options.php')) ?>" class="feat-learn-more">Explore feature <i data-lucide="arrow-right"></i></a>
          </div>
        </article>
      </div>
    </section>

    <section class="feat-cta">
      <div class="container feat-cta-inner">
        <span class="section-label">Get Started</span>
        <h2>Find the right plan for your school</h2>
        <p>Book a demo to see EduPortal live on your campus and discover how every module works together.</p>
        <div class="feat-cta-btns">
          <a href="pricing.php" class="btn btn-primary">View Pricing Plans</a>
          <a href="index.php" class="btn btn-outline">Back to Home</a>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
