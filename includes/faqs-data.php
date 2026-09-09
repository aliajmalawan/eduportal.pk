<?php
/**
 * FAQ content — single source of truth for both faqs.php and the
 * homepage FAQ preview. Server-rendered so crawlers see real text
 * without JavaScript; JS only adds accordion/tab/search interactivity.
 */

function ep_faq_categories(): array
{
    return array (
  0 =>
  array (
    'id' => 'all',
    'label' => 'All Questions',
    'icon' => 'layout-grid',
  ),
  1 =>
  array (
    'id' => 'general',
    'label' => 'School ERP',
    'icon' => 'school',
  ),
  2 =>
  array (
    'id' => 'fees',
    'label' => 'Fee Management',
    'icon' => 'wallet',
  ),
  3 =>
  array (
    'id' => 'attendance',
    'label' => 'Attendance',
    'icon' => 'clipboard-check',
  ),
  4 =>
  array (
    'id' => 'mobile',
    'label' => 'Mobile Apps',
    'icon' => 'smartphone',
  ),
  5 =>
  array (
    'id' => 'whatsapp',
    'label' => 'WhatsApp',
    'icon' => 'message-circle',
  ),
  6 =>
  array (
    'id' => 'exams',
    'label' => 'Results & Exams',
    'icon' => 'award',
  ),
  7 =>
  array (
    'id' => 'security',
    'label' => 'Data Security',
    'icon' => 'shield-check',
  ),
  8 =>
  array (
    'id' => 'pricing',
    'label' => 'Pricing',
    'icon' => 'tag',
  ),
  9 =>
  array (
    'id' => 'cloud',
    'label' => 'Cloud Hosting',
    'icon' => 'cloud',
  ),
  10 =>
  array (
    'id' => 'parent',
    'label' => 'Parent Portal',
    'icon' => 'users',
  ),
  11 =>
  array (
    'id' => 'teacher',
    'label' => 'Teacher Portal',
    'icon' => 'graduation-cap',
  ),
);
}

