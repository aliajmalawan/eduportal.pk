<?php
/**
 * Re-compress already-uploaded testimonial videos that are still large
 * (uploaded before the resolution cap in cms_ffmpeg_convert() existed, or
 * simply came from a high-resolution source). Unlike convert-videos.php,
 * this never changes a file's name or extension — every video is
 * re-encoded to a temp file and, only if the result is actually smaller,
 * swapped in under the exact same path. That means no database row ever
 * needs to change: every ep_video_testimonials.video_file_path and
 * ep_site_settings.demo_video_file_path stays valid before and after.
 */
require_once __DIR__ . '/includes/layout.php';
cms_require_super_admin();

global $m;

$ffmpeg = cms_ffmpeg_path();
$videoDir = dirname(__DIR__) . '/assets/video-testimonials';

// Above this, a video is flagged as a likely candidate and pre-checked —
// admins can still select/deselect anything by hand.
$candidateBytes = 3 * 1024 * 1024;

/** @return array<int, array{path: string, name: string, size: int}> */
$listMp4Files = static function (string $dir): array {
    if (!is_dir($dir)) {
        return [];
    }
    $found = [];
    foreach (scandir($dir) ?: [] as $entry) {
        $abs = $dir . '/' . $entry;
        if (preg_match('/\.mp4$/i', $entry) && is_file($abs)) {
            $found[] = ['path' => $abs, 'name' => $entry, 'size' => (int) filesize($abs)];
        }
    }
    usort($found, static fn (array $a, array $b): int => $b['size'] <=> $a['size']);
    return $found;
};

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && cms_verify_csrf($_POST['_csrf'] ?? null)) {
    $toProcess = (array) ($_POST['files'] ?? []);
    foreach ($toProcess as $basename) {
        $basename = basename((string) $basename);
        if (!preg_match('/\.mp4$/i', $basename)) {
            continue;
        }
        $srcAbs = $videoDir . '/' . $basename;
        if (!is_file($srcAbs)) {
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'File not found.'];
            continue;
        }
        if ($ffmpeg === '') {
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'FFmpeg not available on this server.'];
            continue;
        }

        $originalSize = (int) filesize($srcAbs);
        $tmpAbs = $videoDir . '/.recompress-' . bin2hex(random_bytes(6)) . '.mp4';

        $ok = cms_ffmpeg_convert($srcAbs, $tmpAbs);
        if (!$ok || !is_file($tmpAbs) || filesize($tmpAbs) < 1) {
            @unlink($tmpAbs);
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'FFmpeg conversion failed. Check server error log.'];
            continue;
        }

        $newSize = (int) filesize($tmpAbs);
        if ($newSize >= $originalSize) {
            // Already about as small as it's going to get — keep the
            // original rather than swap in a same-size-or-larger file.
            @unlink($tmpAbs);
            $results[] = [
                'file' => $basename,
                'ok' => true,
                'msg' => 'Already optimized — no change made (re-encode was not smaller).',
            ];
            continue;
        }

        if (!@rename($tmpAbs, $srcAbs)) {
            @unlink($tmpAbs);
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'Could not replace the original file.'];
            continue;
        }

        $savedPct = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100) : 0;
        $results[] = [
            'file' => $basename,
            'ok' => true,
            'msg' => round($originalSize / 1048576, 1) . ' MB → ' . round($newSize / 1048576, 1) . ' MB (' . $savedPct . '% smaller)',
        ];
    }
}

$files = $listMp4Files($videoDir);

cms_page_start('Re-compress Videos', 'videos', 'videos.manage', false, 'Shrink already-uploaded testimonial videos in place — same filename, same database rows, just smaller');
?>

<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
    <h5 class="fw-semibold mb-1">Re-compress Existing Videos</h5>
    <p class="text-muted small mb-0">Re-encodes videos already on the server with the same 1280px cap and compression the upload form now applies automatically — for videos uploaded before that existed. The filename never changes, so nothing else needs updating.</p>
  </div>
  <div class="card-body px-4 pb-4">

    <?php if ($ffmpeg === ''): ?>
    <div class="alert alert-warning border-0">
      <strong><i class="bi bi-exclamation-triangle me-2"></i>FFmpeg Not Found</strong><br>
      FFmpeg is not installed or not accessible on this server. Contact your hosting provider to install FFmpeg.
    </div>
    <?php endif; ?>

    <?php if ($results): ?>
    <div class="mb-4">
      <?php foreach ($results as $r): ?>
      <div class="alert <?= $r['ok'] ? 'alert-success' : 'alert-danger' ?> border-0 py-2 mb-2">
        <i class="bi bi-<?= $r['ok'] ? 'check-circle' : 'x-circle' ?> me-2"></i>
        <strong><?= ep_h($r['file']) ?></strong>: <?= ep_h($r['msg']) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$files): ?>
    <div class="alert alert-success border-0">
      <i class="bi bi-check-circle me-2"></i>No MP4 files found in <code>assets/video-testimonials/</code>.
    </div>
    <?php else: ?>
    <p class="text-muted small mb-3">
      Found <strong><?= count($files) ?></strong> video(s), largest first. Files over 3&nbsp;MB are pre-checked as likely candidates — pick whichever you'd like re-encoded.
    </p>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
      <div class="table-responsive mb-3">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px"><input type="checkbox" id="chkAll" class="form-check-input"></th>
              <th>Filename</th>
              <th>Size</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($files as $f): ?>
            <tr>
              <td>
                <input type="checkbox" name="files[]" value="<?= ep_h($f['name']) ?>" class="form-check-input chk-file"
                       <?= $f['size'] > $candidateBytes ? 'checked' : '' ?>>
              </td>
              <td><code class="small"><?= ep_h($f['name']) ?></code></td>
              <td class="text-muted small"><?= round($f['size'] / 1048576, 1) ?> MB</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($ffmpeg !== ''): ?>
      <div class="alert alert-info border-0 py-2 mb-3 small">
        <i class="bi bi-info-circle me-1"></i>
        Re-encoding may take 1–5 minutes per video depending on size. A file is only replaced if the result is actually smaller — nothing gets worse.
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-arrow-down-circle me-2"></i>Re-compress Selected
      </button>
      <?php else: ?>
      <button type="submit" class="btn btn-secondary" disabled>FFmpeg Required</button>
      <?php endif; ?>
    </form>
    <?php endif; ?>

  </div>
</div>

<script>
document.getElementById('chkAll')?.addEventListener('change', function () {
  document.querySelectorAll('.chk-file').forEach(function (c) { c.checked = this.checked; }, this);
});
</script>

<?php cms_page_end(); ?>
