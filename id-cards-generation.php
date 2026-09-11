<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'School ID Card Generator with Auto QR Code | EduPortal Pakistan';
$pageDescription  = 'Design &amp; batch-print student &amp; staff ID cards with auto-generated QR codes for digital attendance. Cards sync from EduPortal SIS — no designer needed. School ERP Pakistan.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'id-cards-generation';

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
            "@id": "https://eduportal.pk/id-cards-generation.php#breadcrumb",
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
                    "name": "ID Cards Generation",
                    "item": "https://eduportal.pk/id-cards-generation.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/id-cards-generation.php#webpage",
            "name": "School ID Card Generator with Auto QR Code | EduPortal Pakistan",
            "description": "Design &amp; batch-print student &amp; staff ID cards with auto-generated QR codes for digital attendance. Cards sync from EduPortal SIS — no designer needed. School ERP Pakistan.",
            "url": "https://eduportal.pk/id-cards-generation.php",
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
  <meta property="og:title" content="School ID Card Generator with Auto QR Code | EduPortal Pakistan">
  <meta property="og:description" content="Design &amp; batch-print student &amp; staff ID cards with auto-generated QR codes for digital attendance. Cards sync from EduPortal SIS — no designer needed. School ERP Pakistan.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is EduPortal ID Cards Generation?',
     'a' => 'It is a module inside EduPortal school ERP that auto-designs and batch-prints student and staff ID cards with school branding, photos from profiles, and embedded QR codes for digital attendance scanning.'],
    ['q' => 'Are QR codes added automatically to ID cards?',
     'a' => 'Yes. Every card can include an auto-generated QR code linked to the student or staff record. No manual QR creation or external tools — print once and use for attendance immediately.'],
    ['q' => 'Can ID cards work with QR attendance?',
     'a' => 'Yes. QR codes on EduPortal-printed cards integrate directly with our QR attendance scanning system. Scan the card and attendance marks in the software within seconds.'],
    ['q' => 'Do photos come from student profiles?',
     'a' => 'Photos are pulled automatically from student and staff records in EduPortal — saving hours compared to collecting files separately for a designer.'],
    ['q' => 'Can I print cards in bulk?',
     'a' => 'Yes. Generate hundreds of cards as PDF in one batch during admission season. Reprint individual cards anytime when students lose or damage them.'],
    ['q' => 'Does it support staff ID cards too?',
     'a' => 'Yes. Staff cards use the same templates and QR workflow, so employees can check in with QR or face recognition alongside students.'],
    ['q' => 'Can I customize card layout and logo?',
     'a' => 'Yes. Add your school logo, colors, class info, blood group, emergency contacts, and other fields using flexible templates.'],
    ['q' => 'Is this separate from certificates?',
     'a' => 'ID Cards Generation focuses on daily-use identity cards with QR for attendance. Leaving certificates, character certificates, and awards are handled in our Certificates module — both share the same student data.'],
    ['q' => 'How fast can we print cards for new admissions?',
     'a' => 'Once students are entered in EduPortal, cards can be generated the same day — no waiting for external designers during peak admission weeks.'],
    ['q' => 'How do I see ID card generation in action?',
     'a' => 'Book a free demo and our team will show template design, QR embedding, bulk print, and how cards connect to digital attendance on your campus.'],
];
$jsonLdSchema = ep_append_faq_schema($jsonLdSchema, $moduleFaqs, $canonicalUrl);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="container fd-breadcrumb">
      <nav aria-label="Breadcrumb">
        <ol>
          <li><a href="<?= ep_h(ep_url('index.php')) ?>">Home</a></li>
          <li><a href="<?= ep_h(ep_url('features.php')) ?>">Features</a></li>
          <li><span aria-current="page">ID Cards Generation</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="id-card" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>ID Cards Generation — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Auto-design and print QR-coded ID cards for students and staff — ready for digital attendance scanning. EduPortal replaces designer delays, manual QR creation, and cards disconnected from attendance with same-day card printing, embedded QR codes, and seamless attendance check-in for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal ID card generation with QR codes for attendance" width="640" height="400" loading="eager">
          <div class="fd-hero-stat">
            <span><?= ep_h(ep_site_metric('total_clients')) ?> schools trust EduPortal</span>
            <span>Full school ERP Pakistan</span>
          </div>
        </div>
      </div>
    </section>

    
    <section class="fd-section fd-section--alt" aria-labelledby="qr-link-heading">
      <div class="container">
        <div class="fd-qr-banner reveal-fd">
          <div class="fd-qr-banner-icon"><i data-lucide="qr-code"></i></div>
          <div>
            <h2 id="qr-link-heading">ID cards built for QR attendance</h2>
            <p>Every card can include an auto-generated QR code linked to the student or staff record. Scan at the gate with EduPortal <a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>">digital attendance</a> — no separate QR tools or manual encoding. A cost-effective path for smaller institutes; pairs perfectly with face recognition at larger campuses.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="fd-ai-section" aria-labelledby="ai-overview">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Overview</span>
          <h2 id="ai-overview">Everything you need to know about ID Cards Generation</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is ID Cards Generation?</h2>
            <p>ID Cards Generation is EduPortal's module for designing and batch-printing student and staff identity cards with auto-generated QR codes — directly linked to digital attendance scanning in your school management software Pakistan.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is ID Cards Generation important for schools?</h2>
            <p>Schools waste time and money on external designers, especially during admission season. When cards are disconnected from attendance, QR codes must be recreated manually. EduPortal generates branded, scannable cards from existing profile photos in minutes.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's ID Cards Generation work?</h2>
            <p>Select a template, merge student or staff data from SIS, and export print-ready PDFs with embedded QR codes. Hand cards to students — gate staff scan with phone or scanner and attendance marks in EduPortal automatically. Reprint anytime from the same record.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>Ideal for Pakistani schools wanting professional ID cards without designer delays. QR attendance on these cards offers a lower-cost digital check-in path for smaller institutes while larger schools combine cards with AI face recognition at main entrances.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal ID Cards Generation</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Auto QR on every card</h3>
            <p>QR codes embed automatically — cards work instantly with EduPortal QR attendance scanning.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>No external designer needed</h3>
            <p>Templates, photos, and data merge inside your ERP. Print admission cards the same day students enroll.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Student &amp; staff cards</h3>
            <p>One workflow for learners and employees with consistent branding and scannable codes.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>Photos from software profiles</h3>
            <p>Pull pictures directly from SIS records — no duplicate photo collection drives.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>Batch PDF printing</h3>
            <p>Generate hundreds of cards before term start. Reprint lost cards in minutes.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Custom branding</h3>
            <p>School logo, colors, class, blood group, and emergency contacts on professional layouts.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Attendance-ready output</h3>
            <p>Cards are designed for daily use at gates and classrooms — not just display.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Lower cost digital attendance</h3>
            <p>Schools wanting affordable check-in pair QR cards with phone scanners — no expensive hardware required.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Integrated with full ERP</h3>
            <p>Card data stays linked to student records, attendance, and access control in one platform.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Fast admission season</h3>
            <p>Skip designer queues during April rush — office staff print cards themselves.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete ID Cards Generation capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>Auto ID card design from templates</h3>
              <p>EduPortal id cards generation includes auto ID card design from templates designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Student &amp; staff card layouts</h3>
              <p>EduPortal id cards generation includes student &amp; staff card layouts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>QR code auto-printed on every card</h3>
              <p>EduPortal id cards generation includes QR code auto-printed on every card designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Photos pulled from student/staff profiles</h3>
              <p>EduPortal id cards generation includes photos pulled from student/staff profiles designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>School logo &amp; branding</h3>
              <p>EduPortal id cards generation includes school logo &amp; branding designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Batch PDF generation</h3>
              <p>EduPortal id cards generation includes batch PDF generation designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Barcode support</h3>
              <p>EduPortal id cards generation includes barcode support designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Linked to QR attendance scanning</h3>
              <p>EduPortal id cards generation includes linked to QR attendance scanning designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Reprint &amp; replacement cards</h3>
              <p>EduPortal id cards generation includes reprint &amp; replacement cards designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Custom fields &amp; class info on card</h3>
              <p>EduPortal id cards generation includes custom fields &amp; class info on card designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>ID Cards Generation reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual designer delays, manual QR creation, and cards disconnected from attendance.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How ID Cards Generation works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs id cards generation today.</p>
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
            <p>Teams use id cards generation every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce designer delays, manual QR creation, and cards disconnected from attendance, and improve same-day card printing, embedded QR codes, and seamless attendance check-in.</p>
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
        <h2 id="why-heading">Why schools need ID Cards Generation</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on designer delays, manual QR creation, and cards disconnected from attendance. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>ID Cards Generation inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When same-day card printing, embedded QR codes, and seamless attendance check-in become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate designer delays, manual QR creation, and cards disconnected from attendance with structured digital workflows.</li>
          <li>Achieve same-day card printing, embedded QR codes, and seamless attendance check-in visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">ID Cards Generation screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal ID card generation with QR codes for attendance" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal ID Cards Generation admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>ID Cards Generation mobile view</h4>
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
          <h2 id="faq-heading">ID Cards Generation — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <?php
    $ctaLabel     = 'Get Started';
    $ctaHeading   = 'Ready to modernize id cards generation?';
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
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>">Attendance Management</a>
<a href="<?= ep_h(ep_feature_url('certificates-id-cards.php')) ?>">Certificates &amp; ID Cards</a>
<a href="<?= ep_h(ep_feature_url('student-information-system.php')) ?>">Student Information System</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>