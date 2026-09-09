<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'Bulk SMS for Schools Pakistan | EduPortal SIM Messaging';
$pageDescription  = 'Send fee reminders, holidays, exam schedules &amp; emergency alerts via school SIM integration. Reliable bulk SMS for school ERP Pakistan with delivery logs.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'sms-messaging';

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
            "@id": "https://eduportal.pk/sms-messaging.php#breadcrumb",
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
                    "name": "SMS Messaging",
                    "item": "https://eduportal.pk/sms-messaging.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/sms-messaging.php#webpage",
            "name": "Bulk SMS for Schools Pakistan | EduPortal SIM Messaging",
            "description": "Send fee reminders, holidays, exam schedules &amp; emergency alerts via school SIM integration. Reliable bulk SMS for school ERP Pakistan with delivery logs.",
            "url": "https://eduportal.pk/sms-messaging.php",
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
  <meta property="og:title" content="Bulk SMS for Schools Pakistan | EduPortal SIM Messaging">
  <meta property="og:description" content="Send fee reminders, holidays, exam schedules &amp; emergency alerts via school SIM integration. Reliable bulk SMS for school ERP Pakistan with delivery logs.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is SMS Messaging in EduPortal?',
     'a' => 'SMS Messaging in EduPortal is a module within our school management software Pakistan that reach thousands of parents in minutes with branded sms from your sim. It replaces manual texting, no delivery proof, and missed fee reminder campaigns with logged bulk sends, targeted lists, and reliable parent reach.'],
    ['q' => 'Why is SMS Messaging important for schools?',
     'a' => 'Schools lose time and money when manual texting, no delivery proof, and missed fee reminder campaigns. SMS Messaging gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.'],
    ['q' => 'How does EduPortal\'s SMS Messaging work?',
     'a' => 'Staff log in to EduPortal web or mobile apps, access the sms messaging module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.'],
    ['q' => 'Is EduPortal sms messaging suitable for Pakistani schools?',
     'a' => 'Yes. EduPortal is used by {total_clients} schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.'],
    ['q' => 'Can sms messaging integrate with other EduPortal modules?',
     'a' => 'Absolutely. SMS Messaging shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.'],
    ['q' => 'Do parents see sms messaging updates?',
     'a' => 'Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.'],
    ['q' => 'How long does implementation take?',
     'a' => 'Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to sms messaging workflows.'],
    ['q' => 'Is training included?',
     'a' => 'Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.'],
    ['q' => 'Can we customize sms messaging for our school?',
     'a' => 'EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.'],
    ['q' => 'How do I see SMS Messaging in action?',
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
          <li><span aria-current="page">SMS Messaging</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="message-square" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>SMS Messaging — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Reach thousands of parents in minutes with branded SMS from your SIM. EduPortal replaces manual texting, no delivery proof, and missed fee reminder campaigns with logged bulk sends, targeted lists, and reliable parent reach for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal bulk SMS messaging" width="640" height="400" loading="eager">
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
          <h2 id="ai-overview">Everything you need to know about SMS Messaging</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is SMS Messaging?</h2>
            <p>SMS Messaging is a core module of EduPortal — AI-ready school management software Pakistan used by hundreds of campuses. Reach thousands of parents in minutes with branded SMS from your SIM. It centralizes workflows that schools previously handled with manual texting, no delivery proof, and missed fee reminder campaigns, giving every stakeholder accurate information from a single login.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is SMS Messaging important for schools?</h2>
            <p>SMS Messaging matters because operational mistakes directly affect student experience and school reputation. When records are late or incomplete, parents lose trust, teachers duplicate effort, and owners fly blind on decisions. EduPortal helps schools professionalize back-office work so education stays the focus.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's SMS Messaging work?</h2>
            <p>EduPortal's sms messaging connects staff dashboards, optional mobile apps, and parent-facing portals. Administrators configure rules once; daily users complete tasks in simple screens; reports and notifications flow automatically to the right people — principals, accountants, teachers, and families.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>For schools in Pakistan, sms messaging must respect local realities: multi-lingual families, WhatsApp as the primary channel, tight fee cycles, and board exam pressure. EduPortal supports Urdu/English workflows, SIM and WhatsApp messaging, PKR billing, and formats familiar to regional education boards — without forcing you to adopt foreign software habits.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal SMS Messaging</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Save admin hours every week</h3>
            <p>Automate repetitive sms messaging tasks so your office team focuses on students, not spreadsheets.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>Single source of truth</h3>
            <p>All sms messaging data lives inside EduPortal school ERP Pakistan — no duplicate tools or manual sync.</p>
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
            <p>SMS Messaging connects natively to fees, attendance, exams, and finance — no brittle integrations.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="heart-handshake"></i></div>
            <h3>Faster decision making</h3>
            <p>Leaders spot issues early with logged bulk sends, targeted lists, and reliable parent reach instead of reacting after problems escalate.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete SMS Messaging capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>SIM integration</h3>
              <p>EduPortal sms messaging includes SIM integration designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Bulk campaigns</h3>
              <p>EduPortal sms messaging includes bulk campaigns designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Fee reminders</h3>
              <p>EduPortal sms messaging includes fee reminders designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Holiday notices</h3>
              <p>EduPortal sms messaging includes holiday notices designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Exam alerts</h3>
              <p>EduPortal sms messaging includes exam alerts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Class-wise filters</h3>
              <p>EduPortal sms messaging includes class-wise filters designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Delivery logs</h3>
              <p>EduPortal sms messaging includes delivery logs designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Template library</h3>
              <p>EduPortal sms messaging includes template library designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Scheduled sends</h3>
              <p>EduPortal sms messaging includes scheduled sends designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Emergency broadcasts</h3>
              <p>EduPortal sms messaging includes emergency broadcasts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>SMS Messaging reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual texting, no delivery proof, and missed fee reminder campaigns.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How SMS Messaging works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs sms messaging today.</p>
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
            <p>Teams use sms messaging every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce manual texting, no delivery proof, and missed fee reminder campaigns, and improve logged bulk sends, targeted lists, and reliable parent reach.</p>
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
        <h2 id="why-heading">Why schools need SMS Messaging</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on manual texting, no delivery proof, and missed fee reminder campaigns. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>SMS Messaging inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When logged bulk sends, targeted lists, and reliable parent reach become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate manual texting, no delivery proof, and missed fee reminder campaigns with structured digital workflows.</li>
          <li>Achieve logged bulk sends, targeted lists, and reliable parent reach visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">SMS Messaging screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal bulk SMS messaging" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal SMS Messaging admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>SMS Messaging mobile view</h4>
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
          <h2 id="faq-heading">SMS Messaging — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <section class="fd-cta reveal-fd">
      <div class="container">
        <span class="section-label" style="color:var(--color-primary)">Get Started</span>
        <h2>Ready to modernize sms messaging?</h2>
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
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('whatsapp-communication.php')) ?>">WhatsApp Communication</a>
<a href="<?= ep_h(ep_feature_url('fee-management.php')) ?>">Fee Management</a>
<a href="<?= ep_h(ep_feature_url('parent-mobile-app.php')) ?>">Parent Mobile App</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>