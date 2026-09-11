<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'School Payroll Software Pakistan | EduPortal Staff Salary ERP';
$pageDescription  = 'Staff profiles, salary structures, deductions &amp; monthly payroll with payslips. EduPortal payroll for school ERP Pakistan linked to attendance &amp; leave.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'payroll-management';

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
            "@id": "https://eduportal.pk/payroll-management.php#breadcrumb",
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
                    "name": "Payroll Management",
                    "item": "https://eduportal.pk/payroll-management.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/payroll-management.php#webpage",
            "name": "School Payroll Software Pakistan | EduPortal Staff Salary ERP",
            "description": "Staff profiles, salary structures, deductions &amp; monthly payroll with payslips. EduPortal payroll for school ERP Pakistan linked to attendance &amp; leave.",
            "url": "https://eduportal.pk/payroll-management.php",
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
  <meta property="og:title" content="School Payroll Software Pakistan | EduPortal Staff Salary ERP">
  <meta property="og:description" content="Staff profiles, salary structures, deductions &amp; monthly payroll with payslips. EduPortal payroll for school ERP Pakistan linked to attendance &amp; leave.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is Payroll Management in EduPortal?',
     'a' => 'Payroll Management in EduPortal is a module within our school management software Pakistan that process staff payroll accurately with attendance-linked deductions. It replaces manual payslip math and mismatched leave deductions every month with automated payroll runs, payslip PDFs, and linked HR records.'],
    ['q' => 'Why is Payroll Management important for schools?',
     'a' => 'Schools lose time and money when manual payslip math and mismatched leave deductions every month. Payroll Management gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.'],
    ['q' => 'How does EduPortal\'s Payroll Management work?',
     'a' => 'Staff log in to EduPortal web or mobile apps, access the payroll management module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.'],
    ['q' => 'Is EduPortal payroll management suitable for Pakistani schools?',
     'a' => 'Yes. EduPortal is used by {total_clients} schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.'],
    ['q' => 'Can payroll management integrate with other EduPortal modules?',
     'a' => 'Absolutely. Payroll Management shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.'],
    ['q' => 'Do parents see payroll management updates?',
     'a' => 'Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.'],
    ['q' => 'How long does implementation take?',
     'a' => 'Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to payroll management workflows.'],
    ['q' => 'Is training included?',
     'a' => 'Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.'],
    ['q' => 'Can we customize payroll management for our school?',
     'a' => 'EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.'],
    ['q' => 'How do I see Payroll Management in action?',
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
          <li><span aria-current="page">Payroll Management</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="banknote" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>Payroll Management — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">Process staff payroll accurately with attendance-linked deductions. EduPortal replaces manual payslip math and mismatched leave deductions every month with automated payroll runs, payslip PDFs, and linked HR records for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal payroll and salary management" width="640" height="400" loading="eager">
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
          <h2 id="ai-overview">Everything you need to know about Payroll Management</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is Payroll Management?</h2>
            <p>Payroll Management processes staff salaries with attendance-linked deductions, generating payslip PDFs from a monthly payroll run instead of manual payslip math.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is Payroll Management important for schools?</h2>
            <p>A deduction calculated by hand from a separate leave count is where payday disputes start. Deductions pulled directly from recorded attendance and leave are what keep a payslip traceable to an actual reason.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's Payroll Management work?</h2>
            <p>A salary structure is configured once per staff member, deductions calculate from attendance and leave already tracked elsewhere, and the monthly run generates payslip PDFs ready to distribute.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>Salary structures and deduction patterns follow how Pakistani schools typically run monthly payroll, with the same process holding up whether staff headcount is ten or two hundred.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal Payroll Management</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Salary structures set once per staff member</h3>
            <p>Each person's structure, including standard deductions, is configured once rather than recalculated every month.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>Deductions linked to real attendance and leave</h3>
            <p>Payroll pulls from the attendance and leave already tracked elsewhere, instead of a separate manual count.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Payslip PDFs generated with the run</h3>
            <p>Payslips are produced automatically from the same payroll run, not typed up separately afterward.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>Monthly payroll without manual payslip math</h3>
            <p>The whole month's run compiles from configured structures and linked attendance in one pass.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>Fewer mismatched leave deductions</h3>
            <p>Because leave data feeds payroll directly, deductions match actual recorded leave instead of a manual estimate.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Accessible to HR from office or app</h3>
            <p>Payroll can be reviewed and run from a desktop or checked on the move.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Built around Pakistani payroll cycles</h3>
            <p>Monthly salary runs and standard local deduction patterns are the default setup.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Works the same for ten staff or two hundred</h3>
            <p>Payroll run performance does not change with staff headcount.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Linked HR records, not a separate system</h3>
            <p>Salary structures sit against the same staff profile used by attendance and scheduling.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Payslips ready to distribute immediately</h3>
            <p>PDFs generate as part of the run, so distribution does not wait on a separate formatting step.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete Payroll Management capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>Salary structures</h3>
              <p>EduPortal payroll management includes salary structures designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Allowances</h3>
              <p>EduPortal payroll management includes allowances designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Deductions</h3>
              <p>EduPortal payroll management includes deductions designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Monthly payroll run</h3>
              <p>EduPortal payroll management includes monthly payroll run designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Payslip PDF</h3>
              <p>EduPortal payroll management includes payslip PDF designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>Bank advice</h3>
              <p>EduPortal payroll management includes bank advice designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Leave integration</h3>
              <p>EduPortal payroll management includes leave integration designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Increment history</h3>
              <p>EduPortal payroll management includes increment history designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Tax fields</h3>
              <p>EduPortal payroll management includes tax fields designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Staff cost reports</h3>
              <p>EduPortal payroll management includes staff cost reports designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>Payroll Management reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual manual payslip math and mismatched leave deductions every month.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How Payroll Management works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs payroll management today.</p>
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
            <p>Teams use payroll management every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce manual payslip math and mismatched leave deductions every month, and improve automated payroll runs, payslip PDFs, and linked HR records.</p>
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
        <h2 id="why-heading">Why schools need Payroll Management</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on manual payslip math and mismatched leave deductions every month. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>Payroll Management inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When automated payroll runs, payslip PDFs, and linked HR records become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate manual payslip math and mismatched leave deductions every month with structured digital workflows.</li>
          <li>Achieve automated payroll runs, payslip PDFs, and linked HR records visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">Payroll Management screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal payroll and salary management" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal Payroll Management admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>Payroll Management mobile view</h4>
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
          <h2 id="faq-heading">Payroll Management — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <?php
    $ctaLabel     = 'Get Started';
    $ctaHeading   = 'Ready to modernize payroll management?';
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
        <div class="fd-related-links"><a href="<?= ep_h(ep_feature_url('accounting-management.php')) ?>">Accounting Management</a>
<a href="<?= ep_h(ep_feature_url('attendance-management.php')) ?>">Attendance Management</a>
<a href="<?= ep_h(ep_feature_url('expenses-profit-loss.php')) ?>">Expenses &amp; Profit/Loss Tracking</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>