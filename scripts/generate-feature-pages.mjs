import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import { FEATURES, FEATURE_MAP } from './features-data.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');

const BENEFIT_ICONS = ['zap', 'shield-check', 'clock', 'trending-up', 'users', 'smartphone', 'bar-chart-3', 'check-circle', 'lock', 'globe', 'heart-handshake', 'sparkles'];
const BREAKDOWN_ICONS = ['circle-dot', 'layers', 'file-text', 'settings', 'bell', 'database', 'search', 'download', 'upload', 'filter', 'tag', 'calendar', 'mail', 'phone', 'map-pin', 'credit-card', 'percent', 'list-checks', 'eye', 'refresh-cw'];

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildBenefits(f) {
  if (f.slug === 'attendance-management') {
    return [
      { title: '1-second AI face recognition', desc: 'Our attendance machine scans faces in about one second — faster than manual registers and ideal for busy morning rushes at school gates.' },
      { title: 'No manual machine registration', desc: 'Students and staff are identified from photos already in EduPortal. The device auto-syncs — no typing names into the machine one by one.' },
      { title: 'Works for students & staff', desc: 'One system covers learners and employees. Trusted by large chains and small institutes across Pakistan.' },
      { title: 'QR code card scanning', desc: 'Budget-friendly digital attendance — scan QR codes auto-printed on ID cards designed in EduPortal.' },
      { title: 'Instant software sync', desc: 'Every check-in updates dashboards, reports, and parent apps in real time inside your school ERP Pakistan.' },
      { title: 'Same-day parent alerts', desc: 'Absent and late notifications via SMS, app, or WhatsApp — families stay informed without calling the office.' },
      { title: 'Flexible for every budget', desc: 'Choose face recognition for speed, QR cards for lower cost, or manual/app marking — or combine all methods.' },
      { title: 'Audit-ready reports', desc: 'Daily, monthly, and board-format attendance sheets export in one click for inspections and meetings.' },
      { title: 'Linked to ID Cards module', desc: 'QR codes on student and staff cards are generated automatically when you print cards from EduPortal.' },
      { title: 'Proven at scale', desc: 'From boutique academies to multi-branch groups — digital attendance that grows with enrollment.' },
    ];
  }
  if (f.slug === 'id-cards-generation') {
    return [
      { title: 'Auto QR on every card', desc: 'QR codes embed automatically — cards work instantly with EduPortal QR attendance scanning.' },
      { title: 'No external designer needed', desc: 'Templates, photos, and data merge inside your ERP. Print admission cards the same day students enroll.' },
      { title: 'Student & staff cards', desc: 'One workflow for learners and employees with consistent branding and scannable codes.' },
      { title: 'Photos from software profiles', desc: 'Pull pictures directly from SIS records — no duplicate photo collection drives.' },
      { title: 'Batch PDF printing', desc: 'Generate hundreds of cards before term start. Reprint lost cards in minutes.' },
      { title: 'Custom branding', desc: 'School logo, colors, class, blood group, and emergency contacts on professional layouts.' },
      { title: 'Attendance-ready output', desc: 'Cards are designed for daily use at gates and classrooms — not just display.' },
      { title: 'Lower cost digital attendance', desc: 'Schools wanting affordable check-in pair QR cards with phone scanners — no expensive hardware required.' },
      { title: 'Integrated with full ERP', desc: 'Card data stays linked to student records, attendance, and access control in one platform.' },
      { title: 'Fast admission season', desc: 'Skip designer queues during April rush — office staff print cards themselves.' },
    ];
  }
  const base = [
    { title: 'Save admin hours every week', desc: `Automate repetitive ${f.title.toLowerCase()} tasks so your office team focuses on students, not spreadsheets.` },
    { title: 'Single source of truth', desc: `All ${f.title.toLowerCase()} data lives inside EduPortal school ERP Pakistan — no duplicate tools or manual sync.` },
    { title: 'Real-time visibility for owners', desc: 'Principals and school owners see live dashboards instead of waiting for end-of-month summaries.' },
    { title: 'Parent trust & transparency', desc: 'When families see accurate updates in the app, phone calls to the front desk drop dramatically.' },
    { title: 'Audit-ready records', desc: 'Every action is logged with timestamps — ideal for board inspections, franchisor reviews, and internal audits.' },
    { title: 'Role-based security', desc: 'Teachers, accountants, and admins see only what their job requires. Sensitive data stays protected.' },
    { title: 'Works on mobile & desktop', desc: 'Staff update records from office computers or mobile apps — data syncs instantly across campus.' },
    { title: 'Built for Pakistani schools', desc: 'Fee calendars, Urdu/English workflows, local boards, and WhatsApp-first parent culture are supported out of the box.' },
    { title: 'Scales with enrollment', desc: 'Whether you have 200 or 2,000 students, performance stays fast during admission and exam peaks.' },
    { title: 'Integrated with full ERP', desc: `${f.title} connects natively to fees, attendance, exams, and finance — no brittle integrations.` },
    { title: 'Faster decision making', desc: `Leaders spot issues early with ${f.outcomes} instead of reacting after problems escalate.` },
    { title: 'Lower operational cost', desc: 'Reduce paper, printing, and manual labor — typical schools recover software cost within one fee cycle.' },
  ];
  return base.slice(0, 10 + (f.slug.length % 3));
}

