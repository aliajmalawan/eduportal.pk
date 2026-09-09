<?php
/**
 * Generates minified .min.css / .min.js siblings for every stylesheet and
 * script under css/ and js/.
 *
 * Apache serves the .min version automatically when it exists (see the
 * rewrite in .htaccess), so no template or <link>/<script> tag changes —
 * and deleting a .min file instantly falls back to the readable original.
 *
 * Deliberately conservative. It strips comments, indentation and blank
 * lines but KEEPS every newline, so JavaScript's automatic-semicolon-
 * insertion cannot change meaning — the classic way a naive minifier
 * silently breaks a site. Gzip (also enabled in .htaccess) does the heavy
 * lifting on the remaining whitespace, so the aggressive tricks a real
 * minifier like terser performs are not worth the risk here.
 *
 * Usage:  php scripts/minify-assets.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI-only.\n";
    exit(1);
}

$root = dirname(__DIR__);

/** Strip /* *\/ and // comments without touching strings or regex literals. */
function ep_strip_comments(string $src, bool $isJs): string
{
    $out = '';
    $len = strlen($src);
    $i = 0;
    $quote = null;       // active string delimiter
    while ($i < $len) {
        $c = $src[$i];
        $next = $i + 1 < $len ? $src[$i + 1] : '';

        if ($quote !== null) {
            $out .= $c;
            if ($c === '\\') {                 // escape: copy next char verbatim
                if ($next !== '') { $out .= $next; $i += 2; continue; }
            } elseif ($c === $quote) {
                $quote = null;
            }
            $i++;
            continue;
        }

        if ($c === '"' || $c === "'" || ($isJs && $c === '`')) {
            $quote = $c;
            $out .= $c;
            $i++;
            continue;
        }

        if ($c === '/' && $next === '*') {      // block comment
            $end = strpos($src, '*/', $i + 2);
            $i = $end === false ? $len : $end + 2;
            continue;
        }
        if ($isJs && $c === '/' && $next === '/') {
            // Line comment — but only when '/' cannot be starting a regex or
            // division. Preceding non-space char tells us enough in practice.
            $prev = rtrim($out);
            $prevChar = $prev === '' ? '' : substr($prev, -1);
            if ($prevChar === '' || !preg_match('/[A-Za-z0-9_$)\]]/', $prevChar) || str_ends_with($prev, 'return')) {
                $end = strpos($src, "\n", $i);
                $i = $end === false ? $len : $end;
                continue;
            }
            // Also treat "http://" style inside code as safe to keep.
            $end = strpos($src, "\n", $i);
            $i = $end === false ? $len : $end;
            continue;
        }

        $out .= $c;
        $i++;
    }
    return $out;
}

function ep_minify(string $path, bool $isJs): array
{
    $src = file_get_contents($path);
    $orig = strlen($src);

    // Comments are stripped from CSS only. Doing it to JavaScript requires a
    // real tokenizer: a regex literal such as /^\/+/ contains "//" and a naive
    // scanner truncates the line, which is exactly how videos-page.js was
    // silently corrupted on the first run here. Gzip compresses repeated
    // comment text well, so the remaining gain is not worth shipping broken
    // JavaScript for. Whitespace-only handling below is safe either way.
    $s = $isJs ? $src : ep_strip_comments($src, false);

    // Collapse indentation and drop blank lines; keep newlines so JS ASI is safe.
    $lines = preg_split('/\R/', $s);
    $kept = [];
    foreach ($lines as $line) {
        $t = rtrim($line);
        $t = preg_replace('/^[ \t]+/', '', $t);
        if ($t !== '') {
            $kept[] = $t;
        }
    }
    $min = implode("\n", $kept);

    if (!$isJs) {
        // CSS-only: safe structural whitespace removal.
        $min = preg_replace('/\s*([{}:;,>])\s*/', '$1', $min);
        $min = preg_replace('/;}/', '}', $min);
    }

    return [$min, $orig, strlen($min)];
}

$totalOrig = $totalMin = $count = 0;
foreach (['css' => false, 'js' => true] as $dir => $isJs) {
    $ext = $dir;
    foreach (glob("{$root}/{$dir}/*.{$ext}") ?: [] as $file) {
        if (str_ends_with($file, ".min.{$ext}")) {
            continue;
        }
        [$min, $o, $m] = ep_minify($file, $isJs);
        $out = preg_replace('/\.' . $ext . '$/', ".min.{$ext}", $file);
        file_put_contents($out, $min);
        $totalOrig += $o;
        $totalMin += $m;
        $count++;
        printf("  %-30s %6d -> %6d bytes (-%2.0f%%)\n",
            basename($file), $o, $m, $o > 0 ? (1 - $m / $o) * 100 : 0);
    }
}
printf("\n  %d file(s): %.1f KB -> %.1f KB (saved %.0f%%)\n",
    $count, $totalOrig / 1024, $totalMin / 1024,
    $totalOrig > 0 ? (1 - $totalMin / $totalOrig) * 100 : 0);
