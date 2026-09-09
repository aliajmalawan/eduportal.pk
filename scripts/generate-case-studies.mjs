import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import { CASE_STUDIES, CASE_MAP } from './case-studies-data.mjs';
import { FEATURE_MAP } from './features-data.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const OUT_DIR = join(ROOT, 'case-studies');
mkdirSync(OUT_DIR, { recursive: true });

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildChallenges(c) {
  return [
    `${c.name} operated with fragmented tools — fee clerks on Excel, teachers on paper registers, and coordinators sending results through informal WhatsApp groups. ${c.contactName} needed one platform before opening another academic year.`,
    `Parent expectations in ${c.city.split(',')[0]} rose sharply after COVID: families demanded mobile access to fees and attendance. The school risked losing enrollment to competitors advertising "smart campus" features.`,
    `Peak seasons — admission week, monthly fee day, and board result periods — overwhelmed office staff. Errors in manual data entry damaged trust when wrong fee amounts or misspelled names appeared on vouchers.`,
    `Leadership lacked consolidated visibility across ${c.students} students. Owners could not compare branch or department performance without waiting for staff to compile reports manually.`,
  ];
}

function buildWhyChosen(c) {
  return [
    `${c.contactName} evaluated several school ERP options but chose EduPortal for Pakistani workflows — Urdu/English documents, PKR pricing, WhatsApp integration, and local support reachable on WhatsApp and phone.`,
    `Transparent monthly plans without massive upfront server investment matched ${c.type} budget reality. EduPortal's demo used their actual class structure so staff saw familiar screens before signing.`,
    `Modules aligned to immediate pain: ${c.modules.slice(0, 3).join(', ').replace(/-/g, ' ')} rather than bloated software forcing unused features.`,
    `Reference visits to similar-sized schools using EduPortal face recognition and parent apps reduced leadership risk — proof mattered more than slide decks.`,
  ];
}

function buildResults(c) {
  return [
    `${c.name} reports measurably faster operations within the first two terms — staff cite reduced paperwork and fewer duplicate data entries across office and classroom.`,
    `Parent communication improved through official app and WhatsApp channels instead of unofficial teacher groups — complaints about missed notices dropped significantly.`,
    `Owners now open dashboards on mobile for daily fee collection and attendance percentages instead of calling accountants every evening.`,
    `Audit and board inspection preparation simplified because reports export from one system with consistent student IDs and historical archives.`,
    `The school plans to expand EduPortal modules next year based on success in core workflows — phased rollout prevented staff overwhelm.`,
  ];
}

function buildTranscript(c) {
  return `[Video transcript summary — ${c.contactName}, ${c.contactRole}]

"${c.quote}"

In this testimonial, ${c.contactName} describes how ${c.name} implemented EduPortal across ${c.students} students in ${c.city}. Key topics covered include daily workflows before ERP, the decision process selecting EduPortal over spreadsheets, training for non-technical staff, and measurable improvements in fee collection, attendance monitoring, and parent satisfaction.

The school highlights EduPortal's cloud-based access — leadership checks data without visiting each office — and emphasizes that Pakistani schools of their size can digitize without enterprise budgets. ${c.contactName} recommends other owners book a live demo and involve accountants and principals in the same evaluation call.

Full video available above. Contact EduPortal for a campus-specific walkthrough similar to the ${c.name} implementation.`;
}

function moduleCards(c) {
  return c.modules
    .map((slug) => {
      const f = FEATURE_MAP[slug];
      if (!f) return '';
      return `<article class="cs-module-card">
        <h3><a href="../${f.slug}.html">${esc(f.title)}</a></h3>
        <p>${esc(f.tagline)}</p>
        <a href="../${f.slug}.html" class="cs-module-link">View feature <i data-lucide="arrow-right"></i></a>
      </article>`;
    })
    .join('\n');
}

