<?php
/**
 * Converts every JPEG/PNG under assets/ to a sibling .webp file.
 *
 * The originals are kept. Apache serves the .webp version only when the
 * browser advertises WebP support and the converted file exists (see the
 * rewrite in .htaccess), so old browsers still get the original and no
 * template or <img> tag needs changing.
 *
 * Safe to re-run: a .webp that is already newer than its source is skipped,
 * and a conversion that comes out larger than the original is discarded —
 * serving a bigger "optimised" file would be worse than doing nothing.
 *
 * Usage:  php scripts/convert-images-to-webp.php [--force] [--quality=82]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI-only.\n";
    exit(1);
}
if (!extension_loaded('gd') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(1);
}

$force = in_array('--force', $argv, true);
$quality = 82;
foreach ($argv as $a) {
    if (preg_match('/^--quality=(\d{1,3})$/', $a, $m)) {
        $quality = max(1, min(100, (int) $m[1]));
    }
}

$root = dirname(__DIR__);
$dir = $root . '/assets';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

$converted = $skipped = $rejected = $failed = 0;
$srcBytes = $webpBytes = 0;

foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }

    $out = $path . '.webp';
    if (!$force && is_file($out) && filemtime($out) >= filemtime($path)) {
        $skipped++;
        continue;
    }

    $img = match ($ext) {
        'png' => @imagecreatefrompng($path),
        default => @imagecreatefromjpeg($path),
    };
    if (!$img) {
        fwrite(STDERR, "  could not read: {$path}\n");
        $failed++;
        continue;
    }
    if ($ext === 'png') {
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
    }

    $tmp = $out . '.tmp';
    $ok = imagewebp($img, $tmp, $quality);
    imagedestroy($img);
    if (!$ok || !is_file($tmp)) {
        @unlink($tmp);
        $failed++;
        continue;
    }

    $origSize = filesize($path);
    $newSize = filesize($tmp);
    if ($newSize >= $origSize) {
        // No gain — don't ship a "converted" file that is bigger.
        @unlink($tmp);
        @unlink($out);
        $rejected++;
        continue;
    }

    @unlink($out);
    rename($tmp, $out);
    $converted++;
    $srcBytes += $origSize;
    $webpBytes += $newSize;
}

$saved = $srcBytes - $webpBytes;
printf("  converted: %d   skipped (up to date): %d   rejected (no gain): %d   failed: %d\n",
    $converted, $skipped, $rejected, $failed);
if ($converted > 0) {
    printf("  %.1f MB -> %.1f MB  (saved %.1f MB, %.0f%%)\n",
        $srcBytes / 1048576, $webpBytes / 1048576, $saved / 1048576,
        $srcBytes > 0 ? ($saved / $srcBytes) * 100 : 0);
}
