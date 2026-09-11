<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'Teacher Mobile App for Schools | EduPortal ERP Pakistan';
$pageDescription  = 'EduPortal teacher app — mark attendance, enter marks, share homework &amp; view timetables from mobile. Essential school ERP Pakistan tool for classroom staff.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'teacher-mobile-app';

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
            "@id": "https://eduportal.pk/teacher-mobile-app.php#breadcrumb",
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
                    "name": "Teacher Mobile App",
                    "item": "https://eduportal.pk/teacher-mobile-app.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/teacher-mobile-app.php#webpage",
            "name": "Teacher Mobile App for Schools | EduPortal ERP Pakistan",
            "description": "EduPortal teacher app — mark attendance, enter marks, share homework &amp; view timetables from mobile. Essential school ERP Pakistan tool for classroom staff.",
            "url": "https://eduportal.pk/teacher-mobile-app.php",
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
  <meta property="og:title" content="Teacher Mobile App for Schools | EduPortal ERP Pakistan">
  <meta property="og:description" content="EduPortal teacher app — mark attendance, enter marks, share homework &amp; view timetables from mobile. Essential school ERP Pakistan tool for classroom staff.">
  <meta property="og:image" content="assets/parent-app.jpg">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is Teacher Mobile App in EduPortal?',
     'a' => 'Teacher Mobile App in EduPortal is a module within our school management software Pakistan that empower teachers to mark attendance, enter marks, and share updates on the go. It replaces teachers waiting for office computers to update records after class with same-day data entry, mobile timetables, and faster academic workflows.'],
    ['q' => 'Why is Teacher Mobile App important for schools?',
     'a' => 'Schools lose time and money when teachers waiting for office computers to update records after class. Teacher Mobile App gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.'],
    ['q' => 'How does EduPortal\'s Teacher Mobile App work?',
     'a' => 'Staff log in to EduPortal web or mobile apps, access the teacher mobile app module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.'],
    ['q' => 'Is EduPortal teacher mobile app suitable for Pakistani schools?',
     'a' => 'Yes. EduPortal is used by {total_clients} schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.'],
    ['q' => 'Can teacher mobile app integrate with other EduPortal modules?',
     'a' => 'Absolutely. Teacher Mobile App shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.'],
    ['q' => 'Do parents see teacher mobile app updates?',
     'a' => 'Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.'],
    ['q' => 'How long does implementation take?',
     'a' => 'Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to teacher mobile app workflows.'],
    ['q' => 'Is training included?',
     'a' => 'Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.'],
    ['q' => 'Can we customize teacher mobile app for our school?',
     'a' => 'EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.'],
    ['q' => 'How do I see Teacher Mobile App in action?',
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
          <li><span aria-current="page">Teacher Mobile App</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="tablet-smartphone" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>Teacher Mobile App — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Empower teachers to mark attendance, enter marks, and share updates on the go. EduPortal replaces teachers waiting for office computers to update records after class with same-day data entry, mobile timetables, and faster academic workflows for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/parent-app.jpg')) ?>" alt="EduPortal teacher mobile application" width="640" height="400" loading="eager">
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
          <h2 id="ai-overview">Everything you need to know about Teacher Mobile App</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is Teacher Mobile App?</h2>
            <p>The Teacher Mobile App lets teachers mark attendance, enter marks, and share day-to-day updates from a phone, scoped to their own classes and timetable.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is Teacher Mobile App important for schools?</h2>
            <p>Data entered from a shared office computer after class is data entered late, and late data is where mistakes creep in. Same-day entry from the classroom is what actually keeps records accurate.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's Teacher Mobile App work?</h2>
            <p>A teacher signs in and sees their own schedule, marks attendance during the period itself, and enters marks or updates the same day. Substitutions push to the app directly rather than through a staff-room notice.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>Period structures, substitution norms and bilingual screens are set up for how Pakistani school days actually run, and the same account covers attendance, marks and lesson plans without separate logins per task.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal Teacher Mobile App</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Attendance marked from the classroom</h3>
            <p>Teachers mark their own class during the period itself, instead of waiting for a shared office computer after.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>Marks and updates entered the same day</h3>
            <p>Data entry happens from wherever the teacher is, so a day's work does not sit unrecorded until they reach a desktop.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Each teacher sees only their own classes</h3>
            <p>The app scopes to a teacher's own timetable and records — not the whole school's data.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>Substitutions reach teachers directly</h3>
            <p>A last-minute schedule change is pushed to the phone instead of relying on a staff-room notice board.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>The mobile timetable is the real one</h3>
            <p>What a teacher sees on their phone is the same schedule the office is working from, not a static printout.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Cuts the wait for shared computers</h3>
            <p>Multiple teachers no longer queue for the same office desktop to log attendance or marks between classes.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Same login across every EduPortal screen</h3>
            <p>Teachers use one account for attendance, marks and lesson plans rather than separate logins per task.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Built for Pakistani school timetables</h3>
            <p>Period structures, substitution patterns and bilingual interfaces match how local schools actually run the day.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Works the same for one teacher or two hundred</h3>
            <p>Adoption scales campus-wide without a different setup per teacher.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Feeds straight into attendance and exam records</h3>
            <p>What a teacher enters from the app updates the same records the office and parents see — no manual re-entry.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete Teacher Mobile App capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>Mobile attendance</h3>
              <p>EduPortal teacher mobile app includes mobile attendance designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Marks entry</h3>
              <p>EduPortal teacher mobile app includes marks entry designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Homework posting</h3>
              <p>EduPortal teacher mobile app includes homework posting designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Timetable view</h3>
              <p>EduPortal teacher mobile app includes timetable view designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Class lists</h3>
              <p>EduPortal teacher mobile app includes class lists designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Leave approval view</h3>
              <p>EduPortal teacher mobile app includes leave approval view designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Lesson plan logs</h3>
              <p>EduPortal teacher mobile app includes lesson plan logs designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Push alerts</h3>
              <p>EduPortal teacher mobile app includes push alerts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Offline queue</h3>
              <p>EduPortal teacher mobile app includes offline queue designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Secure login</h3>
              <p>EduPortal teacher mobile app includes secure login designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>Teacher Mobile App reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual teachers waiting for office computers to update records after class.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How Teacher Mobile App works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs teacher mobile app today.</p>
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
            <p>Teams use teacher mobile app every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce teachers waiting for office computers to update records after class, and improve same-day data entry, mobile timetables, and faster academic workflows.</p>
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
        <h2 id="why-heading">Why schools need Teacher Mobile App</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on teachers waiting for office computers to update records after class. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>Teacher Mobile App inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When same-day data entry, mobile timetables, and faster academic workflows become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate teachers waiting for office computers to update records after class with structured digital workflows.</li>
          <li>Achieve same-day data entry, mobile timetables, and faster academic workflows visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">Teacher Mobile App screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/parent-app.jpg')) ?>" alt="EduPortal teacher mobile application" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal Teacher Mobile App admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>Teacher Mobile App mobile view</h4>
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
          <h2 id="faq-heading">Teacher Mobile App — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <?php
    $ctaLabel     = 'Get Started';
    $ctaHeading   = 'Ready to modernize teacher mobile app?';
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
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('parent-mobile-app.php')) ?>">Parent Mobile App</a>
<a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>">Attendance Management</a>
<a href="<?= ep_h(ep_feature_url('examination-management.php')) ?>">Examination Management</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>