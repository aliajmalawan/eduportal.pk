import { readFileSync, writeFileSync, readdirSync, statSync, mkdirSync } from 'fs';
import { join, dirname, relative } from 'path';
import { fileURLToPath } from 'url';
import {
  SITE,
  organization,
  website,
  breadcrumbs,
  softwareApplication,
  faqPage,
  blogPosting,
  videoObject,
  reviewSchema,
  collectionPage,
  localBusiness,
  webPage,
  buildGraph,
  serializeGraph,
  absUrl,
} from './schema-lib.mjs';
import { FEATURE_MAP } from './features-data.mjs';
import { CASE_MAP } from './case-studies-data.mjs';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

function walk(dir, files = []) {
  for (const name of readdirSync(dir)) {
    const p = join(dir, name);
    if (statSync(p).isDirectory()) {
      if (!['node_modules', 'scripts', 'assets', 'css', 'js', 'docs'].includes(name)) walk(p, files);
    } else if (name.endsWith('.html')) files.push(p);
  }
  return files;
}

function loadFaqs() {
  const raw = readFileSync(join(ROOT, 'js/faqs-data.js'), 'utf8');
  const m = raw.match(/window\.EDUPORTAL_FAQS = (\[[\s\S]*?\]);\s*\n\s*window\.EDUPORTAL_FAQS_FEATURED/);
  return m ? eval(m[1]) : [];
}

function parseHtmlMeta(html) {
  const title = html.match(/<title>([^<]*)<\/title>/i)?.[1]?.trim() || '';
  const description =
    html.match(/<meta\s+name="description"\s+content="([^"]*)"/i)?.[1]?.trim() ||
    html.match(/<meta\s+content="([^"]*)"\s+name="description"/i)?.[1]?.trim() ||
    '';
  const canonical = html.match(/<link\s+rel="canonical"\s+href="([^"]*)"/i)?.[1]?.trim() || '';
  const robots = html.match(/<meta\s+name="robots"\s+content="([^"]*)"/i)?.[1] || '';
  return { title, description, canonical, robots };
}

function decodeHtml(s) {
  return s
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, ' ')
    .trim();
}

function extractFaqsFromHtml(html) {
  const faqs = [];
  const re =
    /class="fd-faq-q"[^>]*>[\s\S]*?<span>([^<]*)<\/span>[\s\S]*?class="fd-faq-a-inner">([\s\S]*?)<\/div>/gi;
  let m;
  while ((m = re.exec(html))) {
    faqs.push({ q: decodeHtml(m[1]), a: decodeHtml(m[2]) });
  }
  return faqs;
}

function resolveHref(href, canonical) {
  if (!href || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return null;
  if (href.startsWith('http')) return href;
  if (href.startsWith('#')) return canonical || SITE.url;
  const clean = href.replace(/^\.\.\//, '').replace(/^\//, '');
  if (clean === 'index.html' || clean === '') return `${SITE.url}/`;
  return `${SITE.url}/${clean}`;
}

function extractBreadcrumbFromHtml(html, canonical) {
  const navMatch = html.match(/<nav\s+aria-label="Breadcrumb">[\s\S]*?<\/nav>/i);
  if (!navMatch) return null;
  const items = [];
  const re = /<li>(?:<a href="([^"]*)">([^<]*)<\/a>|<span[^>]*aria-current="page"[^>]*>([^<]*)<\/span>)<\/li>/gi;
  let m;
  while ((m = re.exec(navMatch[0]))) {
    if (m[1] && m[2]) {
      const item = resolveHref(m[1], canonical);
      if (item) items.push({ name: decodeHtml(m[2]), item });
    } else if (m[3]) {
      items.push({ name: decodeHtml(m[3]), item: canonical || SITE.url });
    }
  }
  return items.length > 1 ? items : null;
}

function extractBlogPost(html, relPath) {
  const slug = relPath.split('/').pop().replace(/\.html$/, '');
  const h1 = html.match(/<h1>([^<]*)<\/h1>/i)?.[1];
  const tag = html.match(/class="blog-tag">([^<]*)</i)?.[1];
  const dateMatch = html.match(/blog-post-meta[\s\S]*?<span>([^<]+)<\/span>/i);
  const authorMatch = html.match(/By ([^<]+)</i);
  const img = html.match(/blog-post-hero[\s\S]*?src="([^"]*)"/i)?.[1];
  const meta = parseHtmlMeta(html);
  if (!h1) return null;
  let datePublished = '2026-05-01';
  if (dateMatch) {
    const d = new Date(dateMatch[1]);
    if (!isNaN(d)) datePublished = d.toISOString().split('T')[0];
  }
  return {
    slug,
    title: decodeHtml(h1),
    description: meta.description,
    category: tag ? decodeHtml(tag) : 'EdTech',
    datePublished,
    dateModified: datePublished,
    author: authorMatch ? decodeHtml(authorMatch[1]) : 'EduPortal Team',
    image: img || 'assets/dashboard.png',
  };
}