function buildBreakdown(f) {
  const items = f.modules.map((mod, i) => ({
    icon: BREAKDOWN_ICONS[i % BREAKDOWN_ICONS.length],
    title: mod.replace(/^\w/, (c) => c.toUpperCase()),
    desc: `EduPortal ${f.title.toLowerCase()} includes ${mod} designed for daily school operations in Pakistan — simple for staff, powerful for administrators.`,
  }));
  while (items.length < 18) {
    const i = items.length;
    items.push({
      icon: BREAKDOWN_ICONS[i % BREAKDOWN_ICONS.length],
      title: `${f.title} reporting & exports`,
      desc: `Generate filtered reports and PDF exports for leadership reviews, saving hours compared to manual ${f.pain}.`,
    });
  }
  return items.slice(0, 20);
}

function buildSteps(f) {
  return [
    { title: 'Configure your school setup', desc: `Define classes, sessions, and rules that match how your campus runs ${f.title.toLowerCase()} today.` },
    { title: 'Import or enter existing data', desc: 'Migrate spreadsheets or start fresh — EduPortal support helps during onboarding so nothing is lost.' },
    { title: 'Train staff in one session', desc: 'Intuitive screens mean teachers and office staff adopt quickly without lengthy IT projects.' },
    { title: 'Go live with daily workflows', desc: `Teams use ${f.title.toLowerCase()} every day while parents receive updates through app, SMS, or WhatsApp.` },
    { title: 'Review analytics & optimize', desc: `Owners use dashboards to refine policies, reduce ${f.pain}, and improve ${f.outcomes}.` },
    { title: 'Scale across terms & campuses', desc: 'Reuse the same configuration each academic year and extend to new branches from one owner login.' },
  ];
}

