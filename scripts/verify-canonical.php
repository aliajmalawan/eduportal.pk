<?php
/**
 * Dev-only sanity check for canonical tags across the site.
 *
 * Checks every root-level page (auto-discovered, so new pages are covered
 * automatically) plus one real blog article and one real case study.
 *
 * Requires the local dev server to be running.
 * Usage: php scripts/verify-canonical.php [base-url]
 *        (default base-url: http://localhost/eduportal)
 */

$baseUrl = rtrim($argv[1] ?? 'http://localhost/eduportal', '/');

$skip = [
    'landing.php',    // intentionally noindex/nofollow, never sets a canonical tag
    'blog-post.php',  // requires ?slug=... to resolve; checked separately below with a real slug
    'case-study.php', // requires ?slug=... to resolve; checked separately below with a real slug
];

$pages = [];
foreach (glob(__DIR__ . '/../*.php') as $file) {
    $name = basename($file);
    if (!in_array($name, $skip, true)) {
        $pages[] = '/' . $name;
    }
}
sort($pages);

// Add one real blog article and one real case study, if data exists.
$blogHtml = @file_get_contents($baseUrl . '/blog.php');
if ($blogHtml && preg_match('/blog-post\.php\?slug=[^"\']+/', $blogHtml, $m)) {
    $pages[] = '/' . $m[0];
}
$csHtml = @file_get_contents($baseUrl . '/case-studies.php');
if ($csHtml && preg_match('/case-study\.php\?slug=[^"\']+/', $csHtml, $m)) {
    $pages[] = '/' . $m[0];
}

$failures = 0;

foreach ($pages as $path) {
    $url = $baseUrl . $path;
    $html = @file_get_contents($url);

    if ($html === false) {
        echo "FAIL  $path\n        - could not fetch (is the dev server running at $baseUrl?)\n";
        $failures++;
        continue;
    }

    preg_match_all('/<link\s+rel="canonical"\s+href="([^"]*)"\s*\/?>/i', $html, $matches);
    $hrefs = $matches[1];

    $issues = [];
    if (count($hrefs) !== 1) {
        $issues[] = count($hrefs) . ' canonical tag(s) found (expected exactly 1)';
    } else {
        $href = $hrefs[0];
        if (strpos($href, 'https://eduportal.pk') !== 0) {
            $issues[] = "does not start with https://eduportal.pk (got: $href)";
        }
        if (strpos($href, 'eduportal.io') !== false) {
            $issues[] = 'contains eduportal.io';
        }
        if (stripos($href, 'undefined') !== false) {
            $issues[] = 'contains "undefined"';
        }
        if (stripos($href, 'null') !== false) {
            $issues[] = 'contains "null"';
        }
        if (strpos($href, '//', 8) !== false) {
            $issues[] = 'contains a duplicate slash';
        }
    }

    if ($issues) {
        echo "FAIL  $path\n";
        foreach ($issues as $issue) {
            echo "        - $issue\n";
        }
        $failures++;
    } else {
        echo "PASS  $path  -> {$hrefs[0]}\n";
    }
}

echo "\n" . count($pages) . " page(s) checked, $failures failure(s).\n";
exit($failures > 0 ? 1 : 0);