function extractVideosFromHtml(html) {
  const videos = [];
  const re =
    /class="video-card"[^>]*aria-label="Play testimonial from ([^"]+)"[\s\S]*?<strong>([^<]*)<\/strong><span>([^<]*)<\/span>/gi;
  let m;
  let i = 0;
  while ((m = re.exec(html))) {
    videos.push({
      videoId: SITE.defaultVideoId,
      name: `EduPortal testimonial — ${decodeHtml(m[1])}`,
      description: `${decodeHtml(m[2])}, ${decodeHtml(m[3])} shares their experience with EduPortal school ERP.`,
      id: `${SITE.url}/videos.html#video-${i++}`,
    });
  }
  return videos;
}

function getPageType(relPath) {
  const p = relPath.replace(/\\/g, '/');
  if (p === 'index.html') return 'home';
  if (p === 'landing.html') return 'landing';
  if (p === 'features.html') return 'features-list';
  if (p === 'pricing.html') return 'pricing';
  if (p === 'faqs.html') return 'faqs';
  if (p === 'blog.html') return 'blog-list';
  if (p === 'videos.html') return 'videos-list';
  if (p === 'case-studies.html') return 'case-studies-list';
  if (p === 'contact.html') return 'contact';
  if (p.startsWith('blog-') && p.endsWith('.html')) return 'blog-post';
  if (p.startsWith('case-studies/')) return 'case-study';
  if (FEATURE_MAP[p.replace('.html', '')]) return 'feature';
  return 'generic';
}

