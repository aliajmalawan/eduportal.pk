<?php
/**
 * Builds assets/og-share.jpg — the default Open Graph / Twitter share image.
 *
 * Social platforms want 1200x630 (1.91:1). The product screenshot is 1536x1024
 * (1.50:1) and 1.37 MB, so sharing it directly means the platform crops it
 * unpredictably and every preview fetch pulls a megabyte. This composites a
 * proper card instead: brand ground, wordmark, headline, and a cropped product
 * shot that never gets cut mid-content.
 *
 * Run locally (the font paths below are Windows); the result is committed as a
 * plain .jpg, so the live server never runs this.
 *   php scripts/make-og-image.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$W = 1200; $H = 630;
$canvas = imagecreatetruecolor($W, $H);

// Brand dark band (--gradient-dark-band in css/shared.css: #0f1419 -> #1a2332),
// on a diagonal so the flat ground behind the text has some depth.
for ($y = 0; $y < $H; $y++) {
    $t = $y / max(1, $H - 1);
    imageline($canvas, 0, $y, $W, $y, imagecolorallocate(
        $canvas,
        (int) round(0x0f + (0x1a - 0x0f) * $t),
        (int) round(0x14 + (0x23 - 0x14) * $t),
        (int) round(0x19 + (0x32 - 0x19) * $t)
    ));
}

$accent = imagecolorallocate($canvas, 0xf2, 0x8c, 0x28);
$white  = imagecolorallocate($canvas, 255, 255, 255);
$muted  = imagecolorallocate($canvas, 0x9c, 0xa6, 0xb4);

// One accent keyline along the top edge — the only place the brand orange is
// spent, so it reads as a mark rather than decoration.
imagefilledrectangle($canvas, 0, 0, $W, 6, $accent);

$bold = 'C:/Windows/Fonts/arialbd.ttf';
$reg  = 'C:/Windows/Fonts/arial.ttf';

// ---- left column: wordmark, headline, supporting line -------------------
$logoPath = $root . '/assets/logo.jpg';
if (is_file($logoPath)) {
    $logo = imagecreatefromjpeg($logoPath);
    $lw = imagesx($logo); $lh = imagesy($logo);
    $tw = 232; $th = (int) round($lh * ($tw / $lw));
    // logo.jpg carries a white ground, so it sits on a white plate to keep its
    // edges clean against the dark card.
    imagefilledrectangle($canvas, 64, 62, 64 + $tw + 28, 62 + $th + 22, $white);
    imagecopyresampled($canvas, $logo, 78, 73, 0, 0, $tw, $th, $lw, $lh);
    imagedestroy($logo);
}

if (is_file($bold)) {
    imagettftext($canvas, 40, 0, 64, 250, $white, $bold, 'School management');
    imagettftext($canvas, 40, 0, 64, 306, $white, $bold, 'software built for');
    imagettftext($canvas, 40, 0, 64, 362, $accent, $bold, 'Pakistani schools');
}
if (is_file($reg)) {
    imagettftext($canvas, 17, 0, 64, 424, $muted, $reg, 'Fees, attendance, exams, transport');
    imagettftext($canvas, 17, 0, 64, 456, $muted, $reg, 'and parent apps in one system.');
    imagettftext($canvas, 16, 0, 64, 540, $accent, $reg, 'eduportal.pk');
}

// ---- right column: product shot -----------------------------------------
$shotPath = $root . '/assets/dashboard.png';
if (is_file($shotPath)) {
    $shot = imagecreatefrompng($shotPath);
    $sw = imagesx($shot); $sh = imagesy($shot);

    $dw = 545; $dh = 430;            // destination window
    $dx = 590;  $dy = 105;
    // Crop the source to the destination's aspect so nothing is squashed, and
    // anchor top-left so the KPI row stays in frame rather than the footer.
    $srcW = min($sw, (int) round($sh * ($dw / $dh)));
    $srcH = (int) round($srcW * ($dh / $dw));
    if ($srcH > $sh) { $srcH = $sh; $srcW = (int) round($srcH * ($dw / $dh)); }

    // Thin light frame so the bright UI does not butt straight onto the ground.
    imagefilledrectangle($canvas, $dx - 3, $dy - 3, $dx + $dw + 3, $dy + $dh + 3, $muted);
    imagecopyresampled($canvas, $shot, $dx, $dy, 0, 0, $dw, $dh, $srcW, $srcH);
    imagedestroy($shot);
}

$out = $root . '/assets/og-share.jpg';
imagejpeg($canvas, $out, 84);
imagedestroy($canvas);
printf("Wrote %s (%dx%d, %.1f KB)%s", $out, $W, $H, filesize($out) / 1024, PHP_EOL);