function buildFaqs(f) {
  if (f.slug === 'attendance-management') {
    return [
      { q: 'What digital attendance options does EduPortal offer?', a: 'EduPortal offers four modes: manual office marking, teacher mobile app, AI-based face recognition machines (1-second scan), and QR code scanning on student/staff ID cards. All modes sync automatically to one attendance database inside your school ERP Pakistan.' },
      { q: 'How does AI face recognition attendance work?', a: 'Our AI-based face recognition attendance machine scans faces in about one second. Students and staff do not need manual registration on the device — the machine auto-syncs with EduPortal and identifies users from photos already stored in the software. Attendance records appear instantly in dashboards and parent apps.' },
      { q: 'Does face recognition work for both students and staff?', a: 'Yes. The same EduPortal face recognition system works for students and staff. Many large and small institutes across Pakistan use it at gates, office entrances, and staff rooms with reliable daily performance.' },
      { q: 'What is QR code attendance scanning?', a: 'Each student and staff ID card can include an auto-generated QR code from EduPortal\'s ID Cards Generation module. Staff scan the card with a phone or scanner — attendance marks instantly without typing names. It is ideal for schools wanting a lower-cost digital attendance solution.' },
      { q: 'Do ID cards connect to attendance automatically?', a: 'Yes. When you generate ID cards in EduPortal, QR codes are embedded automatically. Those cards work directly with QR attendance scanning — no separate setup or third-party tools required.' },
      { q: 'Are parents notified when attendance is marked digitally?', a: 'Yes. Absences, late arrivals, and daily summaries can trigger SMS, app push, or WhatsApp alerts so parents know the same day — reducing front-desk phone calls.' },
      { q: 'Can small schools afford digital attendance?', a: 'Absolutely. QR card scanning is a budget-friendly option for smaller institutes. Face recognition suits campuses wanting hands-free speed at the gate. EduPortal helps you choose the right mix for your size and budget.' },
      { q: 'Is manual attendance still available?', a: 'Yes. Teachers and office staff can still mark attendance manually by class, section, or period — alongside or instead of digital methods. Everything stays in one system.' },
      { q: 'How long does digital attendance setup take?', a: 'QR attendance can go live as soon as ID cards are printed — often within days. Face recognition machines sync with EduPortal during onboarding; our support team assists with photo sync and device configuration.' },
      { q: 'Can I see a demo of digital attendance?', a: 'Yes. Watch our digital attendance demo video on the attendance feature page, or book a live demo where we show face recognition and QR scanning with your school structure.' },
    ];
  }
  if (f.slug === 'id-cards-generation') {
    return [
      { q: 'What is EduPortal ID Cards Generation?', a: 'It is a module inside EduPortal school ERP that auto-designs and batch-prints student and staff ID cards with school branding, photos from profiles, and embedded QR codes for digital attendance scanning.' },
      { q: 'Are QR codes added automatically to ID cards?', a: 'Yes. Every card can include an auto-generated QR code linked to the student or staff record. No manual QR creation or external tools — print once and use for attendance immediately.' },
      { q: 'Can ID cards work with QR attendance?', a: 'Yes. QR codes on EduPortal-printed cards integrate directly with our QR attendance scanning system. Scan the card and attendance marks in the software within seconds.' },
      { q: 'Do photos come from student profiles?', a: 'Photos are pulled automatically from student and staff records in EduPortal — saving hours compared to collecting files separately for a designer.' },
      { q: 'Can I print cards in bulk?', a: 'Yes. Generate hundreds of cards as PDF in one batch during admission season. Reprint individual cards anytime when students lose or damage them.' },
      { q: 'Does it support staff ID cards too?', a: 'Yes. Staff cards use the same templates and QR workflow, so employees can check in with QR or face recognition alongside students.' },
      { q: 'Can I customize card layout and logo?', a: 'Yes. Add your school logo, colors, class info, blood group, emergency contacts, and other fields using flexible templates.' },
      { q: 'Is this separate from certificates?', a: 'ID Cards Generation focuses on daily-use identity cards with QR for attendance. Leaving certificates, character certificates, and awards are handled in our Certificates module — both share the same student data.' },
      { q: 'How fast can we print cards for new admissions?', a: 'Once students are entered in EduPortal, cards can be generated the same day — no waiting for external designers during peak admission weeks.' },
      { q: 'How do I see ID card generation in action?', a: 'Book a free demo and our team will show template design, QR embedding, bulk print, and how cards connect to digital attendance on your campus.' },
    ];
  }
  if (f.slug === 'certificates-id-cards') {
    return [
      { q: 'What certificates can EduPortal generate?', a: 'Leaving certificates, character certificates, achievement awards, bonafide letters, and other formal school documents — merged with student data and your principal signature block.' },
      { q: 'Are ID cards part of this module?', a: 'Daily student and staff ID cards with QR codes for attendance are in our dedicated ID Cards Generation module. This module focuses on formal certificates and graduation documents.' },
      { q: 'Can we batch print certificates?', a: 'Yes. Generate hundreds of leaving or character certificates as PDF in one run during graduation season — no manual mail-merge in Word.' },
      { q: 'Can we customize certificate layouts?', a: 'Yes. Use templates with your logo, Urdu/English text, signature blocks, and custom fields pulled from student records.' },
      { q: 'Does certificate data come from student profiles?', a: 'Yes. Name, class, dates, and other fields merge automatically from EduPortal SIS — reducing typos on official documents.' },
      { q: 'Is EduPortal suitable for Pakistani schools?', a: 'Yes. Formats and workflows match how schools in Pakistan issue leaving and character certificates for boards and transfers.' },
      { q: 'Can certificates integrate with other modules?', a: 'Yes. Data flows from student records and exam results — one source of truth across your ERP.' },
      { q: 'How long does setup take?', a: 'Templates can be configured during onboarding. Most schools print their first batch within the first week.' },
      { q: 'Is training included?', a: 'Yes. EduPortal support includes training for office staff who handle certificate printing seasons.' },
      { q: 'How do I see certificates in action?', a: 'Book a free demo and we will show template setup, batch print, and sample leaving certificates with your branding.' },
    ];
  }
  return [
    { q: `What is ${f.title} in EduPortal?`, a: `${f.title} in EduPortal is a module within our school management software Pakistan that ${f.tagline.toLowerCase()} It replaces ${f.pain} with ${f.outcomes}.` },
    { q: `Why is ${f.title} important for schools?`, a: `Schools lose time and money when ${f.pain}. ${f.title} gives administrators and teachers one reliable system so leaders can focus on education quality instead of admin firefighting.` },
    { q: `How does EduPortal's ${f.title} work?`, a: `Staff log in to EduPortal web or mobile apps, access the ${f.title.toLowerCase()} module, and complete daily tasks in guided screens. Data syncs instantly to reports, parent apps, and related modules like fees or attendance.` },
    { q: `Is EduPortal ${f.title.toLowerCase()} suitable for Pakistani schools?`, a: `Yes. EduPortal is used by 500+ schools in Pakistan and abroad. We support local fee cycles, board formats, bilingual communication, and WhatsApp-first parent engagement.` },
    { q: `Can ${f.title.toLowerCase()} integrate with other EduPortal modules?`, a: `Absolutely. ${f.title} shares data with student records, finance, communication, and mobile apps — so you never re-enter the same information twice.` },
    { q: `Do parents see ${f.title.toLowerCase()} updates?`, a: `Where relevant, yes. Parents view authorized updates in ParentsConnect — reducing office calls while keeping families informed in real time.` },
    { q: `How long does implementation take?`, a: `Most schools go live within days, not months. Our team provides onboarding, data import assistance, and training tailored to ${f.title.toLowerCase()} workflows.` },
    { q: `Is training included?`, a: `Yes. Dedicated customer support includes staff training sessions, video guides, and WhatsApp assistance during fee and exam seasons.` },
    { q: `Can we customize ${f.title.toLowerCase()} for our school?`, a: `EduPortal offers customization options — custom fields, reports, fee heads, and permissions — so the module fits your established processes.` },
    { q: `How do I see ${f.title} in action?`, a: `Book a free demo with our team. We walk your staff through real scenarios using your school structure so you can evaluate fit before committing.` },
  ];
}