function ep_faqs(): array
{
    $faqs = array (
  0 =>
  array (
    'id' => 'best-sms-pakistan',
    'category' => 'general',
    'featured' => true,
    'q' => 'What is the best school management software in Pakistan?',
    'a' => 'The best school management software in Pakistan combines fee collection, attendance, exams, parent communication, and finance in one platform — without forcing schools to buy five separate tools. EduPortal is built specifically for Pakistani campuses: Urdu/English workflows, WhatsApp and SMS alerts, PKR billing, board-friendly report formats, and mobile apps parents actually use. With {{total_clients}} schools onboard, EduPortal ranks among the most trusted school ERP options for owners who want proven software, local support in Shakargarh and nationwide remote help, and modules that scale from a single branch to multi-campus groups. When evaluating options, look for same-day parent notifications, digital attendance including face recognition and QR cards, automated fee vouchers, and a dedicated support team that understands admission and exam seasons — not a generic helpdesk.',
  ),
  1 =>
  array (
    'id' => 'what-is-eduportal',
    'category' => 'general',
    'q' => 'What is EduPortal school ERP?',
    'a' => 'EduPortal is an all-in-one school management ERP (Enterprise Resource Planning) system that digitizes daily operations for schools, colleges, and academies. It replaces paper registers, Excel fee sheets, and WhatsApp homework chains with a single cloud platform covering student records, fee management, digital attendance, exam results, parent and teacher mobile apps, accounting, payroll, library, transport, and WhatsApp messaging. School owners get real-time dashboards; office staff save hours every week; teachers mark attendance and enter marks from mobile; parents view fees and results instantly. EduPortal is developed for the Pakistani market with affordable monthly plans starting around Rs. 4,000 per month, making professional school software accessible to institutes of every size.',
  ),
  2 =>
  array (
    'id' => 'who-uses-eduportal',
    'category' => 'general',
    'q' => 'Which types of schools use EduPortal?',
    'a' => 'EduPortal serves private schools, grammar schools, colleges, academies, Montessori chains, and multi-branch groups across Pakistan and internationally. Campuses range from 200 students in a single building to large institutions with thousands of learners and multiple franchises. Day schools use EduPortal for fees and parent apps; boarding schools add hostel modules; schools with transport fleets link route fees to billing. Whether you run Allied Science-style groups or a neighborhood academy, EduPortal adapts through configurable fee heads, custom reports, and optional modules so you pay only for what you need.',
  ),
  3 =>
  array (
    'id' => 'erp-vs-excel',
    'category' => 'general',
    'q' => 'Why switch from Excel to school ERP software?',
    'a' => 'Excel spreadsheets break when multiple staff edit different copies, formulas fail silently, and there is no audit trail when a fee or mark is wrong. School ERP software like EduPortal keeps one live database: when the accountant records a payment, the parent app updates; when attendance is marked, defaulter reports refresh automatically. Owners stop waiting until month-end for accurate numbers. ERP also enables SMS and WhatsApp alerts, mobile apps, role-based access, and backups — things Excel cannot provide. Most EduPortal schools recover software cost within one fee cycle through faster collections and reduced admin labor.',
  ),
  4 =>
  array (
    'id' => 'implementation-time',
    'category' => 'general',
    'q' => 'How long does EduPortal take to implement?',
    'a' => 'Most schools go live within a few days to two weeks depending on data volume and modules selected. EduPortal provides onboarding calls, spreadsheet import assistance, and staff training so you are not left alone during admission or fee season. Basic modules — student records, fees, and attendance — can often run in the first week. Larger migrations from legacy software or multi-campus rollouts may take longer, but our team works in phases so critical workflows like fee vouchers and parent apps are prioritized first. Schools typically start with core modules and add finance, library, or transport as staff become comfortable.',
  ),
  5 =>
  array (
    'id' => 'training-support',
    'category' => 'general',
    'q' => 'Does EduPortal provide training for school staff?',
    'a' => 'Yes. EduPortal includes dedicated customer support with onboarding sessions, WhatsApp assistance, video guides, and remote screen-sharing during peak periods like board exams and fee collection weeks. We train office administrators, accountants, teachers, and IT coordinators on their specific roles — not one generic lecture. Because EduPortal is designed for non-technical school staff, most users become productive after a single hands-on session. Ongoing support continues year-round so new hires joining mid-session can be brought up to speed quickly without disrupting daily operations.',
  ),
  6 =>
  array (
    'id' => 'fee-cost',
    'category' => 'fees',
    'featured' => true,
    'q' => 'How much does school ERP software cost in Pakistan?',
    'a' => 'School ERP pricing in Pakistan varies by features and student count. EduPortal offers transparent monthly plans — Silver, Gold, and Diamond packages — starting from approximately Rs. 4,000 per month with yearly billing saving around 10%. Unlike enterprise systems costing millions upfront, EduPortal is subscription-based with no heavy hardware investment. Fee management, attendance, parent apps, and core reports are included in standard tiers; advanced finance, multi-campus, or customization may sit in higher plans. Request a demo with your student strength and module list for an exact quote tailored to your campus rather than a one-size-fits-all price list.',
  ),
  7 =>
  array (
    'id' => 'fee-vouchers',
    'category' => 'fees',
    'q' => 'Can EduPortal generate fee vouchers automatically?',
    'a' => 'Yes. EduPortal automates fee voucher generation per class, section, or individual student with your defined fee heads — tuition, transport, exam, lab, and custom charges. Generate hundreds of printable or digital vouchers in one batch before the due date, apply sibling discounts or scholarships automatically, and track partial payments with running balances. Defaulter lists highlight who owes what without manual sorting. When a payment is recorded, receipts print instantly and parent apps show updated dues. This removes the front-desk bottleneck every month when families queue for paper vouchers.',
  ),
  8 =>
  array (
    'id' => 'fee-reminders',
    'category' => 'fees',
    'q' => 'How does EduPortal send fee reminders to parents?',
    'a' => 'EduPortal can notify parents about fee dues through the ParentsConnect mobile app push notifications, bulk SMS via your school SIM, and WhatsApp template messages. You filter by class, defaulter status, or amount owed, then launch campaigns with delivery logs for accountability. Reminders can go out before due dates, on due dates, and for overdue accounts — reducing awkward phone calls from the office. Because fee data lives in the same ERP, messages always show accurate amounts with no manual copy-paste from spreadsheets.',
  ),
  9 =>
  array (
    'id' => 'fee-concessions',
    'category' => 'fees',
    'q' => 'Does EduPortal support fee concessions and scholarships?',
    'a' => 'Absolutely. Define concession rules per student, sibling group, staff child, or merit category. Percentage or fixed-amount discounts apply automatically when vouchers generate, so clerks do not forget manual adjustments. Scholarship tracking shows how much was waived for auditor and owner review. Historical concession records stay linked to each student profile for transparency when parents inquire. Combined with defaulter reports and partial payment support, schools maintain fair policies while keeping collections organized across hundreds of accounts.',
  ),
  10 =>
  array (
    'id' => 'fee-accounting-link',
    'category' => 'fees',
    'q' => 'Does fee collection sync with school accounts?',
    'a' => 'Yes. EduPortal links fee income to the accounting module so accountants see collections in the general ledger without re-entering transactions. Daily collection summaries, cash versus bank splits, and month-end reconciliation reports help owners trust their numbers. When finance staff record expenses in the same ERP, profit and loss dashboards compare fee income against operating costs in real time — eliminating the gap between what the fee clerk collected and what the owner thought was deposited.',
  ),
  11 =>
  array (
    'id' => 'face-attendance',
    'category' => 'attendance',
    'q' => 'Does EduPortal offer face recognition attendance?',
    'a' => 'Yes. EduPortal supports AI-based face recognition attendance machines with approximately one-second scanning at school gates. Students and staff do not need manual registration on the device — the machine auto-syncs with EduPortal and identifies users from photos already stored in software profiles. Attendance records appear instantly in dashboards and parent apps. Many large and small institutes across Pakistan use this for hands-free morning rush at entrances while smaller schools may pair it with affordable QR card scanning instead.',
  ),
  12 =>
  array (
    'id' => 'qr-attendance',
    'category' => 'attendance',
    'q' => 'What is QR code attendance in EduPortal?',
    'a' => 'QR code attendance lets staff scan student or employee ID cards printed from EduPortal\'s ID Cards Generation module. Each card embeds an auto-generated QR linked to that person\'s record — scan with a phone or handheld reader and attendance marks in seconds. This is a lower-cost digital option ideal for smaller institutes that want modern check-in without expensive biometric hardware. QR attendance syncs to the same reports, defaulter lists, and parent alerts as face recognition and manual marking, so schools can mix methods on one platform.',
  ),
  13 =>
  array (
    'id' => 'parent-attendance-alert',
    'category' => 'attendance',
    'q' => 'Are parents notified when a child is absent?',
    'a' => 'Yes. When attendance is marked absent or late — manually, via teacher app, face recognition, or QR scan — EduPortal can trigger SMS, app push, or WhatsApp alerts the same day. Parents no longer hear about absences days later at parent-teacher meetings. Office staff configure which classes and times trigger notifications. This transparency reduces front-desk calls asking "Was my child present?" and helps families address truancy early when patterns emerge in monthly attendance summaries.',
  ),
  14 =>
  array (
    'id' => 'manual-attendance',
    'category' => 'attendance',
    'q' => 'Can teachers still mark attendance manually?',
    'a' => 'Yes. EduPortal supports manual attendance by class, section, or period from the office or teacher mobile app alongside digital methods. Many schools use face recognition at the main gate plus manual period attendance in classrooms for subject-wise tracking. All modes write to one database, so reports stay unified. Teachers see class lists on mobile, tap present or absent, and sync instantly — no paper register at the end of the day.',
  ),
  15 =>
  array (
    'id' => 'attendance-reports',
    'category' => 'attendance',
    'q' => 'What attendance reports does EduPortal provide?',
    'a' => 'EduPortal generates daily registers, monthly summaries, chronic absentee lists, late arrival logs, board exam attendance sheets, and class-wise percentage analytics. Owners filter by date range, section, or student to spot trends before they become crises. Export to PDF or Excel for inspections, franchisor reviews, or parent conferences. Because digital and manual marks live in one system, coordinators never merge separate registers before exam week.',
  ),
  16 =>
  array (
    'id' => 'parent-app',
    'category' => 'mobile',
    'featured' => true,
    'q' => 'Does EduPortal provide a parent mobile app?',
    'a' => 'Yes. EduPortal offers ParentsConnect — a mobile app for Android (and web access) where parents view attendance, fee dues, payment receipts, exam results, homework, transport routes, and school notices. Push notifications alert families when new results publish or fees are overdue. Multi-child support lets one login cover all siblings. The app reduces phone traffic to the office because parents self-serve information they previously had to call for. It is included in standard EduPortal plans and connects live to your ERP data — not a separate outdated portal.',
  ),
  17 =>
  array (
    'id' => 'teacher-app',
    'category' => 'mobile',
    'q' => 'Is there a teacher mobile app for EduPortal?',
    'a' => 'Yes. EduPortal\'s teacher app lets staff mark attendance, enter marks, post homework, view timetables, and access class lists from their phone — without waiting for a free office computer after class. Data syncs instantly to the main ERP and parent apps. Secure login ensures teachers only see their assigned classes. Schools report faster same-day data entry and fewer end-of-term mark entry bottlenecks when teachers update records continuously rather than in one stressful week.',
  ),
  18 =>
  array (
    'id' => 'download-app',
    'category' => 'mobile',
    'q' => 'Where can I download the EduPortal mobile app?',
    'a' => 'Parents and teachers can download EduPortal from Google Play Store — search for EduPortal or ParentsConnect linked from your school\'s welcome message. After install, parents log in with credentials provided by the school office, usually tied to registered mobile numbers. Schools receive onboarding materials including parent login instructions and posters for notice boards. If a family needs help, office staff can reset access from the admin panel without IT tickets.',
  ),
  19 =>
  array (
    'id' => 'offline-mobile',
    'category' => 'mobile',
    'q' => 'Does the EduPortal app work without internet?',
    'a' => 'Core viewing of previously synced data may be available briefly offline depending on device, but live fee balances, new results, and attendance updates require internet connectivity. Teachers marking attendance in areas with weak signal can often queue actions that sync when connection returns — ask our support team about current offline behavior for your app version. For daily operations, a basic mobile data connection or school Wi-Fi is recommended for staff apps; parents typically use home mobile data.',
  ),
  20 =>
  array (
    'id' => 'exec-app',
    'category' => 'mobile',
    'q' => 'Can school owners monitor the campus from mobile?',
    'a' => 'Yes. Owner and principal dashboards accessible via web and mobile-friendly views show fee collection totals, attendance percentages, defaulter counts, and enrollment trends without visiting the office. Multi-campus groups see consolidated figures plus per-branch breakdowns. While dedicated owner apps vary by plan, the responsive admin interface lets leaders check morning attendance and daily collection from anywhere — critical when managing multiple branches or traveling between campuses.',
  ),
  21 =>
  array (
    'id' => 'whatsapp-notifications',
    'category' => 'whatsapp',
    'featured' => true,
    'q' => 'Can EduPortal send WhatsApp notifications to parents?',
    'a' => 'Yes. EduPortal includes WhatsApp messaging integration so schools send fee reminders, holiday notices, exam schedules, event invitations, and result announcements on the channel parents already use daily. Template-based messages merge student names, amounts, and dates from ERP data — no manual typing per parent. Delivery status and campaign history provide accountability. WhatsApp complements SMS and app push; schools choose the mix that fits budget and parent preference. Note: WhatsApp business API costs are separate from EduPortal subscription, giving you control over messaging spend.',
  ),
  22 =>
  array (
    'id' => 'whatsapp-fee',
    'category' => 'whatsapp',
    'q' => 'Can I send fee dues on WhatsApp through EduPortal?',
    'a' => 'Yes. Filter defaulters or entire classes and launch WhatsApp fee reminder campaigns with accurate amounts pulled from the fee module. Templates keep messaging professional and consistent with school branding. Office staff save hours compared to personal WhatsApp groups where messages get lost. Combined with SMS and app notifications, schools typically see faster collections within the first month of automated reminders.',
  ),
  23 =>
  array (
    'id' => 'whatsapp-results',
    'category' => 'whatsapp',
    'q' => 'Can exam results be sent on WhatsApp?',
    'a' => 'Schools can notify parents via WhatsApp when results publish to the portal, with links or summaries depending on your template setup and privacy policy. Full mark sheets usually remain in the secure parent app or PDF download rather than open chat, protecting student data. Coordinators announce result day timing, re-evaluation deadlines, and parent meeting schedules through the same WhatsApp module used for fees — keeping communication unified.',
  ),
  24 =>
  array (
    'id' => 'whatsapp-vs-sms',
    'category' => 'whatsapp',
    'q' => 'Should my school use WhatsApp or SMS with EduPortal?',
    'a' => 'Many Pakistani schools use both: SMS for urgent universal reach including parents without smartphones, and WhatsApp for rich messages and higher read rates on daily notices. EduPortal supports SIM-based bulk SMS and WhatsApp templates from one platform with shared contact lists from student records. SMS costs depend on your carrier bundle; WhatsApp costs depend on Meta business pricing. Our team helps you design a communication plan that avoids duplicate spam while maximizing timely delivery during fee season and emergencies.',
  ),
  25 =>
  array (
    'id' => 'whatsapp-setup',
    'category' => 'whatsapp',
    'q' => 'How do we set up WhatsApp messaging in EduPortal?',
    'a' => 'During onboarding, EduPortal support guides you through connecting your WhatsApp Business account or approved sender, importing approved templates, and testing messages to staff numbers first. Contact lists sync from student guardian records so wrong numbers are minimized. Training covers how office staff launch campaigns safely without violating WhatsApp policy. Most schools complete setup within a few working days once business verification documents are ready.',
  ),
  26 =>
  array (
    'id' => 'exam-results',
    'category' => 'exams',
    'q' => 'How does EduPortal manage exam results and report cards?',
    'a' => 'EduPortal lets you configure exam types, subjects, grading scales, and marks entry by teachers or coordinators. After verification, publish results securely to the parent portal and app; print branded report cards with school logos for folders and board compliance. Position holders, class averages, and subject analytics help academic heads improve performance. Re-evaluation workflows track requests without losing original marks. Everything links to student profiles so historical results stay accessible year over year.',
  ),
  27 =>
  array (
    'id' => 'report-cards',
    'category' => 'exams',
    'q' => 'Can EduPortal print custom report cards?',
    'a' => 'Yes. Design report card templates with your layout, principal signature block, grading keys, and Urdu or English labels. Batch generate PDFs for entire classes before result day — eliminating mail-merge errors in Word. Templates reuse each term with updated marks pulled automatically from the exam module. Schools align formats with board requirements and internal branding for parent trust.',
  ),
  28 =>
  array (
    'id' => 'marks-entry',
    'category' => 'exams',
    'q' => 'Can teachers enter marks from mobile?',
    'a' => 'Yes. Teachers enter subject marks through the EduPortal teacher app or web portal for their assigned classes. Coordinators lock entries after deadline to prevent changes, then publish to parents when ready. This distributed entry model spreads workload across staff instead of one harried exam secretary typing all night before result announcement.',
  ),
  29 =>
  array (
    'id' => 'exam-datasheets',
    'category' => 'exams',
    'q' => 'Does EduPortal generate exam datasheets and roll number slips?',
    'a' => 'Yes. Export seating plans, roll number slips, and room-wise datasheets formatted for board exams and internal assessments in one click. Student photos and registration numbers merge from SIS records for accurate exam-day checklists. Coordinators print before exam week without retyping lists from multiple spreadsheets.',
  ),
  30 =>
  array (
    'id' => 'online-results',
    'category' => 'exams',
    'q' => 'Are results visible to parents online immediately?',
    'a' => 'Once administrators publish, parents see results instantly in ParentsConnect with subject-wise marks and grades. Push notifications alert them to log in — reducing crowds at the office on result day. Schools control publish timing so nothing goes live before review. Historical results remain in the app for reference during admissions or transfers.',
  ),
  31 =>
  array (
    'id' => 'data-security',
    'category' => 'security',
    'q' => 'How secure is school data in EduPortal?',
    'a' => 'EduPortal protects school data with role-based access control, encrypted connections (HTTPS), secure authentication, and regular backups. Teachers see only their classes; accountants access finance; parents see only their children. Admin actions are logged for accountability. We host on reliable cloud infrastructure with monitoring and disaster recovery practices appropriate for education data including student profiles, fee records, and staff payroll. Schools should still use strong passwords and deactivate accounts when staff leave — policies our onboarding covers.',
  ),
  32 =>
  array (
    'id' => 'who-access-data',
    'category' => 'security',
    'q' => 'Who can access student information in EduPortal?',
    'a' => 'Access is permission-based. Principals and owners configure roles so office staff, teachers, accountants, librarians, and wardens each see only modules required for their job. Parents access solely their linked children via the app. EduPortal staff do not sell or share your data; support access for troubleshooting occurs only with your authorization during support tickets. This model meets typical expectations for student privacy in private schools and colleges.',
  ),
  33 =>
  array (
    'id' => 'data-backup',
    'category' => 'security',
    'q' => 'Does EduPortal backup school data automatically?',
    'a' => 'Yes. Cloud-hosted EduPortal performs automated backups so your records survive local hardware failures, fire, or theft at the school office. Unlike paper registers or a single PC Excel file, data persists in redundant data center storage. Schools can also export reports and archives periodically for local auditor copies. Ask support about backup frequency and retention for your deployment tier during demo calls.',
  ),
  34 =>
  array (
    'id' => 'password-policy',
    'category' => 'security',
    'q' => 'Can we control staff passwords and login access?',
    'a' => 'Administrators create, reset, and deactivate user accounts from the control panel. When a teacher resigns, disable their login immediately — they lose access to marks and attendance on mobile. Optional password policies and unique usernames reduce shared-credential risk. Multi-branch setups can isolate campus data while owners retain group-wide oversight through consolidated roles.',
  ),
  35 =>
  array (
    'id' => 'privacy-compliance',
    'category' => 'security',
    'q' => 'Is EduPortal suitable for protecting student privacy?',
    'a' => 'EduPortal is designed so sensitive fields — contact numbers, medical notes, fee balances — are visible only to authorized roles and parents. Communication modules send to registered guardians rather than public groups. Schools should publish their privacy policy to parents explaining app usage; EduPortal provides tools to minimize unnecessary data exposure compared to open WhatsApp groups or paper files on reception desks.',
  ),
  36 =>
  array (
    'id' => 'pricing-plans',
    'category' => 'pricing',
    'q' => 'What pricing plans does EduPortal offer?',
    'a' => 'EduPortal offers tiered plans — typically Silver, Gold, and Diamond — scaled by modules and campus needs. Core features like student records, fees, attendance, and parent apps sit in entry tiers; advanced finance, multi-campus, HR, and customization align with higher packages. Monthly and yearly billing are available with roughly 10% savings on annual payment. Visit the pricing page or book a demo with your student count for a recommendation rather than overbuying modules you will not use in year one.',
  ),
  37 =>
  array (
    'id' => 'hidden-fees',
    'category' => 'pricing',
    'q' => 'Are there hidden fees with EduPortal?',
    'a' => 'EduPortal subscription covers software access, updates, and standard support. Separate costs may include SMS airtime from your SIM carrier, WhatsApp Business messaging fees paid to Meta, face recognition hardware if purchased from vendors, and optional premium customization projects. We explain these during sales calls so owners budget honestly — no surprise invoices for basic login or report viewing. Yearly plan clarity helps boards approve ERP spend confidently.',
  ),
  38 =>
  array (
    'id' => 'yearly-discount',
    'category' => 'pricing',
    'q' => 'Is there a discount for yearly billing?',
    'a' => 'Yes. EduPortal offers approximately 10% savings when schools pay annually instead of monthly — equivalent to slightly more than one free month per year. Yearly billing suits established schools confident in long-term ERP use; monthly suits newer campuses testing digital transformation. Finance teams can compare cash-flow impact on the pricing page toggle or with our sales team using your exact plan quote.',
  ),
  39 =>
  array (
    'id' => 'multi-campus-pricing',
    'category' => 'pricing',
    'q' => 'How is multi-campus pricing calculated?',
    'a' => 'Multi-branch groups receive consolidated owner dashboards with pricing based on total enrollment, number of campuses, and modules shared across branches. Rather than buying duplicate systems per campus, one EduPortal contract covers the group with per-branch data isolation. Contact sales with your branch list and combined student strength for a franchise-friendly quote that scales as you open new locations.',
  ),
  40 =>
  array (
    'id' => 'trial-demo',
    'category' => 'pricing',
    'q' => 'Can I try EduPortal before purchasing?',
    'a' => 'Book a free personalized demo where we walk your team through modules using scenarios matching your school — fee vouchers, attendance, parent app, and reports. Live demo beats generic trial accounts because you see real workflows with your class structure questions answered immediately. Many owners invite accountants and principals to the same call for unified buy-in before signing.',
  ),
  41 =>
  array (
    'id' => 'cloud-based',
    'category' => 'cloud',
    'featured' => true,
    'q' => 'Is EduPortal cloud based?',
    'a' => 'Yes. EduPortal is cloud-based school management software — access from any browser without installing servers at your campus. Updates roll out centrally so you always run the latest features and security patches without IT staff downloading installers. Data syncs in real time between office, teacher phones, and parent apps. Cloud hosting means floods or PC theft at school do not destroy your only copy of fee and student records. Login securely from home when owners travel or during emergencies like campus closures.',
  ),
  42 =>
  array (
    'id' => 'internet-required',
    'category' => 'cloud',
    'q' => 'Do I need internet at school to use EduPortal?',
    'a' => 'Yes, a stable internet connection at the office and for staff using mobile apps is recommended for live sync. Many schools use standard PTCL, fiber, or 4G backup routers — EduPortal is optimized for typical Pakistani connectivity, not gigabit enterprise lines. Brief outages may delay sync, but cloud backups protect data once connection returns. For attendance gates, face recognition machines sync when online; QR scanning often works with staff phone data.',
  ),
  43 =>
  array (
    'id' => 'cloud-vs-onpremise',
    'category' => 'cloud',
    'q' => 'Cloud ERP vs on-premise server — which is better for schools?',
    'a' => 'On-premise servers burden schools with hardware cost, power backups, Windows licenses, and an IT person when something fails at 7 AM on fee day. Cloud ERP like EduPortal removes server rooms — you pay subscription and focus on education. Updates and backups are our responsibility. For Pakistani private schools without dedicated IT departments, cloud is almost always lower total cost and faster to deploy than buying a server that ages in three years.',
  ),
  44 =>
  array (
    'id' => 'data-location',
    'category' => 'cloud',
    'q' => 'Where is EduPortal school data stored?',
    'a' => 'EduPortal uses secure cloud infrastructure with industry-standard hosting partners. Data is transmitted over encrypted HTTPS and stored in protected data centers with backup redundancy. Specific region details are available on request for owners with contractual requirements. The key benefit for schools is professional-grade protection without buying your own server room or UPS batteries.',
  ),
  45 =>
  array (
    'id' => 'uptime',
    'category' => 'cloud',
    'q' => 'What happens if EduPortal servers are down?',
    'a' => 'We monitor platform availability and work to resolve outages quickly with status communication to affected schools. No cloud provider promises 100% uptime, but centralized hosting beats a single PC under the accountant\'s desk that fails during result week. Schools should keep exported PDF backups of critical board submissions locally as good practice. Our support WhatsApp line assists during incidents so you know whether an issue is local internet or platform-wide.',
  ),
  46 =>
  array (
    'id' => 'parent-portal-features',
    'category' => 'parent',
    'q' => 'What can parents see in the EduPortal parent portal?',
    'a' => 'Parents view attendance history, fee dues and paid receipts, exam results, homework notices, school calendar events, transport route info where enabled, and official announcements. Push alerts notify when something new publishes. Multi-child families switch between profiles in one login. This self-service transparency builds trust and cuts "please check my child\'s fee" calls to the reception — especially during busy mornings.',
  ),
  47 =>
  array (
    'id' => 'parent-login',
    'category' => 'parent',
    'q' => 'How do parents get login credentials?',
    'a' => 'School office generates parent credentials from EduPortal linked to registered mobile numbers and guardian records entered during admission. Welcome SMS or WhatsApp messages can include download links and login steps. Office staff reset passwords when parents forget them — no ticket to a distant call center. Accurate guardian phone numbers in SIS are essential for smooth rollout.',
  ),
  48 =>
  array (
    'id' => 'parent-fee-pay',
    'category' => 'parent',
    'q' => 'Can parents pay fees through the EduPortal app?',
    'a' => 'Fee payment integration depends on your school\'s chosen payment gateways and policies — many campuses still record cash or bank deposits in ERP while parents view balances digitally. Ask our team about current online payment options for your plan. Even without in-app payment, showing exact dues and downloadable receipts reduces disputes and speeds up counter collections when parents arrive prepared.',
  ),
  49 =>
  array (
    'id' => 'parent-communication',
    'category' => 'parent',
    'q' => 'How does EduPortal improve parent communication?',
    'a' => 'Instead of scattered WhatsApp groups run by individual teachers, official notices flow from school administration through app, SMS, and WhatsApp modules with logs. Parents receive consistent messaging about holidays, exams, and fees. Teachers post homework in structured channels. Result day chaos eases when marks appear in app simultaneously. Schools report stronger parent satisfaction scores and fewer conflicts from missed announcements.',
  ),
  50 =>
  array (
    'id' => 'parent-language',
    'category' => 'parent',
    'q' => 'Is the parent app available in Urdu?',
    'a' => 'EduPortal supports bilingual workflows common in Pakistani schools — English and Urdu labels appear in many modules and printed documents. App interface language options continue expanding; confirm current Urdu support level during your demo if parent literacy in your community favors Urdu-first navigation. Notices can often be composed in either language before sending.',
  ),
  51 =>
  array (
    'id' => 'teacher-portal',
    'category' => 'teacher',
    'q' => 'What is the EduPortal teacher portal?',
    'a' => 'The teacher portal — web and mobile — gives staff access to class lists, attendance marking, marks entry, homework posting, timetable views, and lesson plan logging depending on modules enabled. Teachers work only within assigned classes, protecting other sections\' data. Same-day updates mean coordinators see progress without collecting paper registers every Friday.',
  ),
  52 =>
  array (
    'id' => 'teacher-attendance-app',
    'category' => 'teacher',
    'q' => 'Can teachers mark attendance from their phone?',
    'a' => 'Yes. Open the teacher app, select class and date, tap present or absent for each student, and submit — sync completes in seconds. Combine with gate-level face recognition for arrival tracking plus classroom period attendance for subject-wise records. Substitute teachers with temporary login can mark for absent colleagues when admin grants access.',
  ),
  53 =>
  array (
    'id' => 'teacher-marks',
    'category' => 'teacher',
    'q' => 'How do teachers enter exam marks in EduPortal?',
    'a' => 'Teachers receive marks entry screens per subject and section with student lists pre-loaded from SIS. Enter scores, save drafts, and submit when coordinator deadline arrives. Locked entries prevent accidental changes after verification. This divides exam season workload across the teaching staff instead of centralizing typing in one office computer.',
  ),
  54 =>
  array (
    'id' => 'teacher-homework',
    'category' => 'teacher',
    'q' => 'Can teachers assign homework through EduPortal?',
    'a' => 'Yes. Teachers post homework, attachments, and due dates visible in the parent app — replacing screenshot chains in informal WhatsApp groups. Parents know official assignments from school rather than guessing which group message was authoritative. LMS modules extend this with study material and submission tracking for digitally mature campuses.',
  ),
  55 =>
  array (
    'id' => 'teacher-training',
    'category' => 'teacher',
    'q' => 'Is training provided for teachers on EduPortal?',
    'a' => 'Yes. Onboarding includes teacher-focused sessions on attendance, marks entry, and app basics — usually one hour is enough for confident daily use. Video guides and WhatsApp support help new hires mid-year. Principals appreciate that teachers adopt mobile tools quickly because interfaces mirror simple attendance registers, not complex enterprise software.',
  ),
);

    foreach ($faqs as &$faq) {
        if (str_contains($faq['a'], '{{total_clients}}')) {
            $faq['a'] = str_replace('{{total_clients}}', ep_site_metric('total_clients'), $faq['a']);
        }
    }
    unset($faq);

    return $faqs;
}

function ep_faqs_featured(): array
{
    return array_values(array_filter(ep_faqs(), fn(array $f) => !empty($f['featured'])));
}