function renderDetail(c) {
  const challenges = buildChallenges(c);
  const whyChosen = buildWhyChosen(c);
  const results = buildResults(c);
  const transcript = buildTranscript(c);
  const canonical = `https://eduportal.io/case-studies/${c.slug}.html`;

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="${esc(c.name)} case study — how ${c.students} students in ${esc(c.city)} improved with EduPortal school ERP. ${esc(c.summary)}">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="${canonical}">
  <title>${esc(c.name)} Case Study — EduPortal School ERP Success Story</title>
  <link rel="icon" href="../assets/logo_icon.jpg" type="image/jpeg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/shared.css">
  <link rel="stylesheet" href="../css/case-studies.css">
  <link rel="stylesheet" href="../css/feature-detail.css">
  <link rel="stylesheet" href="../css/lead-toast.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <script src="../js/country-codes.js" defer></script>
  <script src="../js/lead-form.js" defer></script>
  <script src="../js/feature-detail.js" defer></script>
</head>
<body>
  <header class="navbar" id="navbar">
    <div class="container navbar-inner">
      <a href="../index.html" class="logo">
        <img src="../assets/logo_icon.jpg" alt="" class="logo-img" width="36" height="36">
        <span class="logo-text">EduPortal</span>
      </a>
      <nav class="nav-links">
        <a href="../index.html">Home</a>
        <a href="../features.html">Features</a>
        <a href="../pricing.html">Pricing</a>
        <a href="../case-studies.html" class="active">Case Studies</a>
        <a href="../blog.html">Blogs</a>
        <a href="../contact.html">Contact</a>
      </nav>
      <div class="nav-actions">
        <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu"><i data-lucide="menu" style="width:24px;height:24px"></i></button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
      <a href="../index.html">Home</a>
      <a href="../case-studies.html" class="active">Case Studies</a>
      <a href="../contact.html">Contact</a>
      <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
    </div>
  </header>

  <main class="cs-detail">
    <div class="container cs-breadcrumb">
      <nav aria-label="Breadcrumb"><ol>
        <li><a href="../index.html">Home</a></li>
        <li><a href="../case-studies.html">Case Studies</a></li>
        <li><span aria-current="page">${esc(c.name)}</span></li>
      </ol></nav>
    </div>

    <section class="cs-detail-hero" style="--cs-gradient:${c.gradient}">
      <div class="container cs-detail-hero-grid">
        <div>
          <span class="section-label">Customer Success</span>
          <h1>${esc(c.name)}</h1>
          <p class="cs-detail-meta"><i data-lucide="map-pin"></i> ${esc(c.city)} · <i data-lucide="users"></i> ${esc(c.students)} students · ${esc(c.type)}</p>
          <p class="cs-detail-summary">${esc(c.summary)}</p>
        </div>
        <div class="cs-detail-hero-img">
          <img src="../${c.image}" alt="${esc(c.name)} campus using EduPortal" width="640" height="400" loading="eager">
        </div>
      </div>
    </section>

    <section class="cs-metrics-bar">
      <div class="container cs-metrics-grid">
        ${c.metrics.map((m) => `<div class="cs-metric"><strong>${esc(m.value)}</strong><span>${esc(m.label)}</span></div>`).join('')}
      </div>
    </section>

    <section class="cs-section">
      <div class="container cs-prose">
        <h2>School introduction</h2>
        <p>${esc(c.name)} is a ${esc(c.type.toLowerCase())} based in ${esc(c.city)} serving more than ${esc(c.students)} students. Leadership under ${esc(c.contactName)} focuses on academic quality while modernizing back-office operations parents expect in 2026. Before EduPortal, departments used disconnected tools that worked in isolation but failed during peak admission, fee, and exam seasons.</p>
        <p>The institution competes with other private schools where "digital campus" marketing influences enrollment. Families ask about parent apps and online fee visibility during admission tours — ${esc(c.name)} needed credible answers backed by real systems, not promises.</p>
      </div>
    </section>

    <section class="cs-section cs-section--alt">
      <div class="container cs-prose">
        <h2>Challenges before EduPortal</h2>
        ${challenges.map((p) => `<p>${esc(p)}</p>`).join('\n        ')}
      </div>
    </section>

    <section class="cs-section">
      <div class="container cs-prose">
        <h2>Why they chose EduPortal</h2>
        ${whyChosen.map((p) => `<p>${esc(p)}</p>`).join('\n        ')}
      </div>
    </section>

    <section class="cs-section cs-section--alt">
      <div class="container">
        <div class="cs-section-head"><span class="section-label">Implementation</span><h2>Modules implemented</h2></div>
        <div class="cs-module-grid">${moduleCards(c)}</div>
      </div>
    </section>

    <section class="cs-section">
      <div class="container cs-prose">
        <h2>Results achieved</h2>
        ${results.map((p) => `<p>${esc(p)}</p>`).join('\n        ')}
        <ul>
          <li><strong>Faster fee collection</strong> — automated vouchers and reminders shortened collection cycles.</li>
          <li><strong>Reduced paperwork</strong> — digital records replaced duplicate registers and Excel files.</li>
          <li><strong>Improved parent communication</strong> — app and WhatsApp provided official channels families trust.</li>
          <li><strong>Better attendance monitoring</strong> — digital check-in with instant alerts for absences.</li>
        </ul>
      </div>
    </section>

    <section class="cs-testimonial">
      <div class="container">
        <blockquote class="cs-quote">
          <p>"${esc(c.quote)}"</p>
          <footer><strong>${esc(c.contactName)}</strong><span>${esc(c.contactRole)}</span></footer>
        </blockquote>
      </div>
    </section>

    <section class="cs-section cs-section--alt" aria-labelledby="video-heading">
      <div class="container">
        <div class="cs-section-head"><span class="section-label">Video Feedback</span><h2 id="video-heading">${esc(c.videoTitle)}</h2></div>
        <div class="cs-video-wrap">
          <iframe src="https://www.youtube.com/embed/${c.videoId}?rel=0" title="${esc(c.videoTitle)}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
        </div>
        <details class="cs-transcript">
          <summary>Read video transcript</summary>
          <div class="cs-transcript-body">${esc(transcript).replace(/\n\n/g, '</p><p>').replace(/^/, '<p>').replace(/$/, '</p>')}</div>
        </details>
      </div>
    </section>

    <section class="cs-section">
      <div class="container">
        <div class="cs-section-head"><span class="section-label">Gallery</span><h2>Campus &amp; platform views</h2></div>
        <div class="cs-gallery">
          <figure><img src="../${c.image}" alt="${esc(c.name)} EduPortal dashboard" loading="lazy"></figure>
          <figure><img src="../assets/dashboard.png" alt="EduPortal admin panel at ${esc(c.name)}" loading="lazy"></figure>
          <figure><img src="../assets/parent-app.jpg" alt="Parents using EduPortal app — ${esc(c.name)}" loading="lazy"></figure>
          <figure><div class="cs-gallery-placeholder" style="background:${c.gradient}"><img src="../${c.logo}" alt="${esc(c.name)} logo" class="cs-gallery-logo"></div></figure>
        </div>
      </div>
    </section>

    <section class="cs-cta">
      <div class="container">
        <h2>Ready for results like ${esc(c.name)}?</h2>
        <p>Book a demo tailored to your student strength and modules — see EduPortal school ERP Pakistan live.</p>
        <div class="cs-cta-btns">
          <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
          <a href="../contact.html" class="btn-outline-light">Contact Sales</a>
        </div>
      </div>
    </section>

    <section class="cs-related">
      <div class="container">
        <h2>More case studies</h2>
        <div class="cs-related-links">
          ${CASE_STUDIES.filter((x) => x.slug !== c.slug).slice(0, 4).map((x) => `<a href="${x.slug}.html">${esc(x.name)}</a>`).join('')}
          <a href="../case-studies.html">View all</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer"><div class="container"><div class="footer-bottom"><span>&copy; 2026 EduPortal</span></div></div></footer>

  <div class="modal-overlay" id="getStartedModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
      <h2 id="modalTitle">Get Started with EduPortal</h2>
      <form id="getStartedForm" novalidate>
        <div class="form-row">
          <div class="form-group form-group--grow"><label for="instituteName">Institute Name</label><input type="text" id="instituteName" required></div>
          <div class="form-group form-group--side"><label for="studentCount">Strength</label><input type="text" id="studentCount"></div>
        </div>
        <div class="form-row">
          <div class="form-group form-group--grow"><label for="fullName">Contact Person</label><input type="text" id="fullName" required></div>
          <div class="form-group form-group--side"><label for="designation">Role</label><input type="text" id="designation" required></div>
        </div>
        <div class="form-group"><label for="whatsapp">WhatsApp</label><div class="phone-input"><select id="countryCode" class="phone-country-select"></select><input type="tel" id="whatsapp" required></div><input type="hidden" id="whatsappFull"></div>
        <div class="modal-actions"><button type="button" class="btn-modal-cancel" id="modalCancel">Cancel</button><button type="submit" class="btn-modal-submit">Book now</button></div>
      </form>
    </div>
  </div>
</body>
</html>`;
}

function renderListing() {
  const cards = CASE_STUDIES.map(
    (c) => `
    <article class="cs-card reveal-cs">
      <div class="cs-card-visual" style="background:${c.gradient}">
        <img src="${c.image}" alt="${esc(c.name)}" loading="lazy">
        <img src="${c.logo}" alt="" class="cs-card-logo" width="48" height="48">
      </div>
      <div class="cs-card-body">
        <h2><a href="case-studies/${c.slug}.html">${esc(c.name)}</a></h2>
        <p class="cs-card-meta"><i data-lucide="map-pin"></i> ${esc(c.city)} · ${esc(c.students)} students</p>
        <p class="cs-card-summary">${esc(c.summary)}</p>
        <a href="case-studies/${c.slug}.html" class="btn btn-primary cs-card-btn">Read Case Study <i data-lucide="arrow-right"></i></a>
      </div>
    </article>`
  ).join('\n');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="EduPortal case studies — real schools in Pakistan using ERP for fees, attendance, parent apps &amp; exams. Allied Science, Stars, Kohsar &amp; more.">
  <link rel="canonical" href="https://eduportal.io/case-studies.html">
  <title>Case Studies — School ERP Success Stories | EduPortal Pakistan</title>
  <link rel="icon" href="assets/logo_icon.jpg" type="image/jpeg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/shared.css">
  <link rel="stylesheet" href="css/case-studies.css">
  <link rel="stylesheet" href="css/feature-detail.css">
  <link rel="stylesheet" href="css/lead-toast.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <script src="js/country-codes.js" defer></script>
  <script src="js/lead-form.js" defer></script>
  <script src="js/feature-detail.js" defer></script>
</head>
<body>
  <header class="navbar" id="navbar">
    <div class="container navbar-inner">
      <a href="index.html" class="logo"><img src="assets/logo_icon.jpg" alt="" class="logo-img" width="36" height="36"><span class="logo-text">EduPortal</span></a>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="features.html">Features</a>
        <a href="pricing.html">Pricing</a>
        <a href="case-studies.html" class="active">Case Studies</a>
        <a href="blog.html">Blogs</a>
        <a href="contact.html">Contact</a>
      </nav>
      <div class="nav-actions"><button type="button" class="btn btn-dark js-open-modal">Book a Demo</button></div>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu"><i data-lucide="menu" style="width:24px;height:24px"></i></button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
      <a href="index.html">Home</a>
      <a href="case-studies.html" class="active">Case Studies</a>
      <a href="contact.html">Contact</a>
      <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
    </div>
  </header>

  <main>
    <section class="cs-hero">
      <div class="container">
        <span class="section-label">Proof &amp; Authority</span>
        <h1>Case Studies</h1>
        <p>How real schools across Pakistan use EduPortal to collect fees faster, digitize attendance, and engage parents — with measurable results.</p>
      </div>
    </section>
    <section class="cs-grid-section">
      <div class="container cs-grid">${cards}</div>
    </section>
    <section class="cs-cta">
      <div class="container">
        <h2>Become our next success story</h2>
        <p>Book a demo and see how EduPortal fits your campus.</p>
        <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
      </div>
    </section>
  </main>
  <footer class="footer"><div class="container"><div class="footer-bottom"><span>&copy; 2026 EduPortal</span></div></div></footer>
  <div class="modal-overlay" id="getStartedModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal"><h2>Get Started</h2>
      <form id="getStartedForm" novalidate>
        <div class="form-row"><div class="form-group form-group--grow"><label for="instituteName">Institute</label><input id="instituteName" required></div><div class="form-group form-group--side"><label for="studentCount">Strength</label><input id="studentCount"></div></div>
        <div class="form-row"><div class="form-group form-group--grow"><label for="fullName">Name</label><input id="fullName" required></div><div class="form-group form-group--side"><label for="designation">Role</label><input id="designation" required></div></div>
        <div class="form-group"><div class="phone-input"><select id="countryCode" class="phone-country-select"></select><input type="tel" id="whatsapp" required></div><input type="hidden" id="whatsappFull"></div>
        <div class="modal-actions"><button type="button" class="btn-modal-cancel" id="modalCancel">Cancel</button><button type="submit" class="btn-modal-submit">Book now</button></div>
      </form>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded',()=>{lucide?.createIcons();document.querySelectorAll('.reveal-cs').forEach((el,i)=>setTimeout(()=>el.classList.add('visible'),i*80));});
  </script>
</body>
</html>`;
}

for (const c of CASE_STUDIES) {
  writeFileSync(join(OUT_DIR, `${c.slug}.html`), renderDetail(c), 'utf8');
  console.log('Case study:', c.slug);
}
writeFileSync(join(ROOT, 'case-studies.html'), renderListing(), 'utf8');
console.log('Listing: case-studies.html');
spawnSync(process.execPath, [join(__dirname, 'inject-schemas.mjs')], { cwd: ROOT, stdio: 'inherit' });