function buildWhyNeed(f) {
  return [
    `Running a school in 2026 means managing more data than ever — yet many campuses still depend on ${f.pain}. That creates delays, errors, and frustrated parents who expect instant answers about their children.`,
    `${f.title} inside EduPortal school ERP Pakistan addresses these gaps directly. Instead of asking staff to juggle WhatsApp groups, registers, and disconnected Excel files, everything flows through one platform designed for educators.`,
    `When ${f.outcomes} become standard, principals gain confidence during inspections, owners see trustworthy numbers, and teachers spend less time on paperwork. The result is a campus that feels professional to families comparing schools in your city.`,
    `Schools that digitize early also recover hours every week — time redirected toward teaching quality, parent engagement, and growth. EduPortal clients report smoother fee seasons, calmer exam weeks, and fewer emergency meetings about missing records.`,
  ];
}

function buildAiSections(f) {
  if (f.slug === 'attendance-management') {
    return {
      whatIs: 'Attendance Management in EduPortal is a complete digital attendance system for Pakistani schools — combining manual marking, teacher mobile apps, AI-based face recognition machines, and QR code card scanning in one school ERP Pakistan platform.',
      whyImportant: 'Late registers and manual SMS lists cost schools hours every week and leave parents uninformed until it is too late. Digital attendance with 1-second face scans or QR card checks eliminates bottlenecks at the gate while giving owners real-time visibility into absenteeism trends.',
      howWorks: 'Choose your method: office staff mark manually, teachers use the mobile app, students scan faces at an AI machine that auto-syncs photos from EduPortal, or staff scan QR codes printed on ID cards from our ID Cards Generation module. Every check-in flows to the same database, triggers parent alerts, and feeds reports instantly.',
      pakistan: 'EduPortal digital attendance is deployed across big and small institutes in Pakistan — from affordable QR setups for modest campuses to AI face recognition at larger schools. Local SMS, WhatsApp alerts, and Urdu/English parent communication are built in.',
    };
  }
  if (f.slug === 'id-cards-generation') {
    return {
      whatIs: 'ID Cards Generation is EduPortal\'s module for designing and batch-printing student and staff identity cards with auto-generated QR codes — directly linked to digital attendance scanning in your school management software Pakistan.',
      whyImportant: 'Schools waste time and money on external designers, especially during admission season. When cards are disconnected from attendance, QR codes must be recreated manually. EduPortal generates branded, scannable cards from existing profile photos in minutes.',
      howWorks: 'Select a template, merge student or staff data from SIS, and export print-ready PDFs with embedded QR codes. Hand cards to students — gate staff scan with phone or scanner and attendance marks in EduPortal automatically. Reprint anytime from the same record.',
      pakistan: 'Ideal for Pakistani schools wanting professional ID cards without designer delays. QR attendance on these cards offers a lower-cost digital check-in path for smaller institutes while larger schools combine cards with AI face recognition at main entrances.',
    };
  }
  return {
    whatIs: `${f.title} is a core module of EduPortal — AI-ready school management software Pakistan used by hundreds of campuses. ${f.tagline} It centralizes workflows that schools previously handled with ${f.pain}, giving every stakeholder accurate information from a single login.`,
    whyImportant: `${f.title} matters because operational mistakes directly affect student experience and school reputation. When records are late or incomplete, parents lose trust, teachers duplicate effort, and owners fly blind on decisions. EduPortal helps schools professionalize back-office work so education stays the focus.`,
    howWorks: `EduPortal's ${f.title.toLowerCase()} connects staff dashboards, optional mobile apps, and parent-facing portals. Administrators configure rules once; daily users complete tasks in simple screens; reports and notifications flow automatically to the right people — principals, accountants, teachers, and families.`,
    pakistan: `For schools in Pakistan, ${f.title.toLowerCase()} must respect local realities: multi-lingual families, WhatsApp as the primary channel, tight fee cycles, and board exam pressure. EduPortal supports Urdu/English workflows, SIM and WhatsApp messaging, PKR billing, and formats familiar to regional education boards — without forcing you to adopt foreign software habits.`,
  };
}

