<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'Digital Attendance — Face Recognition &amp; QR Code | EduPortal Pakistan';
$pageDescription  = 'AI face recognition attendance (1-sec scan), QR card scanning, manual &amp; app marking. Auto-sync with EduPortal school ERP. Used by big &amp; small institutes across Pakistan.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'attendance-management';

$jsonLdSchema = <<<'JSON'
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "WebSite",
            "@id": "https://eduportal.pk/#website",
            "name": "EduPortal",
            "url": "https://eduportal.pk",
            "description": "EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exams, parent apps and analytics. Trusted by 500+ schools in Pakistan.",
            "publisher": {
                "@id": "https://eduportal.pk/#organization"
            },
            "inLanguage": "en",
            "potentialAction": {
                "@type": "SearchAction",
                "target": {
                    "@type": "EntryPoint",
                    "urlTemplate": "https://eduportal.pk/faqs.php?q={search_term_string}"
                },
                "query-input": "required name=search_term_string"
            }
        },
        {
            "@type": "BreadcrumbList",
            "@id": "https://eduportal.pk/attendance-management.php#breadcrumb",
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
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "Attendance Management",
                    "item": "https://eduportal.pk/attendance-management.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/attendance-management.php#webpage",
            "name": "Digital Attendance — Face Recognition &amp; QR Code | EduPortal Pakistan",
            "description": "AI face recognition attendance (1-sec scan), QR card scanning, manual &amp; app marking. Auto-sync with EduPortal school ERP. Used by big &amp; small institutes across Pakistan.",
            "url": "https://eduportal.pk/attendance-management.php",
            "isPartOf": {
                "@id": "https://eduportal.pk/#website"
            }
        }
    ]
}
JSON;

$jsonLdSchema = str_replace('500+ schools in Pakistan', ep_h(ep_site_metric('total_clients')) . ' schools in Pakistan', $jsonLdSchema);

