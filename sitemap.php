<?php
/**
 * XML sitemap — served at /sitemap.xml (see the rewrite in .htaccess).
 *
 * Covers the site's static pages plus everything that is published from the
 * database: blogs, case studies and — added in Task 7 — the careers page and
 * every open job at its /careers/{slug} URL. Jobs drop out of the sitemap the
 * moment they are unpublished or their deadline passes, because it reuses the
 * same ep_get_open_jobs() query the public listing uses.
 */
require_once __DIR__ . '/includes/careers.php';

$base = 'https://eduportal.pk/';

/**
 * Scripts at the site root that must never appear in the sitemap: detail
 * pages that only exist behind a slug (they are emitted from the database
 * below), the standalone landing page, and this file itself.
 */
$excluded = ['blog-post.php', 'case-study.php', 'career.php', 'landing.php', 'sitemap.php', 'compare.php', 'docs.php'];

/** @var array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}> */
$urls = [];

$addUrl = static function (string $loc, ?string $lastmod, string $changefreq, string $priority) use (&$urls): void {
    $urls[] = [
        'loc' => $loc,
        'lastmod' => $lastmod ? date('Y-m-d', strtotime($lastmod)) : '',
        'changefreq' => $changefreq,
        'priority' => $priority,
    ];
};

// --- Homepage
$addUrl($base, date('Y-m-d', @filemtime(__DIR__ . '/index.php') ?: time()), 'weekly', '1.0');

// --- Static pages (features, pricing, about, every feature detail page, …)
$priorities = [
    'features.php' => '0.9',
    'pricing.php' => '0.9',
    'careers.php' => '0.8',
    'about.php' => '0.7',
    'contact.php' => '0.7',
    'blog.php' => '0.7',
    'case-studies.php' => '0.7',
    'videos.php' => '0.6',
    'reviews.php' => '0.6',
    'faqs.php' => '0.6',
];
foreach (glob(__DIR__ . '/*.php') ?: [] as $file) {
    $name = basename($file);
    if ($name === 'index.php' || in_array($name, $excluded, true)) {
        continue;
    }
    // Pages published under a clean URL are listed at that URL only, never
    // at the .php path they are served from — the .php URL 301s away.
    $featureSlug = ep_feature_slug($name);
    if ($featureSlug !== '') {
        $path = 'features/' . $featureSlug;
    } elseif ($name === 'careers.php') {
        $path = 'careers';
    } elseif ($name === 'pricing.php') {
        $path = 'pricing';
    } elseif ($name === 'case-studies.php') {
        $path = 'case-studies';
    } elseif ($name === 'case-study.php') {
        // Detail pages are listed individually further down, never as the
        // bare script (which has no slug and would 404).
        continue;
    } else {
        $path = $name;
    }
    $addUrl($base . $path, date('Y-m-d', (int) filemtime($file)), 'monthly', $priorities[$name] ?? ($featureSlug !== '' ? '0.8' : '0.5'));
}

// --- Open jobs (Task 7)
foreach (ep_get_open_jobs() as $job) {
    $addUrl(
        $base . 'careers/' . rawurlencode((string) $job['slug']),
        (string) ($job['updated_at'] ?? $job['posted_at'] ?? ''),
        'weekly',
        '0.8'
    );
}

// --- Documentation (Task 8): only published guides
foreach (ep_get_docs() as $doc) {
    $addUrl(
        $base . 'docs/' . rawurlencode((string) $doc['slug']),
        (string) ($doc['updated_at'] ?? ''),
        'monthly',
        '0.6'
    );
}

// --- Comparison pages (Task 8): only those actually published
foreach (ep_get_comparisons() as $cmp) {
    $addUrl(
        $base . 'compare/' . rawurlencode((string) $cmp['slug']),
        (string) ($cmp['updated_at'] ?? ''),
        'monthly',
        '0.7'
    );
}

// --- Case studies (Task 8): each at its clean /case-studies/{slug} URL
foreach (ep_get_case_studies() as $study) {
    $addUrl(
        $base . 'case-studies/' . rawurlencode((string) $study['slug']),
        (string) ($study['updated_at'] ?? $study['published_at'] ?? ''),
        'monthly',
        '0.7'
    );
}

// --- Published blogs
foreach (ep_get_blogs(500) as $blog) {
    $addUrl(
        $base . 'blog-post.php?slug=' . rawurlencode((string) $blog['slug']),
        (string) ($blog['updated_at'] ?? $blog['published_at'] ?? ''),
        'monthly',
        '0.6'
    );
}


header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
<?php if ($url['lastmod'] !== ''): ?>
    <lastmod><?= $url['lastmod'] ?></lastmod>
<?php endif; ?>
    <changefreq><?= $url['changefreq'] ?></changefreq>
    <priority><?= $url['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
