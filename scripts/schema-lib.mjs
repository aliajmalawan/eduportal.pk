/**
 * EduPortal JSON-LD schema builders — Schema.org compliant
 */
export const SITE = {
  name: 'EduPortal',
  legalName: 'EduPortal',
  url: 'https://eduportal.io',
  logo: 'https://eduportal.io/assets/logo_icon.jpg',
  email: 'info@eduportal.pk',
  telephone: '+92-309-1920336',
  telephoneAlt: '+92-301-6138728',
  description:
    'EduPortal is AI-powered school management software (ERP) for attendance, fee collection, exams, parent apps and analytics. Trusted by 500+ schools in Pakistan.',
  foundingDate: '2018',
  founderName: 'EduPortal Team',
  address: {
    streetAddress: 'Office #2, 2nd Floor Asad Plaza, Gamtala Chowk',
    addressLocality: 'Shakargarh',
    addressRegion: 'Punjab',
    postalCode: '51800',
    addressCountry: 'PK',
  },
  geo: { latitude: 32.2636, longitude: 75.1601 },
  sameAs: [
    'https://www.facebook.com/eduportal',
    'https://www.linkedin.com/company/eduportal',
    'https://play.google.com/store/apps/details?id=com.educationportal',
  ],
  openingHours: ['Monday 09:00-18:00', 'Tuesday 09:00-18:00', 'Wednesday 09:00-18:00', 'Thursday 09:00-18:00', 'Friday 09:00-18:00', 'Saturday 09:00-14:00'],
  // NOTE: a hardcoded `aggregateRating: { ratingValue: 4.8, reviewCount: 512 }`
  // used to live here and was emitted into every generated page. It was
  // invented, not measured. Do not reintroduce a rating constant: real
  // ratings live in the google_rating / google_review_count settings, and
  // republishing those as our own schema is prohibited (see
  // GOOGLE_REVIEWS_SETUP.md).
  priceCurrency: 'PKR',
  priceLow: 4000,
  screenshots: [
    'https://eduportal.io/assets/dashboard.png',
    'https://eduportal.io/assets/parent-app.jpg',
  ],
  featureHighlights: [
    'Student Information System',
    'Fee Management & Vouchers',
    'Digital Attendance & Face Recognition',
    'Exam Results & Report Cards',
    'Parent & Teacher Mobile Apps',
    'WhatsApp & SMS Communication',
    'Accounts & Payroll',
    'Multi-Campus Management',
  ],
  defaultVideoId: 'ar637Gcm3K0',
  attendanceVideoId: 'QKHYk0vdkjY',
};