function renderCustomSections(f) {
  if (f.slug === 'attendance-management' && f.demoVideo) {
    return `
    <section class="fd-section fd-section--alt fd-video-section" aria-labelledby="demo-video-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Watch Demo</span>
          <h2 id="demo-video-heading">See digital attendance in action</h2>
          <p>Watch how EduPortal digital attendance works — face recognition sync, instant software updates, and parent notifications.</p>
        </div>
        <div class="fd-video-wrap reveal-fd">
          <iframe
            src="https://www.youtube.com/embed/${esc(f.demoVideo)}?rel=0"
            title="EduPortal digital attendance demo — face recognition and QR scanning"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"></iframe>
        </div>
        <p class="fd-video-caption reveal-fd"><a href="https://www.youtube.com/watch?v=${esc(f.demoVideo)}&t=1s" target="_blank" rel="noopener noreferrer">Open demo on YouTube</a></p>
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
              <li><i data-lucide="check"></i> Cards designed &amp; printed from <a href="id-cards-generation.html">ID Cards Generation</a> module</li>
              <li><i data-lucide="check"></i> Scan with phone or handheld scanner — attendance marks in seconds</li>
              <li><i data-lucide="check"></i> Perfect for schools wanting <strong>affordable digital attendance</strong></li>
              <li><i data-lucide="check"></i> Same ERP sync, reports &amp; parent alerts as face recognition</li>
            </ul>
            <a href="id-cards-generation.html" class="btn btn-dark">Explore ID Cards Generation</a>
          </article>
        </div>
      </div>
    </section>`;
  }
  if (f.slug === 'id-cards-generation') {
    return `
    <section class="fd-section fd-section--alt" aria-labelledby="qr-link-heading">
      <div class="container">
        <div class="fd-qr-banner reveal-fd">
          <div class="fd-qr-banner-icon"><i data-lucide="qr-code"></i></div>
          <div>
            <h2 id="qr-link-heading">ID cards built for QR attendance</h2>
            <p>Every card can include an auto-generated QR code linked to the student or staff record. Scan at the gate with EduPortal <a href="attendance-management.html">digital attendance</a> — no separate QR tools or manual encoding. A cost-effective path for smaller institutes; pairs perfectly with face recognition at larger campuses.</p>
          </div>
        </div>
      </div>
    </section>`;
  }
  return '';
}