function buildSchemasForPage(relPath, html) {
  const type = getPageType(relPath);
  const meta = parseHtmlMeta(html);
  const url = meta.canonical || `${SITE.url}/${relPath.replace(/\\/g, '/')}`;
  const entities = [organization(), website()];
  const bc = extractBreadcrumbFromHtml(html, url);

  if (type === 'landing') return [organization()];

  const fallbackBc = {
    home: [{ name: 'Home', item: `${SITE.url}/` }],
    pricing: [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Pricing', item: url }],
    'features-list': [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Features', item: url }],
    faqs: [{ name: 'Home', item: `${SITE.url}/` }, { name: 'FAQs', item: url }],
    'blog-list': [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Blogs', item: url }],
    'videos-list': [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Video Testimonials', item: url }],
    'case-studies-list': [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Case Studies', item: url }],
    contact: [{ name: 'Home', item: `${SITE.url}/` }, { name: 'Contact', item: url }],
  };
  if (bc) entities.push(breadcrumbs(bc));
  else if (fallbackBc[type]) entities.push(breadcrumbs(fallbackBc[type]));

  switch (type) {
    case 'home':
      entities.push(softwareApplication({ url: SITE.url, description: meta.description }), webPage({ url, name: meta.title, description: meta.description }));
      break;
    case 'pricing':
      entities.push(softwareApplication({ url, description: meta.description, price: 4000 }), webPage({ url, name: meta.title, description: meta.description }));
      break;
    case 'feature': {
      const slug = relPath.replace('.html', '').replace(/\\/g, '/');
      const f = FEATURE_MAP[slug];
      entities.push(
        softwareApplication({ url, name: `EduPortal ${f?.title || ''}`, description: meta.description, featureList: f?.modules || SITE.featureHighlights }),
        webPage({ url, name: meta.title, description: meta.description })
      );
      const pageFaqs = extractFaqsFromHtml(html);
      const faq = faqPage(pageFaqs, url);
      if (faq) entities.push(faq);
      break;
    }
    case 'faqs': {
      const allFaqs = loadFaqs();
      entities.push(collectionPage({ url, name: 'Frequently Asked Questions — EduPortal', description: meta.description }), faqPage(allFaqs, url), webPage({ url, name: meta.title, description: meta.description }));
      break;
    }
    case 'features-list':
      entities.push(collectionPage({ url, name: 'EduPortal Features', description: meta.description }), webPage({ url, name: meta.title, description: meta.description }));
      break;
    case 'blog-list':
      entities.push(collectionPage({ url, name: 'EduPortal Blog', description: meta.description }), webPage({ url, name: meta.title, description: meta.description }));
      break;
    case 'blog-post': {
      const post = extractBlogPost(html, relPath);
      if (post) {
        entities.push(blogPosting(post));
        entities.push(breadcrumbs([{ name: 'Home', item: `${SITE.url}/` }, { name: 'Blogs', item: `${SITE.url}/blog.html` }, { name: post.title, item: url }]));
      }
      break;
    }
    case 'videos-list': {
      const vids = extractVideosFromHtml(html);
      entities.push(collectionPage({ url, name: 'Video Testimonials — EduPortal', description: meta.description }), webPage({ url, name: meta.title, description: meta.description }));
      vids.forEach((v) => entities.push(videoObject(v)));
      if (vids.length) {
        entities.push({
          '@type': 'ItemList',
          '@id': `${url}#videolist`,
          name: 'EduPortal Video Testimonials',
          numberOfItems: vids.length,
          itemListElement: vids.map((v, i) => ({ '@type': 'ListItem', position: i + 1, item: { '@id': v.id } })),
        });
      }
      entities.push(videoObject({ videoId: SITE.defaultVideoId, name: 'EduPortal Product Demo', description: 'Full walkthrough of EduPortal school management ERP.', id: `${url}#featured-video` }));
      break;
    }
    case 'case-studies-list':
      entities.push(collectionPage({ url, name: 'EduPortal Case Studies', description: meta.description }), webPage({ url, name: meta.title, description: meta.description }));
      break;
    case 'case-study': {
      const slug = relPath.split('/').pop().replace('.html', '');
      const c = CASE_MAP[slug];
      if (c) {
        entities.push(
          { '@type': 'Article', '@id': `${url}#article`, headline: `${c.name} Case Study — EduPortal`, description: c.summary, author: { '@id': `${SITE.url}/#organization` }, publisher: { '@id': `${SITE.url}/#organization` }, image: absUrl(c.image), mainEntityOfPage: { '@type': 'WebPage', '@id': url } },
          reviewSchema({ id: `${url}#review`, authorName: c.contactName, instituteName: c.name, reviewBody: c.quote, rating: 5, datePublished: '2025-08-15' }),
          videoObject({ videoId: c.videoId, name: c.videoTitle, description: c.summary, id: `${url}#video` }),
          webPage({ url, name: meta.title, description: meta.description })
        );
      }
      break;
    }
    case 'contact':
      entities.push(localBusiness(), webPage({ url, name: meta.title, description: meta.description }));
      break;
    default:
      entities.push(webPage({ url, name: meta.title, description: meta.description }));
  }
  return entities;
}

function injectSchema(html, schemaScript) {
  let out = html.replace(/<script type="application\/ld\+json"[^>]*>[\s\S]*?<\/script>\s*/gi, '');
  out = out.replace(/<script type="application\/ld\+json" id="eduportal-schema">[\s\S]*?<\/script>\s*/i, '');
  const insertAfter = out.match(/<link\s+rel="canonical"[^>]*>\s*/i);
  if (insertAfter) {
    const idx = out.indexOf(insertAfter[0]) + insertAfter[0].length;
    return out.slice(0, idx) + '\n  ' + schemaScript + '\n  ' + out.slice(idx);
  }
  const headEnd = out.indexOf('</head>');
  return headEnd > -1 ? out.slice(0, headEnd) + '\n  ' + schemaScript + '\n' + out.slice(headEnd) : out;
}

function validateGraph(graph) {
  const issues = [];
  if (!graph['@context']) issues.push('Missing @context');
  if (!graph['@graph']?.length) issues.push('Empty @graph');
  graph['@graph'].forEach((n) => {
    if (n['@type'] === 'FAQPage' && !n.mainEntity?.length) issues.push('FAQPage has no questions');
  });
  return { valid: issues.length === 0, issues };
}

const report = [];
const allFaqs = loadFaqs();
mkdirSync(join(ROOT, 'docs'), { recursive: true });

for (const file of walk(ROOT)) {
  const rel = relative(ROOT, file).replace(/\\/g, '/');
  let html = readFileSync(file, 'utf8');
  const meta = parseHtmlMeta(html);
  const type = getPageType(rel);

  if (meta.robots.includes('noindex')) {
    html = injectSchema(html, serializeGraph([organization()]));
    writeFileSync(file, html, 'utf8');
    report.push({ page: rel, type, schemas: ['Organization'], status: 'noindex-only', issues: [] });
    console.log(`○ ${rel} — noindex (Organization only)`);
    continue;
  }

  const entities = buildSchemasForPage(rel, html);
  const validation = validateGraph(buildGraph(entities));
  html = injectSchema(html, serializeGraph(entities));
  writeFileSync(file, html, 'utf8');

  const types = [...new Set(entities.map((e) => e['@type']).filter(Boolean))];
  report.push({ page: rel, type, schemas: types, status: validation.valid ? 'valid' : 'warnings', issues: validation.issues });
  console.log(`${validation.valid ? '✓' : '⚠'} ${rel} — ${types.join(', ')}`);
}

const md = `# EduPortal JSON-LD Schema Report

Generated: ${new Date().toISOString().split('T')[0]}

## Summary

| Metric | Count |
|--------|-------|
| Pages processed | ${report.length} |
| Valid | ${report.filter((r) => r.status === 'valid').length} |
| Noindex (org only) | ${report.filter((r) => r.status === 'noindex-only').length} |
| FAQ items (faqs.html) | ${allFaqs.length} |

## Schema Types

${[...new Set(report.flatMap((r) => r.schemas))].sort().map((t) => `- **${t}** — ${report.filter((r) => r.schemas.includes(t)).length} pages`).join('\n')}

## All Pages

| Page | Type | Schemas | Status |
|------|------|---------|--------|
${report.map((r) => `| ${r.page} | ${r.type} | ${r.schemas.join(', ')} | ${r.status} |`).join('\n')}

## Re-run

\`\`\`bash
node scripts/inject-schemas.mjs
\`\`\`

Validate: [Google Rich Results Test](https://search.google.com/test/rich-results) | [Schema.org Validator](https://validator.schema.org/)
`;

writeFileSync(join(ROOT, 'docs', 'SCHEMA-REPORT.md'), md, 'utf8');
console.log(`\nReport written to docs/SCHEMA-REPORT.md`);
