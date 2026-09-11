<?php
require_once __DIR__ . '/includes/cms.php';

$pageTitle        = 'School Expense &amp; P&amp;L Tracking | EduPortal ERP Pakistan';
$pageDescription  = 'Record daily expenses by category and see real-time profit &amp; loss vs fee income. School owners get clear dashboards with EduPortal school ERP Pakistan.';
$canonicalUrl     = ep_feature_canonical(__FILE__);
$navActive        = 'features';
$useDemoModal     = true;
$extraStylesheets = ['css/shared.css', 'css/feature-detail.css', 'css/lead-toast.css'];
$extraBodyScripts = ['js/country-codes.js', 'js/lead-form.js', 'js/feature-detail.js'];
$epTrackPage      = 'expenses-profit-loss';

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
            "@id": "https://eduportal.pk/expenses-profit-loss.php#breadcrumb",
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
                    "name": "Expenses & Profit/Loss Tracking",
                    "item": "https://eduportal.pk/expenses-profit-loss.php"
                }
            ]
        },
        {
            "@type": "WebPage",
            "@id": "https://eduportal.pk/expenses-profit-loss.php#webpage",
            "name": "School Expense &amp; P&amp;L Tracking | EduPortal ERP Pakistan",
            "description": "Record daily expenses by category and see real-time profit &amp; loss vs fee income. School owners get clear dashboards with EduPortal school ERP Pakistan.",
            "url": "https://eduportal.pk/expenses-profit-loss.php",
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
  <meta property="og:title" content="School Expense &amp; P&amp;L Tracking | EduPortal ERP Pakistan">
  <meta property="og:description" content="Record daily expenses by category and see real-time profit &amp; loss vs fee income. School owners get clear dashboards with EduPortal school ERP Pakistan.">
  <meta property="og:image" content="assets/dashboard.png">
HTML;

// FAQ content for this module. One array drives both the visible accordion
// and the FAQPage structured data, so the two cannot drift apart.
$moduleFaqs = [
    ['q' => 'What is Expenses & Profit/Loss Tracking in EduPortal?',
     'a' => 'Expenses & Profit/Loss Tracking in EduPortal is a module within our school management software Pakistan that see where money goes and how fee income compares to operating costs. It replaces owners discovering cash shortfalls weeks after expenses pile up with category-wise spend visibility, monthly P&L, and smarter budget calls.'],
    ['q' => 'Why is Expenses & Profit/Loss Tracking important for schools?',
     'a' => 'Schools lose time and money when owners discovering cash shortfalls weeks after expenses pile up. Expenses & Profit/Loss Tracking gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.'],
    ['q' => 'How does EduPortal\'s Expenses & Profit/Loss Tracking work?',
     'a' => 'Staff log in to EduPortal web or mobile apps, access the expenses & profit/loss tracking module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.'],
    ['q' => 'Is EduPortal expenses & profit/loss tracking suitable for Pakistani schools?',
     'a' => 'Yes. EduPortal is used by {total_clients} schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.'],
    ['q' => 'Can expenses & profit/loss tracking integrate with other EduPortal modules?',
     'a' => 'Absolutely. Expenses & Profit/Loss Tracking shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.'],
    ['q' => 'Do parents see expenses & profit/loss tracking updates?',
     'a' => 'Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.'],
    ['q' => 'How long does implementation take?',
     'a' => 'Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to expenses & profit/loss tracking workflows.'],
    ['q' => 'Is training included?',
     'a' => 'Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.'],
    ['q' => 'Can we customize expenses & profit/loss tracking for our school?',
     'a' => 'EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.'],
    ['q' => 'How do I see Expenses & Profit/Loss Tracking in action?',
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
          <li><span aria-current="page">Expenses &amp; Profit/Loss Tracking</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="trending-up" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>Expenses &amp; Profit/Loss Tracking — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">See where money goes and how fee income compares to operating costs. EduPortal replaces owners discovering cash shortfalls weeks after expenses pile up with category-wise spend visibility, monthly P&amp;L, and smarter budget calls for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="<?= ep_h(ep_url('contact.php')) ?>" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal expense and profit loss tracking" width="640" height="400" loading="eager">
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
          <h2 id="ai-overview">Everything you need to know about Expenses &amp; Profit/Loss Tracking</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is Expenses &amp; Profit/Loss Tracking?</h2>
            <p>Expenses & Profit/Loss Tracking records spending by category — utilities, maintenance, supplies — and compares it against fee income to show real-time profit and loss.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is Expenses &amp; Profit/Loss Tracking important for schools?</h2>
            <p>A cash shortfall discovered weeks after expenses have already piled up leaves no room to adjust. Seeing category-wise spend against income every month is what catches a budget problem while it's still fixable.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's Expenses &amp; Profit/Loss Tracking work?</h2>
            <p>Expenses are logged under their category as they happen rather than reconstructed from receipts later, and because fee income is already tracked in Fee Management, the P&L comparison happens automatically rather than being assembled by hand.</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>Expense categories match how Pakistani school budgets are typically organized, and the same monthly P&L view scales from a single campus's budget to a consolidated view across a multi-branch group.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal Expenses &amp; Profit/Loss Tracking</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="zap"></i></div>
            <h3>Spending broken down by category</h3>
            <p>Utilities, maintenance and supplies are tracked separately, not lumped into one expense total.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="shield-check"></i></div>
            <h3>Recorded as it happens, not reconstructed later</h3>
            <p>Expenses log against their category the day they occur instead of being pieced together from receipts at month end.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="clock"></i></div>
            <h3>Compared against real fee income automatically</h3>
            <p>Because Fee Management already tracks collections, expenses are set against real income rather than an estimate.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="trending-up"></i></div>
            <h3>Monthly P&L, not a year-end surprise</h3>
            <p>Profit and loss is visible every month, so a cash shortfall is caught while there is still time to react.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="users"></i></div>
            <h3>Category-wise visibility for smarter budgeting</h3>
            <p>Seeing which category is driving cost makes the next budget decision specific instead of a guess.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="smartphone"></i></div>
            <h3>Same dashboard for owners and accountants</h3>
            <p>Both roles look at the same current numbers, not two different versions reconciled separately.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="bar-chart-3"></i></div>
            <h3>Accessible from office or app</h3>
            <p>Expenses can be logged and reviewed from a desktop or a phone.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="check-circle"></i></div>
            <h3>Built around how Pakistani schools categorize spend</h3>
            <p>Utility, maintenance and supply categories match how local school budgets are already organized.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="lock"></i></div>
            <h3>Scales from one campus's budget to a group's</h3>
            <p>Category-wise tracking works the same way whether it is a single campus or several branches under one owner.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="globe"></i></div>
            <h3>Feeds the same books as Accounting</h3>
            <p>Expense data connects directly into Accounting Management rather than living in a separate spreadsheet.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="heart-handshake"></i></div>
            <h3>Smarter budget calls, not just recordkeeping</h3>
            <p>Owners make budget decisions from current category-wise data instead of reacting after a shortfall is already felt.</p>
          </article>

          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="sparkles"></i></div>
            <h3>No separate expense-tracking app</h3>
            <p>Expense and P&L tracking is part of the same platform as fees and accounting, not a bolt-on tool.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete Expenses &amp; Profit/Loss Tracking capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="circle-dot"></i></div>
            <div>
              <h3>Expense categories</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes expense categories designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="layers"></i></div>
            <div>
              <h3>Daily entry</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes daily entry designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="file-text"></i></div>
            <div>
              <h3>Vendor tracking</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes vendor tracking designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="settings"></i></div>
            <div>
              <h3>Utility bills</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes utility bills designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="bell"></i></div>
            <div>
              <h3>Maintenance logs</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes maintenance logs designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="database"></i></div>
            <div>
              <h3>P&amp;L dashboard</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes P&amp;L dashboard designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="search"></i></div>
            <div>
              <h3>Month comparison</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes month comparison designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="download"></i></div>
            <div>
              <h3>Campus-wise split</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes campus-wise split designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="upload"></i></div>
            <div>
              <h3>Approval workflow</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes approval workflow designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="filter"></i></div>
            <div>
              <h3>Export to accounts</h3>
              <p>EduPortal expenses &amp; profit/loss tracking includes export to accounts designed for daily school operations in Pakistan — simple for staff, powerful for administrators.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="tag"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="calendar"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="mail"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="phone"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="map-pin"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="credit-card"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="percent"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>

          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="list-checks"></i></div>
            <div>
              <h3>Expenses &amp; Profit/Loss Tracking reporting &amp; exports</h3>
              <p>Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual owners discovering cash shortfalls weeks after expenses pile up.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How Expenses &amp; Profit/Loss Tracking works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          
          <article class="fd-step reveal-fd">
            <h3>Configure your school setup</h3>
            <p>Define classes, sessions, and rules that match how your campus runs expenses &amp; profit/loss tracking today.</p>
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
            <p>Teams use expenses &amp; profit/loss tracking every day while parents receive updates through app, SMS, or WhatsApp.</p>
          </article>

          <article class="fd-step reveal-fd">
            <h3>Review analytics &amp; optimize</h3>
            <p>Owners use dashboards to refine policies, reduce owners discovering cash shortfalls weeks after expenses pile up, and improve category-wise spend visibility, monthly P&amp;L, and smarter budget calls.</p>
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
        <h2 id="why-heading">Why schools need Expenses &amp; Profit/Loss Tracking</h2>
        <p>Running a school in 2026 means managing more data than ever — yet many campuses still depend on owners discovering cash shortfalls weeks after expenses pile up. That creates delays, errors, and frustrated parents who expect instant answers about their children.</p>
        <p>Expenses &amp; Profit/Loss Tracking inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.</p>
        <p>When category-wise spend visibility, monthly P&amp;L, and smarter budget calls become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.</p>
        <p>Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.</p>
        <ul>
          <li>Eliminate owners discovering cash shortfalls weeks after expenses pile up with structured digital workflows.</li>
          <li>Achieve category-wise spend visibility, monthly P&amp;L, and smarter budget calls visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">Expenses &amp; Profit/Loss Tracking screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal expense and profit loss tracking" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="<?= ep_h(ep_url('assets/dashboard.png')) ?>" alt="EduPortal Expenses &amp; Profit/Loss Tracking admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>Expenses &amp; Profit/Loss Tracking mobile view</h4>
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
          <h2 id="faq-heading">Expenses &amp; Profit/Loss Tracking — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          
          <?php ep_render_faq_accordion($moduleFaqs); ?>
        </div>
      </div>
    </section>

    <?php
    $ctaLabel     = 'Get Started';
    $ctaHeading   = 'Ready to modernize expenses & profit/loss tracking?';
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
<a href="<?= ep_h(ep_feature_url('fee-management.php')) ?>">Fee Management</a>
<a href="<?= ep_h(ep_feature_url('inventory-management.php')) ?>">Inventory Management</a></div>
      </div>
    </section>
  

<?php require __DIR__ . '/includes/partials/demo-modal.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>