export function absUrl(path, pageUrl) {
  if (!path) return SITE.url;
  if (path.startsWith('http')) return path;
  const base = pageUrl && pageUrl.includes('/case-studies/')
    ? SITE.url
    : SITE.url;
  const clean = path.replace(/^\.\.\//, '').replace(/^\//, '');
  return `${SITE.url}/${clean}`;
}

export function organization() {
  return {
    '@type': 'Organization',
    '@id': `${SITE.url}/#organization`,
    name: SITE.name,
    legalName: SITE.legalName,
    url: SITE.url,
    logo: { '@type': 'ImageObject', url: SITE.logo, width: 512, height: 512 },
    image: SITE.logo,
    description: SITE.description,
    email: SITE.email,
    telephone: SITE.telephone,
    foundingDate: SITE.foundingDate,
    founder: { '@type': 'Organization', name: SITE.founderName },
    address: { '@type': 'PostalAddress', ...SITE.address },
    contactPoint: [
      {
        '@type': 'ContactPoint',
        telephone: SITE.telephone,
        contactType: 'customer support',
        email: SITE.email,
        areaServed: 'PK',
        availableLanguage: ['English', 'Urdu'],
      },
    ],
    sameAs: SITE.sameAs,
  };
}

export function website() {
  return {
    '@type': 'WebSite',
    '@id': `${SITE.url}/#website`,
    name: SITE.name,
    url: SITE.url,
    description: SITE.description,
    publisher: { '@id': `${SITE.url}/#organization` },
    inLanguage: 'en',
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${SITE.url}/faqs.html?q={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };
}

export function breadcrumbs(items) {
  return {
    '@type': 'BreadcrumbList',
    '@id': `${items[items.length - 1]?.item || SITE.url}#breadcrumb`,
    itemListElement: items.map((item, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: item.name,
      item: item.item,
    })),
  };
}

export function softwareApplication(opts = {}) {
  const url = opts.url || SITE.url;
  return {
    '@type': 'SoftwareApplication',
    '@id': `${url}#software`,
    name: opts.name || SITE.name,
    applicationCategory: 'BusinessApplication',
    applicationSubCategory: 'School Management Software',
    operatingSystem: 'Web, Android, iOS',
    description: opts.description || SITE.description,
    url,
    image: SITE.screenshots,
    screenshot: SITE.screenshots.map((u) => ({ '@type': 'ImageObject', url: u })),
    featureList: opts.featureList || SITE.featureHighlights,
    offers: {
      '@type': 'Offer',
      priceCurrency: SITE.priceCurrency,
      price: opts.price || SITE.priceLow,
      availability: 'https://schema.org/InStock',
      url: `${SITE.url}/pricing.html`,
    },
    // aggregateRating deliberately removed. It previously emitted a
    // hardcoded 4.8 / 512 that corresponded to no real review data — a
    // fabricated rating, which is both dishonest and against Google's
    // structured data guidelines. Any real figures we do hold come from
    // Google Business Profile, and Google's review-snippet rules forbid
    // republishing those as our own aggregateRating ("Don't aggregate
    // reviews or ratings from other websites"). See
    // GOOGLE_REVIEWS_SETUP.md for the full policy quotes.
    provider: { '@id': `${SITE.url}/#organization` },
  };
}

export function faqPage(faqs, pageUrl) {
  if (!faqs?.length) return null;
  return {
    '@type': 'FAQPage',
    '@id': `${pageUrl}#faq`,
    mainEntity: faqs.map((f) => ({
      '@type': 'Question',
      name: f.q,
      acceptedAnswer: { '@type': 'Answer', text: f.a },
    })),
  };
}

export function blogPosting(post) {
  const url = `${SITE.url}/${post.slug}.html`;
  return {
    '@type': 'BlogPosting',
    '@id': `${url}#article`,
    headline: post.title,
    description: post.description,
    image: absUrl(post.image),
    author: { '@type': 'Organization', name: post.author || 'EduPortal Team', url: SITE.url },
    publisher: {
      '@type': 'Organization',
      name: SITE.name,
      logo: { '@type': 'ImageObject', url: SITE.logo },
    },
    datePublished: post.datePublished,
    dateModified: post.dateModified || post.datePublished,
    articleSection: post.category,
    mainEntityOfPage: { '@type': 'WebPage', '@id': url },
    url,
    inLanguage: 'en',
  };
}

export function videoObject(v) {
  const embed = `https://www.youtube.com/embed/${v.videoId}`;
  return {
    '@type': 'VideoObject',
    '@id': v.id || `${embed}#video`,
    name: v.name,
    description: v.description,
    thumbnailUrl: v.thumbnailUrl || `https://eduportal.io/assets/thumbnail/${v.thumbnailKey || 'fusion'}-720.jpg`,
    uploadDate: v.uploadDate || '2025-06-01',
    duration: v.duration || 'PT5M',
    contentUrl: `https://www.youtube.com/watch?v=${v.videoId}`,
    embedUrl: embed,
    publisher: { '@id': `${SITE.url}/#organization` },
  };
}

export function reviewSchema(r) {
  return {
    '@type': 'Review',
    '@id': r.id,
    itemReviewed: {
      '@type': 'SoftwareApplication',
      name: SITE.name,
      '@id': `${SITE.url}#software`,
    },
    author: { '@type': 'Person', name: r.authorName },
    reviewRating: {
      '@type': 'Rating',
      ratingValue: String(r.rating || 5),
      bestRating: '5',
      worstRating: '1',
    },
    reviewBody: r.reviewBody,
    datePublished: r.datePublished || '2025-06-01',
    publisher: { '@type': 'Organization', name: r.instituteName || r.authorName },
  };
}

export function collectionPage(opts) {
  return {
    '@type': 'CollectionPage',
    '@id': `${opts.url}#collection`,
    name: opts.name,
    description: opts.description,
    url: opts.url,
    isPartOf: { '@id': `${SITE.url}/#website` },
    about: { '@type': 'SoftwareApplication', name: SITE.name },
    publisher: { '@id': `${SITE.url}/#organization` },
  };
}

export function localBusiness() {
  return {
    '@type': 'LocalBusiness',
    '@id': `${SITE.url}/contact.html#localbusiness`,
    name: SITE.name,
    image: SITE.logo,
    url: SITE.url,
    telephone: [SITE.telephone, SITE.telephoneAlt],
    email: SITE.email,
    address: { '@type': 'PostalAddress', ...SITE.address },
    geo: {
      '@type': 'GeoCoordinates',
      latitude: SITE.geo.latitude,
      longitude: SITE.geo.longitude,
    },
    openingHoursSpecification: [
      {
        '@type': 'OpeningHoursSpecification',
        dayOfWeek: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        opens: '09:00',
        closes: '18:00',
      },
      { '@type': 'OpeningHoursSpecification', dayOfWeek: 'Saturday', opens: '09:00', closes: '14:00' },
    ],
    priceRange: '$$',
    areaServed: { '@type': 'Country', name: 'Pakistan' },
  };
}

export function webPage(opts) {
  return {
    '@type': 'WebPage',
    '@id': `${opts.url}#webpage`,
    name: opts.name,
    description: opts.description,
    url: opts.url,
    isPartOf: { '@id': `${SITE.url}/#website` },
  };
}

export function buildGraph(entities) {
  const filtered = entities.filter(Boolean);
  return {
    '@context': 'https://schema.org',
    '@graph': filtered,
  };
}

export function serializeGraph(entities) {
  return `<script type="application/ld+json" id="eduportal-schema">\n${JSON.stringify(buildGraph(entities), null, 2)}\n</script>`;
}