$extraHeadHtml = <<<'HTML'
  <meta property="og:type" content="website">
  <meta property="og:title" content="Digital Attendance — Face Recognition &amp; QR Code | EduPortal Pakistan">
  <meta property="og:description" content="AI face recognition attendance (1-sec scan), QR card scanning, manual &amp; app marking. Auto-sync with EduPortal school ERP. Used by big &amp; small institutes across Pakistan.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What digital attendance options does EduPortal offer?',
     'a' => 'EduPortal offers four modes: manual office marking, teacher mobile app, AI-based face recognition machines (1-second scan), and QR code scanning on student/staff ID cards. All modes sync automatically to one attendance database inside your school ERP Pakistan.'],
    ['q' => 'How does AI face recognition attendance work?',
     'a' => 'Our AI-based face recognition attendance machine scans faces in about one second. Students and staff do not need manual registration on the device — the machine auto-syncs with EduPortal and identifies users from photos already stored in the software. Attendance records appear instantly in dashboards and parent apps.'],
    ['q' => 'Does face recognition work for both students and staff?',
     'a' => 'Yes. The same EduPortal face recognition system works for students and staff. Many large and small institutes across Pakistan use it at gates, office entrances, and staff rooms with reliable daily performance.'],
    ['q' => 'What is QR code attendance scanning?',
     'a' => 'Each student and staff ID card can include an auto-generated QR code from EduPortal\'s ID Cards Generation module. Staff scan the card with a phone or scanner — attendance marks instantly without typing names. It is ideal for schools wanting a lower-cost digital attendance solution.'],
    ['q' => 'Do ID cards connect to attendance automatically?',
     'a' => 'Yes. When you generate ID cards in EduPortal, QR codes are embedded automatically. Those cards work directly with QR attendance scanning — no separate setup or third-party tools required.'],
    ['q' => 'Are parents notified when attendance is marked digitally?',
     'a' => 'Yes. Absences, late arrivals, and daily summaries can trigger SMS, app push, or WhatsApp alerts so parents know the same day — reducing front-desk phone calls.'],
    ['q' => 'Can small schools afford digital attendance?',
     'a' => 'Absolutely. QR card scanning is a budget-friendly option for smaller institutes. Face recognition suits campuses wanting hands-free speed at the gate. EduPortal helps you choose the right mix for your size and budget.'],
    ['q' => 'Is manual attendance still available?',
     'a' => 'Yes. Teachers and office staff can still mark attendance manually by class, section, or period — alongside or instead of digital methods. Everything stays in one system.'],
    ['q' => 'How long does digital attendance setup take?',
     'a' => 'QR attendance can go live as soon as ID cards are printed — often within days. Face recognition machines sync with EduPortal during onboarding; our support team assists with photo sync and device configuration.'],
    ['q' => 'Can I see a demo of digital attendance?',
     'a' => 'Yes. Watch our digital attendance demo video on the attendance feature page, or book a live demo where we show face recognition and QR scanning with your school structure.'],
];
$jsonLdSchema = ep_append_faq_schema($jsonLdSchema, $moduleFaqs, $canonicalUrl);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <div class="container fd-breadcrumb">
      <nav aria-label="Breadcrumb">
        <ol>
          <li><a href="<?= ep_h(ep_url('index.php')) ?>">Home</a></li>
          <li><a href="<?= ep_h(ep_url('features.php')) ?>">Features</a></li>
          <li><span aria-current="page">Attendance Management</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="scan-face" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>Attendance Management — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Manual, mobile app, AI face recognition, or QR card scanning — attendance that fits every campus and budget. EduPortal replaces late registers, manual machine enrollment, and no visibility into chronic absenteeism with 1-second digital check-in, auto-synced records, and instant parent alerts for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal digital attendance with face recognition and QR scanning" width="640" height="400" loading="eager">
          <div class="fd-hero-stat">
            <span><?= ep_h(ep_site_metric('total_clients')) ?> schools trust EduPortal</span>
            <span>Full school ERP Pakistan</span>
          </div>
        </div>
      </div>
    </section>


    <section class="fd-section fd-section--alt fd-video-section" aria-labelledby="demo-video-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Watch Demo</span>
          <h2 id="demo-video-heading">See digital attendance in action</h2>
          <p>Watch how EduPortal digital attendance works — face recognition sync, instant software updates, and parent notifications.</p>
        </div>
        <div class="fd-video-wrap reveal-fd">
          <iframe
            src="https://www.youtube.com/embed/QKHYk0vdkjY?rel=0"
            title="EduPortal digital attendance demo — face recognition and QR scanning"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"></iframe>
        </div>
        <p class="fd-video-caption reveal-fd"><a href="https://www.youtube.com/watch?v=QKHYk0vdkjY&t=1s" target="_blank" rel="noopener noreferrer">Open demo on YouTube</a></p>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="digital-methods-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Digital Attendance</span>
          <h2 id="digital-methods-heading">Two powerful ways to mark attendance digitally</h2>
          <p>EduPortal supports premium AI face recognition and affordable QR card scanning — both auto-sync with your school ERP.</p>
        </div>
        <div class="fd-digital-grid">
          <article class="fd-method-card fd-method-card--ai reveal-fd">
            <div class="fd-method-icon"><i data-lucide="scan-face"></i></div>
            <h3>AI-Based Face Recognition Machine</h3>
            <p class="fd-method-lead">Our intelligent attendance machine delivers <strong>1-second scanning</strong> at the school gate — no queues, no manual typing.</p>
            <ul class="fd-method-list">
              <li><i data-lucide="check"></i> <strong>No manual registration on device</strong> — auto-syncs with EduPortal software</li>
              <li><i data-lucide="check"></i> Identifies users from <strong>photos already stored</strong> in student &amp; staff profiles</li>
              <li><i data-lucide="check"></i> Works for <strong>students and staff</strong> at the same machine</li>
              <li><i data-lucide="check"></i> Trusted by <strong>many big and small institutes</strong> across Pakistan</li>
              <li><i data-lucide="check"></i> Attendance appears instantly in dashboards &amp; parent apps</li>
            </ul>
            <button type="button" class="btn btn-primary js-open-modal">Request face recognition demo</button>
          </article>
          <article class="fd-method-card fd-method-card--qr reveal-fd">
            <div class="fd-method-icon"><i data-lucide="qr-code"></i></div>
            <h3>QR Code Card Scanning</h3>
            <p class="fd-method-lead">A <strong>lower-cost digital attendance</strong> option — ideal for smaller institutes that want modern check-in without expensive hardware.</p>
            <ul class="fd-method-list">
              <li><i data-lucide="check"></i> QR codes <strong>auto-printed on student &amp; staff ID cards</strong></li>
              <li><i data-lucide="check"></i> Cards designed &amp; printed from <a href="<?= ep_h(ep_feature_url('id-cards-generation.php')) ?>">ID Cards Generation</a> module</li>
              <li><i data-lucide="check"></i> Scan with phone or handheld scanner — attendance marks in seconds</li>
              <li><i data-lucide="check"></i> Perfect for schools wanting <strong>affordable digital attendance</strong></li>
              <li><i data-lucide="check"></i> Same ERP sync, reports &amp; parent alerts as face recognition</li>
            </ul>
            <a href="<?= ep_h(ep_feature_url('id-cards-generation.php')) ?>" class="btn btn-dark">Explore ID Cards Generation</a>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-ai-section" aria-labelledby="ai-overview">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Overview</span>
          <h2 id="ai-overview">Everything you need to know about Attendance Management</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is Attendance Management?</h2>
            <p>Attendance Management in EduPortal is a complete digital attendance system for Pakistani schools — combining manual marking, teacher mobile apps, AI-based face recognition machines, and QR code card scanning in one school ERP Pakistan platform.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is Attendance Management important for schools?</h2>
            <p>Late registers and manual SMS lists cost schools hours every week and leave parents uninformed until it is too late. Digital attendance with 1-second face scans or QR card checks eliminates bottlenecks at the gate while giving owners real-time visibility into absenteeism trends.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's Attendance Management work?</h2>
            <p>Choose your method: office staff mark manually, teachers use the mobile app, students scan faces at an AI machine that auto-syncs photos from EduPortal, or staff scan QR codes printed on ID cards from our ID Cards Generation module. Every check-in flows to the same database, triggers parent alerts, and feeds reports instantly.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>EduPortal digital attendance is deployed across big and small institutes in Pakistan — from affordable QR setups for modest campuses to AI face recognition at larger schools. Local SMS, WhatsApp alerts, and Urdu/English parent communication are built in.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal Attendance Management</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>1-second AI face recognition</h3>
            <p>Our attendance machine scans faces in about one second — faster than manual registers and ideal for busy morning rushes at school gates.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>No manual machine registration</h3>
            <p>Students and staff are identified from photos already in EduPortal. The device auto-syncs — no typing names into the machine one by one.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Works for students &amp; staff</h3>
            <p>One system covers learners and employees. Trusted by large chains and small institutes across Pakistan.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>QR code card scanning</h3>
            <p>Budget-friendly digital attendance — scan QR codes auto-printed on ID cards designed in EduPortal.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>Instant software sync</h3>
            <p>Every check-in updates dashboards, reports, and parent apps in real time inside your school ERP Pakistan.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Same-day parent alerts</h3>
            <p>Absent and late notifications via SMS, app, or WhatsApp — families stay informed without calling the office.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Flexible for every budget</h3>
            <p>Choose face recognition for speed, QR cards for lower cost, or manual/app marking — or combine all methods.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Audit-ready reports</h3>
            <p>Daily, monthly, and board-format attendance sheets export in one click for inspections and meetings.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Linked to ID Cards module</h3>
            <p>QR codes on student and staff cards are generated automatically when you print cards from EduPortal.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Proven at scale</h3>
            <p>From boutique academies to multi-branch groups — digital attendance that grows with enrollment.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete Attendance Management capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>AI face recognition machine (1-sec scan)</h3>
              <p>EduPortal attendance management includes AI face recognition machine (1-sec scan) designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Auto-sync from software photos — no manual machine registration</h3>
              <p>EduPortal attendance management includes auto-sync from software photos — no manual machine registration designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Student &amp; staff face attendance</h3>
              <p>EduPortal attendance management includes student &amp; staff face attendance designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>QR code scanning on ID cards</h3>
              <p>EduPortal attendance management includes QR code scanning on ID cards designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Class-wise manual marking</h3>
              <p>EduPortal attendance management includes class-wise manual marking designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Teacher mobile app attendance</h3>
              <p>EduPortal attendance management includes teacher mobile app attendance designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Biometric device integration</h3>
              <p>EduPortal attendance management includes biometric device integration designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Instant parent SMS/app alerts</h3>
              <p>EduPortal attendance management includes instant parent SMS/app alerts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Late arrival &amp; leave tracking</h3>
              <p>EduPortal attendance management includes late arrival &amp; leave tracking designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Daily &amp; monthly attendance reports</h3>
              <p>EduPortal attendance management includes daily &amp; monthly attendance reports designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>Board exam attendance sheets</h3>
              <p>EduPortal attendance management includes board exam attendance sheets designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>Real-time owner dashboards</h3>
              <p>EduPortal attendance management includes real-time owner dashboards designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>Attendance Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual late registers, manual machine enrollment, and no visibility into chronic absenteeism.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How Attendance Management works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">

          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs attendance management today.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Import or enter existing data</h3>
            <p>Migrate spreadsheets or start fresh — EduPortal support helps during onboarding so nothing is lost.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Train staff in one session</h3>
            <p>Intuitive screens mean teachers and office staff adopt quickly without lengthy IT projects.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Go live with daily workflows</h3>
            <p>Teams use attendance management every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce late registers, manual machine enrollment, and no visibility into chronic absenteeism, and improve 1-second digital check-in, auto-synced records, and instant parent alerts.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Scale across terms &amp; campuses</h3>
            <p>Reuse the same configuration each academic year and extend to new branches from one owner login.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="why-heading">
      <div class="container fd-prose reveal-fd">
        <h2 id="why-heading">Why schools need Attendance Management</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on late registers, manual machine enrollment, and no visibility into chronic absenteeism. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>Attendance Management inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When 1-second digital check-in, auto-synced records, and instant parent alerts become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate late registers, manual machine enrollment, and no visibility into chronic absenteeism with structured digital workflows.</li>
          <li>Achieve 1-second digital check-in, auto-synced records, and instant parent alerts visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">Attendance Management screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal digital attendance with face recognition and QR scanning" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal Attendance Management admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>Attendance Management mobile view</h4>
              <p>Staff &amp; parents access on Android</p>
            </div>
          </figure>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="faq-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">FAQ</span>
          <h2 id="faq-heading">Attendance Management — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">

          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <?php
    $ctaLabel     = 'Get Started';
    $ctaHeading   = 'Ready to modernize attendance management?';
    $ctaText      = 'Book a personalized demo and see how EduPortal school ERP Pakistan fits your campus — from admission to graduation.';
    $ctaPrimary   = 'demo';
    $ctaSecondary = ['href' => ep_url('contact.php'), 'label' => 'Contact Sales'];
    require __DIR__ . '/includes/partials/cta-band.php';
    ?>


    <section class="fd-related">
      <div class="container reveal-fd">
        <div class="fd-section-header" style="margin-bottom:0">
          <span class="section-label">Related Features</span>
          <h2>Explore more EduPortal modules</h2>
        </div>
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('id-cards-generation.php')) ?>">ID Cards Generation</a>
<a href="<?= ep_h(ep_feature_url('parent-mobile-app.php')) ?>">Parent Mobile App</a>
<a href="<?= ep_h(ep_feature_url('teacher-mobile-app.php')) ?>">Teacher Mobile App</a></div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
