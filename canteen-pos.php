<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'School Canteen POS Software | EduPortal ERP Pakistan';
$pageDescription  = 'Fast canteen POS tied to student wallets or cash. Track daily sales, stock &amp; revenue with EduPortal — school management software Pakistan.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'canteen-pos';

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
            "@id": "https://eduportal.pk/canteen-pos.php#breadcrumb",
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
                    "name": "Canteen & POS System",
                    "item": "https://eduportal.pk/canteen-pos.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/canteen-pos.php#webpage",
            "name": "School Canteen POS Software | EduPortal ERP Pakistan",
            "description": "Fast canteen POS tied to student wallets or cash. Track daily sales, stock &amp; revenue with EduPortal — school management software Pakistan.",
            "url": "https://eduportal.pk/canteen-pos.php",
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
  <meta property="og:title" content="School Canteen POS Software | EduPortal ERP Pakistan">
  <meta property="og:description" content="Fast canteen POS tied to student wallets or cash. Track daily sales, stock &amp; revenue with EduPortal — school management software Pakistan.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is Canteen & POS System in EduPortal?',
     'a' => 'Canteen & POS System in EduPortal is a module within our school management software Pakistan that sell meals quickly with pos linked to student accounts and daily revenue reports. It replaces cash handling errors, no sales visibility, and manual stock counts with fast checkout, prepaid wallets, and daily canteen P&L clarity.'],
    ['q' => 'Why is Canteen & POS System important for schools?',
     'a' => 'Schools lose time and money when cash handling errors, no sales visibility, and manual stock counts. Canteen & POS System gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.'],
    ['q' => 'How does EduPortal\'s Canteen & POS System work?',
     'a' => 'Staff log in to EduPortal web or mobile apps, access the canteen & pos system module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.'],
    ['q' => 'Is EduPortal canteen & pos system suitable for Pakistani schools?',
     'a' => 'Yes. EduPortal is used by {total_clients} schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.'],
    ['q' => 'Can canteen & pos system integrate with other EduPortal modules?',
     'a' => 'Absolutely. Canteen & POS System shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.'],
    ['q' => 'Do parents see canteen & pos system updates?',
     'a' => 'Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.'],
    ['q' => 'How long does implementation take?',
     'a' => 'Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to canteen & pos system workflows.'],
    ['q' => 'Is training included?',
     'a' => 'Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.'],
    ['q' => 'Can we customize canteen & pos system for our school?',
     'a' => 'EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.'],
    ['q' => 'How do I see Canteen & POS System in action?',
     'a' => 'Book a free demo with our team. We walk your staff through real scenarios using your school structure so you can evaluate fit before committing.'],
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
          <li><span aria-current="page">Canteen &amp; POS System</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="shopping-cart" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>Canteen &amp; POS System — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Sell meals quickly with POS linked to student accounts and daily revenue reports. EduPortal replaces cash handling errors, no sales visibility, and manual stock counts with fast checkout, prepaid wallets, and daily canteen P&amp;L clarity for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal canteen point of sale" width="640" height="400" loading="eager">
          <div class="fd-hero-stat">
            <span><?= ep_h(ep_site_metric('total_clients')) ?> schools trust EduPortal</span>
            <span>Full school ERP Pakistan</span>
          </div>
        </div>
      </div>
    </section>

    

    <section class="fd-ai-section" aria-labelledby="ai-overview">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Overview</span>
          <h2 id="ai-overview">Everything you need to know about Canteen &amp; POS System</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is Canteen &amp; POS System?</h2>
            <p>Canteen &amp; POS System is a core module of EduPortal — AI-ready school management software Pakistan used by hundreds of campuses. Sell meals quickly with POS linked to student accounts and daily revenue reports. It centralizes workflows that schools previously handled with cash handling errors, no sales visibility, and manual stock counts, giving every stakeholder accurate information from a single login.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is Canteen &amp; POS System important for schools?</h2>
            <p>Canteen &amp; POS System matters because operational mistakes directly affect student experience and school reputation. When records are late or incomplete, parents lose trust, teachers duplicate effort, and owners fly blind on decisions. EduPortal helps schools professionalize back-office work so education stays the focus.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's Canteen &amp; POS System work?</h2>
            <p>EduPortal's canteen &amp; pos system connects staff dashboards, optional mobile apps, and parent-facing portals. Administrators configure rules once; daily users complete tasks in simple screens; reports and notifications flow automatically to the right people — principals, accountants, teachers, and families.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>For schools in Pakistan, canteen &amp; pos system must respect local realities: multi-lingual families, WhatsApp as the primary channel, tight fee cycles, and board exam pressure. EduPortal supports Urdu/English workflows, SIM and WhatsApp messaging, PKR billing, and formats familiar to regional education boards — without forcing you to adopt foreign software habits.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal Canteen &amp; POS System</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Save admin hours every week</h3>
            <p>Automate repetitive canteen &amp; pos system tasks so your office team focuses on students, not spreadsheets.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>Single source of truth</h3>
            <p>All canteen &amp; pos system data lives inside EduPortal school ERP Pakistan — no duplicate tools or manual sync.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Real-time visibility for owners</h3>
            <p>Principals and school owners see live dashboards instead of waiting for end-of-month summaries.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>Parent trust &amp; transparency</h3>
            <p>When families see accurate updates in the app, phone calls to the front desk drop dramatically.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>Audit-ready records</h3>
            <p>Every action is logged with timestamps — ideal for board inspections, franchisor reviews, and internal audits.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Role-based security</h3>
            <p>Teachers, accountants, and admins see only what their job requires. Sensitive data stays protected.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Works on mobile &amp; desktop</h3>
            <p>Staff update records from office computers or mobile apps — data syncs instantly across campus.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Built for Pakistani schools</h3>
            <p>Fee calendars, Urdu/English workflows, local boards, and WhatsApp-first parent culture are supported out of the box.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Scales with enrollment</h3>
            <p>Whether you have 200 or 2,000 students, performance stays fast during admission and exam peaks.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Integrated with full ERP</h3>
            <p>Canteen &amp; POS System connects natively to fees, attendance, exams, and finance — no brittle integrations.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="heart-handshake"></i></div>
            <h3>Faster decision making</h3>
            <p>Leaders spot issues early with fast checkout, prepaid wallets, and daily canteen P&amp;L clarity instead of reacting after problems escalate.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="sparkles"></i></div>
            <h3>Lower operational cost</h3>
            <p>Reduce paper, printing, and manual labor — typical schools recover software cost within one fee cycle.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete Canteen &amp; POS System capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>POS checkout</h3>
              <p>EduPortal canteen &amp; pos system includes POS checkout designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Student wallet</h3>
              <p>EduPortal canteen &amp; pos system includes student wallet designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Menu management</h3>
              <p>EduPortal canteen &amp; pos system includes menu management designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Daily sales report</h3>
              <p>EduPortal canteen &amp; pos system includes daily sales report designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Stock consumption</h3>
              <p>EduPortal canteen &amp; pos system includes stock consumption designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Cash vs wallet split</h3>
              <p>EduPortal canteen &amp; pos system includes cash vs wallet split designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Item popularity</h3>
              <p>EduPortal canteen &amp; pos system includes item popularity designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Staff canteen sales</h3>
              <p>EduPortal canteen &amp; pos system includes staff canteen sales designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Receipt printing</h3>
              <p>EduPortal canteen &amp; pos system includes receipt printing designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Inventory link</h3>
              <p>EduPortal canteen &amp; pos system includes inventory link designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>Canteen &amp; POS System reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual cash handling errors, no sales visibility, and manual stock counts.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How Canteen &amp; POS System works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs canteen &amp; pos system today.</p>
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
            <p>Teams use canteen &amp; pos system every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce cash handling errors, no sales visibility, and manual stock counts, and improve fast checkout, prepaid wallets, and daily canteen P&amp;L clarity.</p>
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
        <h2 id="why-heading">Why schools need Canteen &amp; POS System</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on cash handling errors, no sales visibility, and manual stock counts. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>Canteen &amp; POS System inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When fast checkout, prepaid wallets, and daily canteen P&amp;L clarity become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate cash handling errors, no sales visibility, and manual stock counts with structured digital workflows.</li>
          <li>Achieve fast checkout, prepaid wallets, and daily canteen P&amp;L clarity visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">Canteen &amp; POS System screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal canteen point of sale" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal Canteen &amp; POS System admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>Canteen &amp; POS System mobile view</h4>
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
          <h2 id="faq-heading">Canteen &amp; POS System — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <section class="fd-cta reveal-fd">
      <div class="container">
        <span class="section-label" style="color:var(--color-primary)">Get Started</span>
        <h2>Ready to modernize canteen &amp; pos system?</h2>
        <p>Book a personalized demo and see how EduPortal school ERP Pakistan fits your campus — from admission to graduation.</p>
        <div class="fd-cta-btns">
          <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
          <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn-outline-light">Contact Sales</a>
        </div>
      </div>
    </section>

    
    <section class="fd-related">
      <div class="container reveal-fd">
        <div class="fd-section-header" style="margin-bottom:0">
          <span class="section-label">Related Features</span>
          <h2>Explore more EduPortal modules</h2>
        </div>
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('inventory-management.php')) ?>">Inventory Management</a>
<a href="<?= ep_h(ep_feature_url('fee-management.php')) ?>">Fee Management</a>
<a href="<?= ep_h(ep_feature_url('accounting-management.php')) ?>">Accounting Management</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>