<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: videos.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $row = $m->getOne('ep_video_testimonials');
        if ($row) {
            cms_delete_public_file($row['thumbnail_path'] ?? '', cms_thumbnail_dir_rel());
            cms_delete_public_file($row['video_file_path'] ?? '', cms_video_files_dir_rel());
            $m->where('id', $id);
            $m->delete('ep_video_testimonials');
        }
        cms_flash('success', 'Video deleted.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $personName = trim((string) ($_POST['person_name'] ?? ''));
        $slugHint = cms_slugify($personName);
        $videoType = ($_POST['video_type'] ?? 'youtube') === 'upload' ? 'upload' : 'youtube';
        $existingThumb = trim((string) ($_POST['thumbnail_path'] ?? ''));
        $existingVideo = trim((string) ($_POST['video_file_path'] ?? ''));
        $thumbPath = cms_upload_video_thumbnail(
            isset($_FILES['thumbnail_file']) && is_array($_FILES['thumbnail_file']) ? $_FILES['thumbnail_file'] : null,
            $existingThumb,
            $slugHint
        );
        $videoPath = $existingVideo;
        $videoFileInput = isset($_FILES['video_file']) && is_array($_FILES['video_file']) ? $_FILES['video_file'] : null;
        $newVideoUploaded = $videoFileInput
            && ($videoFileInput['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && is_uploaded_file((string) ($videoFileInput['tmp_name'] ?? ''));
        if ($videoType === 'upload' || $newVideoUploaded || $existingVideo !== '') {
            $videoType = 'upload';
            $videoPath = cms_upload_video_file($videoFileInput, $existingVideo, $slugHint);
        }
        $designation = trim((string) ($_POST['designation'] ?? ''));
        $roleFilter = trim((string) ($_POST['role_filter'] ?? 'principal'));
        if (!in_array($roleFilter, ['principal', 'owner', 'director', 'admin', 'other'], true)) {
            $roleFilter = 'other';
        }
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $data = [
            'person_name' => $personName,
            'designation' => $designation,
            'person_title' => '',
            'school_name' => trim((string) ($_POST['school_name'] ?? '')),
            'role_filter' => $roleFilter,
            'video_type' => $videoType,
            'youtube_video_id' => $videoType === 'youtube' ? trim((string) ($_POST['youtube_video_id'] ?? '')) : '',
            'youtube_start_seconds' => (int) ($_POST['youtube_start_seconds'] ?? 0),
            'thumbnail_path' => $thumbPath,
            'video_file_path' => $videoType === 'upload' ? $videoPath : '',
            'description' => trim((string) ($_POST['description'] ?? '')),
            'show_on_homepage' => isset($_POST['show_on_homepage']) ? 1 : 0,
            'homepage_sort_order' => (int) ($_POST['homepage_sort_order'] ?? 0),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status' => $status,
        ];

        if ($videoType === 'youtube' && $data['youtube_video_id'] === '') {
            cms_flash('error', 'YouTube video ID is required for YouTube videos.');
            header('Location: videos.php' . ($id ? '?edit=' . $id : ''));
            exit;
        }
        if ($videoType === 'upload' && $data['video_file_path'] === '') {
            cms_flash('error', 'Please upload a video file (MP4, WebM, or MOV — max 200 MB).');
            header('Location: videos.php' . ($id ? '?edit=' . $id : ''));
            exit;
        }

        if ($data['video_file_path'] !== '') {
            $data['video_file_path'] = ep_normalize_public_path($data['video_file_path'], 'assets/video-testimonials');
            $data['video_type'] = 'upload';
            $data['youtube_video_id'] = '';
        }

        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_video_testimonials', $data);
            cms_flash($ok ? 'success' : 'error', $ok ? 'Video updated.' : 'No changes saved.');
        } else {
            $ok = $m->insert('ep_video_testimonials', $data);
            cms_flash($ok ? 'success' : 'error', $ok ? 'Video added.' : 'Could not save video.');
        }
    }
    header('Location: videos.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_video_testimonials');
}

// Same order the public /videos.php page uses — see ep_sort_by_display_order() in cms.php.
$rows = $m->get('ep_video_testimonials');
$rows = ep_sort_by_display_order($rows);