function renderList(items, fn) {
  return items.map(fn).join('\n');
}

function renderPage(f) {
  const benefits = buildBenefits(f);
  const breakdown = buildBreakdown(f);
  const steps = buildSteps(f);
  const faqs = buildFaqs(f);
  const whyNeed = buildWhyNeed(f);
  const ai = buildAiSections(f);
  const canonical = `https://eduportal.io/${f.slug}.html`;
  const relatedLinks = (f.related || [])
    .filter((slug) => FEATURE_MAP[slug])
    .map((slug) => {
      const r = FEATURE_MAP[slug];
      return `<a href="${r.slug}.html">${esc(r.title)}</a>`;
    })
    .join('\n');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="${esc(f.metaDescription)}">
  <meta name="keywords" content="${esc(f.keywords)}">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="${canonical}">
  <title>${esc(f.metaTitle)}</title>
  <meta property="og:type" content="website">
  <meta property="og:title" content="${esc(f.metaTitle)}">
  <meta property="og:description" content="${esc(f.metaDescription)}">
  <meta property="og:image" content="${esc(f.heroImage)}">
  <link rel="icon" href="assets/logo_icon.jpg" type="image/jpeg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/shared.css">
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
      <a href="index.html" class="logo">
        <img src="assets/logo_icon.jpg" alt="" class="logo-img" width="36" height="36">
        <span class="logo-text">EduPortal</span>
      </a>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="features.html" class="active">Features</a>
        <a href="pricing.html">Pricing</a>
        <a href="case-studies.html">Case Studies</a>
        <a href="blog.html">Blogs</a>
        <a href="contact.html">Contact</a>
      </nav>
      <div class="nav-actions">
        <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu">
        <i data-lucide="menu" style="width:24px;height:24px"></i>
      </button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
      <a href="index.html">Home</a>
      <a href="features.html" class="active">Features</a>
      <a href="pricing.html">Pricing</a>
      <a href="case-studies.html">Case Studies</a>
      <a href="blog.html">Blogs</a>
      <a href="contact.html">Contact</a>
      <button type="button" class="btn btn-dark js-open-modal">Book a Demo</button>
    </div>
  </header>

  <main>
    <div class="container fd-breadcrumb">
      <nav aria-label="Breadcrumb">
        <ol>
          <li><a href="index.html">Home</a></li>
          <li><a href="features.html">Features</a></li>
          <li><span aria-current="page">${esc(f.title)}</span></li>
        </ol>
      </nav>
    </div>

    <section class="fd-hero">
      <div class="container fd-hero-grid reveal-fd">
        <div>
          <span class="fd-hero-badge"><i data-lucide="${f.icon}" style="width:16px;height:16px"></i> School ERP Pakistan</span>
          <h1>${esc(f.title)} — School Management Software Pakistan</h1>
          <p class="fd-hero-lead">${esc(f.tagline)} EduPortal replaces ${esc(f.pain)} with ${esc(f.outcomes)} for modern schools.</p>
          <div class="fd-hero-actions">
            <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
            <a href="contact.html" class="btn btn-dark">Contact Sales</a>
          </div>
        </div>
        <div class="fd-hero-visual">
          <img src="${esc(f.heroImage)}" alt="${esc(f.heroAlt)}" width="640" height="400" loading="eager">
          <div class="fd-hero-stat">
            <span>500+ schools trust EduPortal</span>
            <span>Full school ERP Pakistan</span>
          </div>
        </div>
      </div>
    </section>

    ${renderCustomSections(f)}

    <section class="fd-ai-section" aria-labelledby="ai-overview">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Overview</span>
          <h2 id="ai-overview">Everything you need to know about ${esc(f.title)}</h2>
          <p>Clear answers for school owners, administrators, and AI search — optimized for school ERP Pakistan keywords.</p>
        </div>
        <div class="fd-ai-grid">
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="help-circle"></i> What is ${esc(f.title)}?</h2>
            <p>${esc(ai.whatIs)}</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="star"></i> Why is ${esc(f.title)} important for schools?</h2>
            <p>${esc(ai.whyImportant)}</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="route"></i> How does EduPortal's ${esc(f.title)} work?</h2>
            <p>${esc(ai.howWorks)}</p>
          </article>
          <article class="fd-ai-card reveal-fd">
            <h2><i data-lucide="map-pin"></i> Benefits for schools in Pakistan</h2>
            <p>${esc(ai.pakistan)}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="benefits-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Key Benefits</span>
          <h2 id="benefits-heading">Why schools choose EduPortal ${esc(f.title)}</h2>
          <p>Business outcomes that matter to principals, owners, and admin teams — not just software features.</p>
        </div>
        <div class="fd-benefits-grid">
          ${renderList(benefits, (b, i) => `
          <article class="fd-benefit-card reveal-fd">
            <div class="fd-benefit-icon"><i data-lucide="${BENEFIT_ICONS[i % BENEFIT_ICONS.length]}"></i></div>
            <h3>${esc(b.title)}</h3>
            <p>${esc(b.desc)}</p>
          </article>`)}
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="breakdown-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Feature Breakdown</span>
          <h2 id="breakdown-heading">Complete ${esc(f.title)} capabilities</h2>
          <p>Every tool your team needs — from daily tasks to leadership reports inside one school ERP Pakistan platform.</p>
        </div>
        <div class="fd-breakdown-grid">
          ${renderList(breakdown, (item) => `
          <article class="fd-breakdown-item reveal-fd">
            <div class="fd-breakdown-icon"><i data-lucide="${item.icon}"></i></div>
            <div>
              <h3>${esc(item.title)}</h3>
              <p>${esc(item.desc)}</p>
            </div>
          </article>`)}
        </div>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="how-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">How It Works</span>
          <h2 id="how-heading">How ${esc(f.title)} works in your school</h2>
          <p>A proven rollout path from setup to daily use — designed for busy Pakistani campuses.</p>
        </div>
        <div class="fd-steps">
          ${renderList(steps, (step) => `
          <article class="fd-step reveal-fd">
            <h3>${esc(step.title)}</h3>
            <p>${esc(step.desc)}</p>
          </article>`)}
        </div>
      </div>
    </section>

    <section class="fd-section" aria-labelledby="why-heading">
      <div class="container fd-prose reveal-fd">
        <h2 id="why-heading">Why schools need ${esc(f.title)}</h2>
        ${whyNeed.map((p) => `<p>${esc(p)}</p>`).join('\n        ')}
        <ul>
          <li>Eliminate ${esc(f.pain)} with structured digital workflows.</li>
          <li>Achieve ${esc(f.outcomes)} visible to owners and parents alike.</li>
          <li>Stay compliant with board inspections and franchise reporting standards.</li>
          <li>Free staff time during peak admission, fee, and exam seasons.</li>
        </ul>
      </div>
    </section>

    <section class="fd-section fd-section--alt" aria-labelledby="screens-heading">
      <div class="container">
        <div class="fd-section-header reveal-fd">
          <span class="section-label">Product Views</span>
          <h2 id="screens-heading">${esc(f.title)} screenshots &amp; mockups</h2>
          <p>Modern interfaces aligned with EduPortal branding — dashboard, mobile, and workflow views.</p>
        </div>
        <div class="fd-gallery reveal-fd">
          <figure class="fd-gallery-item">
            <img src="${esc(f.heroImage)}" alt="${esc(f.heroAlt)}" width="800" height="500" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <img src="assets/dashboard.png" alt="EduPortal ${esc(f.title)} admin dashboard" width="600" height="400" loading="lazy">
          </figure>
          <figure class="fd-gallery-item">
            <div class="fd-gallery-mock">
              <div class="fd-gallery-mock-bars"><span></span><span></span><span></span></div>
              <h4>${esc(f.title)} mobile view</h4>
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
          <h2 id="faq-heading">${esc(f.title)} — frequently asked questions</h2>
        </div>
        <div class="fd-faq-list">
          ${renderList(faqs, (item, i) => `
          <div class="fd-faq-item reveal-fd">
            <button type="button" class="fd-faq-q" aria-expanded="false" aria-controls="faq-a-${i}">
              <span>${esc(item.q)}</span>
              <i data-lucide="chevron-down"></i>
            </button>
            <div class="fd-faq-a" id="faq-a-${i}"><div class="fd-faq-a-inner">${esc(item.a)}</div></div>
          </div>`)}
        </div>
      </div>
    </section>

    <section class="fd-cta reveal-fd">
      <div class="container">
        <span class="section-label" style="color:var(--color-primary)">Get Started</span>
        <h2>Ready to modernize ${esc(f.title.toLowerCase())}?</h2>
        <p>Book a personalized demo and see how EduPortal school ERP Pakistan fits your campus — from admission to graduation.</p>
        <div class="fd-cta-btns">
          <button type="button" class="btn btn-primary js-open-modal">Book a Demo</button>
          <a href="contact.html" class="btn-outline-light">Contact Sales</a>
        </div>
      </div>
    </section>

    ${relatedLinks ? `
    <section class="fd-related">
      <div class="container reveal-fd">
        <div class="fd-section-header" style="margin-bottom:0">
          <span class="section-label">Related Features</span>
          <h2>Explore more EduPortal modules</h2>
        </div>
        <div class="fd-related-links">${relatedLinks}</div>
      </div>
    </section>` : ''}
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.html" class="logo">
            <img src="assets/logo_icon.jpg" alt="" class="logo-img" width="36" height="36">
            <span class="logo-text">EduPortal</span>
          </a>
          <p>Modern school management ERP trusted by educators worldwide. Simplify operations, engage parents, and grow smarter.</p>
        </div>
        <div class="footer-col">
          <h5>Product</h5>
          <ul>
            <li><a href="features.html">Features</a></li>
            <li><a href="pricing.html">Pricing</a></li>
            <li><a href="videos.html">Video Testimonials</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h5>Company</h5>
          <ul>
            <li><a href="blog.html">Blog</a></li>
            <li><a href="contact.html">Contact</a></li>
          </ul>
        </div>
        <div class="footer-col footer-contact">
          <h5>Contact</h5>
          <ul>
            <li><a href="mailto:info@eduportal.pk">info@eduportal.pk</a></li>
            <li><a href="https://wa.me/923091920336" target="_blank" rel="noopener noreferrer">+92 309 1920336</a></li>
            <li><a href="https://www.google.com/maps/search/?api=1&amp;query=Asad+Plaza+Gamtala+Chowk+Shakargarh" target="_blank" rel="noopener noreferrer">Shakargarh, Pakistan</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; 2026 EduPortal. All rights reserved.</span>
      </div>
    </div>
  </footer>

  <div class="modal-overlay" id="getStartedModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal">
      <h2 id="modalTitle">Get Started with EduPortal</h2>
      <p class="modal-desc">Share a few details about your institute and we'll set up a quick, personalized demo for you.</p>
      <form id="getStartedForm" novalidate>
        <div class="form-row">
          <div class="form-group form-group--grow">
            <label for="instituteName">Institute Name</label>
            <input type="text" id="instituteName" name="instituteName" placeholder="Institute name" required>
          </div>
          <div class="form-group form-group--side">
            <label for="studentCount">Strength</label>
            <input type="text" id="studentCount" name="studentCount" placeholder="e.g. 500" inputmode="numeric">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group form-group--grow">
            <label for="fullName">Contact Person Name</label>
            <input type="text" id="fullName" name="fullName" placeholder="Full name" required>
          </div>
          <div class="form-group form-group--side">
            <label for="designation">Designation / Role</label>
            <input type="text" id="designation" name="designation" placeholder="Role" required>
          </div>
        </div>
        <div class="form-group">
          <label for="whatsapp">WhatsApp Number</label>
          <div class="phone-input">
            <select id="countryCode" name="country_code" class="phone-country-select" aria-label="Country code"></select>
            <input type="tel" id="whatsapp" name="whatsapp" placeholder="300 1234567" inputmode="tel" autocomplete="tel-national" required>
          </div>
          <input type="hidden" id="whatsappFull" name="whatsapp_full" value="">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" id="modalCancel">Cancel</button>
          <button type="submit" class="btn-modal-submit">Book now</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>`;
}

// Generate all pages
let count = 0;
for (const f of FEATURES) {
  const outPath = join(ROOT, `${f.slug}.html`);
  writeFileSync(outPath, renderPage(f), 'utf8');
  count++;
  console.log('Generated:', f.slug + '.html');
}
console.log(`\nDone — ${count} feature pages created.`);
spawnSync(process.execPath, [join(__dirname, 'inject-schemas.mjs')], { cwd: ROOT, stdio: 'inherit' });

// Export slug map for features.html update
export const SLUG_MAP = Object.fromEntries(
  FEATURES.map((f) => [f.title.toLowerCase(), f.slug])
);
