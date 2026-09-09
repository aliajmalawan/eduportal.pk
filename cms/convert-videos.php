<?php
require_once __DIR__ . '/includes/layout.php';
cms_require_super_admin();

global $m;

$ffmpeg = cms_ffmpeg_path();
$videoDir = dirname(__DIR__) . '/assets/video-testimonials';
$dirRel   = 'assets/video-testimonials/';

/**
 * .mov files, case-insensitively — glob('*.mov') only matches that exact
 * case on a case-sensitive (Linux) filesystem, and iPhones commonly export
 * with an uppercase .MOV extension, so those files were silently never
 * listed here at all.
 *
 * @return array<int, string>
 */
$findMovFiles = static function (string $dir): array {
    if (!is_dir($dir)) {
        return [];
    }
    $found = [];
    foreach (scandir($dir) ?: [] as $entry) {
        if (preg_match('/\.mov$/i', $entry) && is_file($dir . '/' . $entry)) {
            $found[] = $dir . '/' . $entry;
        }
    }
    return $found;
};

$results  = [];
$movFiles = $findMovFiles($videoDir);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && cms_verify_csrf($_POST['_csrf'] ?? null)) {
    $toConvert = (array) ($_POST['files'] ?? []);
    foreach ($toConvert as $basename) {
        $basename = basename((string) $basename);
        if (!preg_match('/\.mov$/i', $basename)) {
            continue;
        }
        $srcAbs  = $videoDir . '/' . $basename;
        if (!is_file($srcAbs)) {
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'File not found.'];
            continue;
        }
        $mp4Base = substr($basename, 0, -4) . '.mp4';
        $dstAbs  = $videoDir . '/' . $mp4Base;

        if ($ffmpeg === '') {
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'FFmpeg not available on this server.'];
            continue;
        }

        $ok = cms_ffmpeg_convert($srcAbs, $dstAbs);
        if ($ok) {
            @unlink($srcAbs);
            // Update every place that could be pointing at the old MOV path:
            // testimonial videos, and the homepage hero's demo video setting.
            $oldRel = $dirRel . $basename;
            $newRel = $dirRel . $mp4Base;
            $m->where('video_file_path', $oldRel);
            $m->update('ep_video_testimonials', ['video_file_path' => $newRel]);
            $m->where('setting_key', 'demo_video_file_path');
            $m->where('setting_value', $oldRel);
            $m->update('ep_site_settings', ['setting_value' => $newRel]);
            $results[] = ['file' => $basename, 'ok' => true, 'msg' => 'Converted → ' . $mp4Base];
        } else {
            $results[] = ['file' => $basename, 'ok' => false, 'msg' => 'FFmpeg conversion failed. Check server error log.'];
        }
    }
    // Refresh MOV list after conversion
    $movFiles = $findMovFiles($videoDir);
}

cms_page_start('Convert MOV Videos', 'videos', 'videos.manage', false, 'Convert uploaded MOV files to MP4 for browser compatibility');
?>

<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
    <h5 class="fw-semibold mb-1">MOV → MP4 Converter</h5>
    <p class="text-muted small mb-0">MOV files (especially from iPhones) use H.265 codec which Chrome cannot play. Convert them to H.264 MP4 for universal browser support.</p>
  </div>
  <div class="card-body px-4 pb-4">

    <?php if ($ffmpeg === ''): ?>
    <div class="alert alert-warning border-0">
      <strong><i class="bi bi-exclamation-triangle me-2"></i>FFmpeg Not Found</strong><br>
      FFmpeg is not installed or not accessible on this server. Contact your hosting provider to install FFmpeg, or convert the videos on your computer before uploading.<br><br>
      <strong>How to convert on your computer:</strong><br>
      1. Download <a href="https://handbrake.fr/" target="_blank" rel="noopener">HandBrake</a> (free)<br>
      2. Open each MOV file → Preset: "Web → Gmail Large 3 Minutes 720p30"<br>
      3. Export as MP4 → re-upload in the CMS
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

    <?php if (!$movFiles): ?>
    <div class="alert alert-success border-0">
      <i class="bi bi-check-circle me-2"></i>No MOV files found in <code>assets/video-testimonials/</code>. All videos are already in a compatible format.
    </div>
    <?php else: ?>
    <p class="text-muted small mb-3">Found <strong><?= count($movFiles) ?></strong> MOV file(s). Select which to convert:</p>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
      <div class="table-responsive mb-3">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px"><input type="checkbox" id="chkAll" class="form-check-input"></th>
              <th>Filename</th>
              <th>Size</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($movFiles as $f):
              $base = basename($f);
              $size = filesize($f);
              $sizeMb = $size > 0 ? round($size / 1048576, 1) . ' MB' : '?';
              $mp4Exists = is_file($videoDir . '/' . substr($base, 0, -4) . '.mp4');
            ?>
            <tr>
              <td><input type="checkbox" name="files[]" value="<?= ep_h($base) ?>" class="form-check-input chk-file" checked></td>
              <td><code class="small"><?= ep_h($base) ?></code></td>
              <td class="text-muted small"><?= $sizeMb ?></td>
              <td>
                <?php if ($mp4Exists): ?>
                <span class="badge bg-warning text-dark">MP4 already exists</span>
                <?php else: ?>
                <span class="badge bg-secondary">Pending</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($ffmpeg !== ''): ?>
      <div class="alert alert-info border-0 py-2 mb-3 small">
        <i class="bi bi-info-circle me-1"></i>
        FFmpeg found at <code><?= ep_h($ffmpeg) ?></code>. Conversion may take 1–5 minutes per video depending on file size.
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-arrow-repeat me-2"></i>Convert Selected to MP4
      </button>
      <?php else: ?>
      <button type="submit" class="btn btn-secondary" disabled>FFmpeg Required</button>
      <?php endif; ?>
    </form>
    <?php endif; ?>

  </div>
</div>

<div class="card ep-card shadow-sm border-0">
  <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
    <h5 class="fw-semibold mb-1">Manual Conversion Instructions</h5>
  </div>
  <div class="card-body px-4 pb-4">
    <p class="small text-muted mb-3">If automatic conversion is not available, convert videos manually before uploading:</p>
    <ol class="small">
      <li class="mb-2">Download <strong><a href="https://handbrake.fr/" target="_blank" rel="noopener">HandBrake</a></strong> (free, Windows/Mac/Linux)</li>
      <li class="mb-2">Open your MOV file in HandBrake</li>
      <li class="mb-2">Select Preset: <strong>Web → Gmail Large 3 Minutes 720p30</strong></li>
      <li class="mb-2">Format: <strong>MP4</strong>, Video Codec: <strong>H.264</strong></li>
      <li class="mb-2">Click <strong>Start Encode</strong></li>
      <li>Delete the old MOV video in CMS → re-upload the new MP4</li>
    </ol>
  </div>
</div>

<script>
document.getElementById('chkAll')?.addEventListener('change', function () {
  document.querySelectorAll('.chk-file').forEach(function (c) { c.checked = this.checked; }, this);
});
</script>

<?php cms_page_end(); ?>
