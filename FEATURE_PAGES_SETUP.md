# Feature Pages, /features/ URLs & FAQ Structured Data — Setup

Task 8, step 1. Covers the 26 module pages: their new `/features/{slug}` URLs,
the 301 redirects from the old `.php` URLs, and the `FAQPage` structured data
that makes their content quotable by Google and AI tools.

## What was already there

The module pages were **not** short marketing pages. Each already had:

- **1,800–2,100 words** (the brief asked for 800–1,200)
- sections covering what the module does, why a Pakistani school needs it,
  how it works step by step, and its full capability list
- a screenshots/mockups gallery
- **10 FAQs** each, written for Pakistani schools

So no new page content was written. What was missing was the structured data —
the part that actually gets a page quoted — and the `/features/` URLs.

## 1. FAQPage structured data

All 26 pages now emit a `FAQPage` node inside their existing `@graph`
(alongside `WebSite`, `BreadcrumbList` and `WebPage`). **260 questions** in
total.

Each page defines its FAQs once, near the top:

```php
$moduleFaqs = [
    ['q' => 'What digital attendance options does EduPortal offer?',
     'a' => 'EduPortal offers four modes: …'],
    …
];
$jsonLdSchema = ep_append_faq_schema($jsonLdSchema, $moduleFaqs, $canonicalUrl);
```

and renders the accordion from that same array:

```php
<?php ep_render_faq_accordion($moduleFaqs); ?>
```

**One array drives both.** This matters: Google drops FAQ rich results when the
markup does not match the visible text, so the two must never drift apart.

Answers may contain `{total_clients}`, `{total_students}` or `{total_cities}`
tokens, expanded by `ep_faq_expand_tokens()` to the live values from Settings.
The expanded number appears in both the visible answer and the JSON-LD, so the
structured data never publishes a placeholder.

## 2. /features/ URLs

`ep_feature_pages()` in `includes/cms.php` maps each script to its slug and is
the **single source of truth** — routing, canonical tags, the sitemap, and
internal links all derive from it.

| URL | Script |
|---|---|
| `/features/attendance` | `attendance-management.php` |
| `/features/fee-management` | `fee-management.php` |
| `/features/examinations` | `examination-management.php` |
| `/features/sms-alerts` | `sms-messaging.php` |
| `/features/whatsapp` | `whatsapp-communication.php` |
| `/features/parent-app` | `parent-mobile-app.php` |
| `/features/teacher-app` | `teacher-mobile-app.php` |
| …19 more | see `ep_feature_pages()` |

### Redirects

Every old `.php` URL **301s to its `/features/` URL in exactly one hop**:

```apache
RewriteCond %{THE_REQUEST} \s([^\s]*)/attendance-management\.php[\s?] [NC]
RewriteRule ^ %1/features/attendance? [R=301,L]

RewriteRule ^features/attendance/?$ attendance-management.php [L,QSA]
```

Two details worth keeping:

- The redirect matches `%{THE_REQUEST}` (the original request line), not the
  rewritten path. The internal rewrite therefore cannot re-trigger the
  redirect, so there is **no loop**.
- `%1` captures the URL path prefix, so the rules work whether the site is at
  the domain root (production) or in a subdirectory (`/eduportal/` on XAMPP).
  No `RewriteBase` to keep in sync between environments.

### Relative URLs — the thing that breaks on a URL move

The pages are now one path segment deep, so a relative `href="css/shared.css"`
would resolve to `/features/css/shared.css` and 404. **263 relative references
across the 26 pages** were converted to absolute URLs:

- `includes/head.php`, `footer.php`, `header.php` now pass asset paths through
  `ep_asset_url()`, which leaves absolute and protocol-relative URLs alone.
- In-page links go through `ep_url()`, and links to other module pages through
  `ep_feature_url()` so they land on the new URL without a 301 hop.

A `<base>` tag would have been a one-line fix but was rejected: it also changes
how `href="#"` and in-page anchors resolve, which would break the FAQ accordion
and the "Apply now" style jump links.

## 3. Sitemap

`sitemap.xml` lists each module at its `/features/{slug}` URL only, never at
the `.php` path it is served from, at priority `0.8`.

## After deploying

1. Submit `sitemap.xml` in Search Console so the new URLs are discovered.
2. Spot-check a page in the [Rich Results Test](https://search.google.com/test/rich-results)
   — it should report a valid **FAQPage**. This needs a public URL, so it
   cannot be run against localhost.
3. Expect a week or two of ranking fluctuation while Google re-indexes and
   follows the 301s. This is normal for a URL migration; the redirects pass
   the existing authority to the new URLs.
4. Leave the redirects in place permanently — old links and backlinks will
   keep arriving at the `.php` URLs indefinitely.