$autoOpen = cms_modal_should_open();
$thumbPath = trim((string) ($edit['thumbnail_path'] ?? ''));
$videoPath = ep_normalize_public_path((string) ($edit['video_file_path'] ?? ''), 'assets/video-testimonials');
$videoType = $videoPath !== '' ? 'upload' : ((($edit['video_type'] ?? 'youtube') === 'upload') ? 'upload' : 'youtube');
$designation = trim((string) ($edit['designation'] ?? ''));
if ($designation === '' && $edit) {
    $designation = trim((string) ($edit['person_title'] ?? ''));
    if ($designation === '') {
        $designation = ep_video_role_label((string) ($edit['role_filter'] ?? ''));
    }
}

cms_page_start('Video Testimonials', 'videos', 'videos.manage');
?>
<?php cms_list_card_start('All videos', count($rows), 'Add video'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Person</th><th style="width:90px">Thumbnail</th><th>Role</th><th>Type</th><th>Status</th><th class="text-center">Homepage</th><th class="text-center" style="width:70px" title="Position this video actually appears in on the public /videos page">Order</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No videos yet.</td></tr><?php endif; ?>
      <?php $position = 0; ?>
      <?php foreach ($rows as $r):
        $position++;
        $vType = ($r['video_type'] ?? 'youtube') === 'upload' ? 'Upload' : 'YouTube';
        $roleLabel = ep_video_designation($r);
        $rThumb = trim((string) ($r['thumbnail_path'] ?? ''));
      ?>
      <tr>
        <td>
          <div class="fw-semibold"><?= ep_h($r['person_name']) ?></div>
          <?php if (!empty($r['school_name'])): ?><div class="small text-muted"><?= ep_h($r['school_name']) ?></div><?php endif; ?>
        </td>
        <td>
          <?php if ($rThumb !== ''): ?>
            <img src="../<?= ep_h($rThumb) ?>" alt="" style="height:44px;width:auto;border-radius:6px;object-fit:cover;border:1px solid #dee2e6">
          <?php else: ?>
            <span class="badge bg-warning text-dark" title="No thumbnail">Missing</span>
          <?php endif; ?>
        </td>
        <td><?= ep_h($roleLabel) ?></td>
        <td><span class="badge bg-info-subtle text-info"><?= ep_h($vType) ?></span></td>
        <td><span class="badge <?= ($r['status'] ?? '') === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= ep_h($r['status']) ?></span></td>
        <td class="text-center">
          <?php if ((int) ($r['show_on_homepage'] ?? 0) === 1): ?>
          <span class="badge bg-warning text-dark" title="Showing on homepage"><i class="bi bi-house-fill me-1"></i>Yes</span>
          <?php else: ?>
          <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <span class="badge bg-light text-dark border">#<?= (int) $position ?></span>
          <?php if ((int) ($r['sort_order'] ?? 0) !== 0): ?>
          <div class="small text-muted mt-1">sort_order: <?= (int) $r['sort_order'] ?></div>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="videos.php?edit=<?= (int) $r['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete video?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Video testimonial', $autoOpen, 'lg', true, true); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <input type="hidden" name="thumbnail_path" id="videoThumbPath" value="<?= ep_h($thumbPath) ?>">
  <input type="hidden" name="video_file_path" id="videoFilePath" value="<?= ep_h($videoPath) ?>">
  <input type="hidden" name="homepage_sort_order" value="<?= (int) ($edit['homepage_sort_order'] ?? 0) ?>">

  <div class="ep-form-sections">
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-person me-1"></i> Person details</h6>
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label">Person name <span class="text-danger">*</span></label>
          <input type="text" name="person_name" class="form-control" required value="<?= ep_h($edit['person_name'] ?? '') ?>" placeholder="Full name">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Role / designation <span class="text-danger">*</span></label>
          <input type="text" name="designation" class="form-control" required value="<?= ep_h($designation) ?>" placeholder="e.g. Principal">
        </div>
        <div class="col-sm-6">
          <label class="form-label">School <span class="text-muted fw-normal">(optional)</span></label>
          <input type="text" name="school_name" class="form-control" value="<?= ep_h($edit['school_name'] ?? '') ?>" placeholder="School name">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Filter on videos page</label>
          <select name="role_filter" class="form-select">
            <?php foreach (['principal' => 'Principals', 'owner' => 'Owners', 'director' => 'Directors', 'admin' => 'Administrators', 'other' => 'Other'] as $val => $label): ?>
            <option value="<?= $val ?>"<?= (($edit['role_filter'] ?? 'principal') === $val) ? ' selected' : '' ?>><?= ep_h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Testimonial quote <span class="text-muted fw-normal">(optional)</span></label>
          <textarea name="description" class="form-control" rows="3" placeholder="A short written excerpt of what they said in the video — shown as text next to the video so it's readable without playing it."><?= ep_h($edit['description'] ?? '') ?></textarea>
        </div>
      </div>
    </section>

    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-image me-1"></i> Thumbnail</h6>
      <div class="ep-thumb-upload-row">
        <div id="videoThumbPreview" class="ep-thumb-preview ep-thumb-preview--cms" role="img" aria-label="Thumbnail preview">
          <?php if ($thumbPath): ?>
          <img src="../<?= ep_h($thumbPath) ?>" alt="" class="ep-thumb-preview-img">
          <?php else: ?>
          <div class="ep-thumb-empty">Preview appears here</div>
          <?php endif; ?>
        </div>
        <div class="ep-thumb-upload-fields">
          <label class="form-label">Upload image</label>
          <input type="file" name="thumbnail_file" id="videoThumbFile" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
          <p class="form-text mb-0">Auto-compressed under 50 KB (JPG, PNG, WebP, GIF).</p>
        </div>
      </div>
    </section>

    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-play-btn me-1"></i> Video source</h6>
      <label class="form-label">How is this video provided?</label>
      <select name="video_type" id="videoTypeSelect" class="form-select">
        <option value="youtube"<?= $videoType === 'youtube' ? ' selected' : '' ?>>YouTube link</option>
        <option value="upload"<?= $videoType === 'upload' ? ' selected' : '' ?>>Upload from computer</option>
      </select>

      <div id="videoFieldsYoutube" class="ep-video-source-panel mt-3">
        <div class="row g-3">
          <div class="col-sm-8">
            <label class="form-label">YouTube video ID</label>
            <input type="text" name="youtube_video_id" class="form-control" value="<?= ep_h($edit['youtube_video_id'] ?? '') ?>" placeholder="Paste ID from YouTube URL">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Start at (sec)</label>
            <input type="number" min="0" name="youtube_start_seconds" class="form-control" value="<?= ep_h((string) ($edit['youtube_start_seconds'] ?? 0)) ?>">
          </div>
        </div>
      </div>

      <div id="videoFieldsUpload" class="ep-video-source-panel mt-3 d-none">
        <?php if ($videoPath): ?>
        <p class="small text-success mb-2"><i class="bi bi-check-circle me-1"></i> Current file: <?= ep_h(basename($videoPath)) ?></p>
        <?php endif; ?>
        <label class="form-label">Video file</label>
        <input type="file" name="video_file" id="videoFileInput" class="form-control" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
        <p class="form-text mb-0">MP4, WebM, or MOV — maximum 200 MB.</p>
      </div>
    </section>

    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-send me-1"></i> Publishing</h6>
      <div class="row g-3 align-items-center">
        <div class="col-sm-5">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="draft"<?= (($edit['status'] ?? 'draft') === 'draft') ? ' selected' : '' ?>>Draft</option>
            <option value="published"<?= (($edit['status'] ?? '') === 'published') ? ' selected' : '' ?>>Published — live on site</option>
          </select>
        </div>
        <div class="col-sm-7">
          <div class="form-check ep-form-check-card">
            <input class="form-check-input" type="checkbox" name="show_on_homepage" id="vid_home" <?= !isset($edit['show_on_homepage']) || (int) $edit['show_on_homepage'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="vid_home">Also show on homepage testimonials section</label>
          </div>
        </div>
        <div class="col-sm-5">
          <label class="form-label">Display order on /videos page</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) ($edit['sort_order'] ?? 0) ?>" placeholder="0">
          <p class="form-text mb-0">1 shows first, 2 shows second, and so on. Leave at 0 to let the video fall in naturally (newest-added first) after any numbered ones.</p>
        </div>
      </div>
    </section>
  </div>
<?php cms_modal_end('Save video', 'videos.php'); ?>

<script src="assets/cms-video-form.js"></script>
<?php cms_page_end(); ?>
