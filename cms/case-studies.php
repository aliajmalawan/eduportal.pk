<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: case-studies.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $row = $m->getOne('ep_case_studies');
        if ($row) {
            $thumb = trim((string) ($row['thumbnail_path'] ?? ''));
            if ($thumb !== '' && str_starts_with($thumb, cms_case_study_thumbnail_dir_rel())) {
                $abs = dirname(__DIR__, 2) . '/' . ltrim($thumb, '/');
                if (is_file($abs)) @unlink($abs);
            }
            $m->where('id', $id);
            $m->delete('ep_case_studies');
        }
        cms_flash('success', 'Case study deleted.');
    } else {
        $id      = (int) ($_POST['id'] ?? 0);
        $title   = trim((string) ($_POST['title'] ?? ''));
        $slug    = trim((string) ($_POST['slug'] ?? ''));
        $status  = trim((string) ($_POST['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published'], true)) { $status = 'draft'; }

        $existingThumb = trim((string) ($_POST['thumbnail_path'] ?? ''));
        $thumbPath = cms_upload_case_study_thumbnail(
            isset($_FILES['thumbnail_file']) && is_array($_FILES['thumbnail_file']) ? $_FILES['thumbnail_file'] : null,
            $existingThumb,
            cms_slugify($title)
        );

        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        if ($publishedAt === '') {
            $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        } else {
            $publishedAt = date('Y-m-d H:i:s', strtotime($publishedAt)) ?: null;
        }

        $data = [
            'name'             => $title,
            'slug'             => $slug !== '' ? cms_slugify($slug) : cms_slugify($title),
            'thumbnail_path'   => $thumbPath,
            'video_youtube_id' => trim((string) ($_POST['video_youtube_id'] ?? '')),
            'description'      => trim((string) ($_POST['description'] ?? '')),
            'status'           => $status,
            'published_at'     => $publishedAt,
            'sort_order'       => (int) ($_POST['sort_order'] ?? 0),

            // The fields a case study actually needs: who the school is, the
            // problem before EduPortal, what was set up, what changed, and an
            // attributed quote. These existed in the table but had no editor.
            'city'             => mb_substr(trim((string) ($_POST['city'] ?? '')), 0, 120),
            'students_label'   => mb_substr(trim((string) ($_POST['students_label'] ?? '')), 0, 40),
            'school_type'      => mb_substr(trim((string) ($_POST['school_type'] ?? '')), 0, 120),
            'summary'          => trim((string) ($_POST['summary'] ?? '')),
            'challenges_html'  => trim((string) ($_POST['challenges_html'] ?? '')),
            'why_chosen_html'  => trim((string) ($_POST['why_chosen_html'] ?? '')),
            'results_html'     => trim((string) ($_POST['results_html'] ?? '')),
            'quote'            => trim((string) ($_POST['quote'] ?? '')),
            'contact_name'     => mb_substr(trim((string) ($_POST['contact_name'] ?? '')), 0, 120),
            'contact_role'     => mb_substr(trim((string) ($_POST['contact_role'] ?? '')), 0, 160),
        ];

        /**
         * Replaces a case study's metrics with the posted set. Rewriting the
         * whole list keeps ordering and deletions simple, and there is never
         * more than a handful of rows.
         */
        $saveMetrics = static function (int $caseStudyId) use ($m): void {
            $values = (array) ($_POST['metric_value'] ?? []);
            $labels = (array) ($_POST['metric_label'] ?? []);

            $m->where('case_study_id', $caseStudyId);
            $m->delete('ep_case_study_metrics');

            $order = 0;
            foreach ($values as $i => $value) {
                $value = mb_substr(trim((string) $value), 0, 40);
                $label = mb_substr(trim((string) ($labels[$i] ?? '')), 0, 160);
                if ($value === '' || $label === '') {
                    continue;   // a metric needs both a number and what it means
                }
                $m->insert('ep_case_study_metrics', [
                    'case_study_id' => $caseStudyId,
                    'metric_value'  => $value,
                    'metric_label'  => $label,
                    'sort_order'    => $order++,
                ]);
            }
        };

        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_case_studies', $data);
            $saveMetrics($id);
        } else {
            $ok = $m->insert('ep_case_studies', $data);
            if ($ok) {
                $saveMetrics((int) $ok);
            }
        }
        cms_flash($ok ? 'success' : 'error', $ok ? 'Case study saved.' : 'Save failed.');
    }
    header('Location: case-studies.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_case_studies');
}

$m->orderBy('sort_order', 'ASC');
$list = $m->get('ep_case_studies') ?: [];

$autoOpen  = cms_modal_should_open();
$thumbPath = trim((string) ($edit['thumbnail_path'] ?? ''));
$pubDate   = '';
if (!empty($edit['published_at'])) {
    $ts = strtotime((string) $edit['published_at']);
    if ($ts) $pubDate = date('Y-m-d', $ts);
}

cms_page_start('Case Studies', 'case-studies', 'case_studies.manage');
?>
<?php cms_list_card_start('All case studies', count($list ?: []), 'Add case study'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Video</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">No case studies yet. Click "Add case study" to create one.</td></tr>
      <?php endif; ?>
      <?php foreach ($list as $row):
        $hasThumb = !empty($row['thumbnail_path']);
        $hasVideo = !empty($row['video_youtube_id']);
        $pubAt    = !empty($row['published_at']) ? date('d M Y', strtotime((string) $row['published_at'])) : '—';
      ?>
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            <?php if ($hasThumb): ?>
            <img src="../<?= ep_h($row['thumbnail_path']) ?>" alt="" width="48" height="32"
                 style="object-fit:cover;border-radius:6px;border:1px solid var(--bs-border-color);flex-shrink:0">
            <?php else: ?>
            <div style="width:48px;height:32px;border-radius:6px;background:#f1f5f9;border:1px solid var(--bs-border-color);flex-shrink:0;display:flex;align-items:center;justify-content:center">
              <i class="bi bi-image text-muted" style="font-size:0.75rem"></i>
            </div>
            <?php endif; ?>
            <div>
              <div class="fw-semibold"><?= ep_h($row['name']) ?></div>
              <code class="small text-muted">/<?= ep_h($row['slug']) ?></code>
            </div>
          </div>
        </td>
        <td class="text-muted small"><?= $pubAt ?></td>
        <td>
          <?php if ($hasVideo): ?>
          <span class="badge bg-info-subtle text-info"><i class="bi bi-youtube me-1"></i>YouTube</span>
          <?php else: ?>
          <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge <?= ($row['status'] ?? '') === 'published' ? 'bg-success' : 'bg-secondary' ?>">
            <?= ep_h($row['status']) ?>
          </span>
        </td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="case-studies.php?edit=<?= (int) $row['id'] ?>">
            <i class="bi bi-pencil"></i>
          </a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this case study?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Case study', $autoOpen, 'xl', true, true); ?>
  <input type="hidden" name="_csrf"           value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id"              value="<?= (int) ($edit['id'] ?? 0) ?>">
  <input type="hidden" name="thumbnail_path"  id="csThumbPath" value="<?= ep_h($thumbPath) ?>">
  <input type="hidden" name="sort_order"      value="<?= (int) ($edit['sort_order'] ?? 0) ?>">

  <div class="ep-form-sections">

    <!-- DETAILS -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-file-earmark-text me-1"></i> Details</h6>
      <div class="row g-3">
        <div class="col-sm-8">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required
                 value="<?= ep_h($edit['name'] ?? '') ?>" placeholder="e.g. How Allied School Doubled Fee Collection">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Slug <span class="text-muted fw-normal">(auto-generated)</span></label>
          <input type="text" name="slug" class="form-control"
                 value="<?= ep_h($edit['slug'] ?? '') ?>" placeholder="leave blank to auto-generate">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Published date</label>
          <input type="date" name="published_at" class="form-control" value="<?= ep_h($pubDate) ?>">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="draft"      <?= (($edit['status'] ?? 'draft') === 'draft')      ? 'selected' : '' ?>>Draft</option>
            <option value="published"  <?= (($edit['status'] ?? '') === 'published')        ? 'selected' : '' ?>>Published — live on site</option>
          </select>
        </div>
      </div>
    </section>

    <!-- THUMBNAIL -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-image me-1"></i> Thumbnail</h6>
      <div class="ep-thumb-upload-row">
        <div id="csThumbPreview" class="ep-thumb-preview ep-thumb-preview--cms" role="img" aria-label="Thumbnail preview">
          <?php if ($thumbPath): ?>
          <img src="../<?= ep_h($thumbPath) ?>" alt="" class="ep-thumb-preview-img">
          <?php else: ?>
          <div class="ep-thumb-empty">No image yet</div>
          <?php endif; ?>
        </div>
        <div class="ep-thumb-upload-fields">
          <label class="form-label">Upload image</label>
          <input type="file" name="thumbnail_file" id="csThumbFile" class="form-control"
                 accept="image/jpeg,image/png,image/webp,image/gif">
          <p class="form-text mb-0">JPG, PNG, WebP — max 8 MB. Auto-compressed to JPEG.</p>
        </div>
      </div>
    </section>

    <!-- FEATURED VIDEO -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-play-btn me-1"></i> Featured video <span class="text-muted fw-normal">(optional)</span></h6>
      <div class="row g-3">
        <div class="col-sm-5">
          <label class="form-label">YouTube video ID</label>
          <input type="text" name="video_youtube_id" class="form-control"
                 value="<?= ep_h($edit['video_youtube_id'] ?? '') ?>"
                 placeholder="e.g. ar637Gcm3K0">
          <p class="form-text mb-0">Paste only the ID from the YouTube URL, not the full link.</p>
        </div>
        <div class="col-sm-7 d-flex align-items-end">
          <p class="form-text mb-0 text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Leave blank if there is no video for this case study.
            The video will appear embedded on the case study detail page.
          </p>
        </div>
      </div>
    </section>

    <!-- THE SCHOOL -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-building me-1"></i> The school</h6>
      <div class="row g-3">
        <div class="col-sm-4">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" maxlength="120"
                 value="<?= ep_h($edit['city'] ?? '') ?>" placeholder="e.g. Karachi, Sindh">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Student count</label>
          <input type="text" name="students_label" class="form-control" maxlength="40"
                 value="<?= ep_h($edit['students_label'] ?? '') ?>" placeholder="e.g. 1,200+">
        </div>
        <div class="col-sm-4">
          <label class="form-label">School type</label>
          <input type="text" name="school_type" class="form-control" maxlength="120"
                 value="<?= ep_h($edit['school_type'] ?? '') ?>" placeholder="e.g. Private grammar school">
        </div>
        <div class="col-12">
          <label class="form-label">One-line summary</label>
          <input type="text" name="summary" class="form-control"
                 value="<?= ep_h($edit['summary'] ?? '') ?>"
                 placeholder="Shown on the case study card and used as the meta description.">
        </div>
      </div>
    </section>

    <!-- THE STORY -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-signpost-split me-1"></i> The story</h6>
      <p class="form-text mb-2">
        These three blocks are what make a case study worth reading — and what Google and AI tools quote.
        Basic HTML is allowed (<code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>).
      </p>
      <div class="row g-3">
        <div class="col-lg-4">
          <label class="form-label">The problem before EduPortal</label>
          <textarea name="challenges_html" class="form-control" rows="7"
                    placeholder="What was not working: manual registers, fee leakage, parents calling the office…"><?= ep_h($edit['challenges_html'] ?? '') ?></textarea>
        </div>
        <div class="col-lg-4">
          <label class="form-label">What was set up</label>
          <textarea name="why_chosen_html" class="form-control" rows="7"
                    placeholder="Which modules were rolled out, in what order, and over how long."><?= ep_h($edit['why_chosen_html'] ?? '') ?></textarea>
        </div>
        <div class="col-lg-4">
          <label class="form-label">The results</label>
          <textarea name="results_html" class="form-control" rows="7"
                    placeholder="What changed after go-live, in the school's own terms."><?= ep_h($edit['results_html'] ?? '') ?></textarea>
        </div>
      </div>
    </section>

    <!-- RESULTS WITH NUMBERS -->
    <?php
    $metrics = [];
    if (!empty($edit['id'])) {
        $m->where('case_study_id', (int) $edit['id']);
        $m->orderBy('sort_order', 'ASC');
        $metrics = $m->get('ep_case_study_metrics') ?: [];
    }
    // Always offer four rows: the existing ones plus blanks to fill in.
    while (count($metrics) < 4) {
        $metrics[] = ['metric_value' => '', 'metric_label' => ''];
    }
    ?>
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-graph-up-arrow me-1"></i> Results with real numbers</h6>
      <p class="form-text mb-2">
        Shown as the highlighted figures on the case study page. Use numbers the school has actually confirmed
        — a case study with invented figures is worse than one with none. Leave a row blank to skip it.
      </p>
      <div class="row g-2">
        <?php foreach ($metrics as $metric): ?>
        <div class="col-sm-3">
          <input type="text" name="metric_value[]" class="form-control mb-1" maxlength="40"
                 value="<?= ep_h($metric['metric_value'] ?? '') ?>" placeholder="40%">
          <input type="text" name="metric_label[]" class="form-control form-control-sm" maxlength="160"
                 value="<?= ep_h($metric['metric_label'] ?? '') ?>" placeholder="Faster fee collection">
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- QUOTE -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-chat-quote me-1"></i> Administrator quote</h6>
      <p class="form-text mb-2">Only publish a quote the person has agreed to. Their name and role appear beneath it.</p>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Quote</label>
          <textarea name="quote" class="form-control" rows="3"><?= ep_h($edit['quote'] ?? '') ?></textarea>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Name</label>
          <input type="text" name="contact_name" class="form-control" maxlength="120"
                 value="<?= ep_h($edit['contact_name'] ?? '') ?>" placeholder="e.g. Mrs. Farah Shamim">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Role</label>
          <input type="text" name="contact_role" class="form-control" maxlength="160"
                 value="<?= ep_h($edit['contact_role'] ?? '') ?>" placeholder="e.g. Owner, Shamim Grammar School">
        </div>
      </div>
    </section>

    <!-- CONTENT -->
    <section class="ep-form-section">
      <h6 class="ep-form-section-title"><i class="bi bi-body-text me-1"></i> Content <span class="text-danger">*</span></h6>
      <p class="form-text mb-2">Write the full case study. Use headings, bullet lists, quotes, and tables as needed.</p>
      <textarea name="description" id="caseStudyEditor" class="form-control"
                rows="12"><?= ep_h($edit['description'] ?? '') ?></textarea>
    </section>

  </div>
<?php cms_modal_end('Save case study', 'case-studies.php'); ?>

<script src="assets/cms-case-study-editor.js"></script>
<script>
(function () {
  var fileInput = document.getElementById('csThumbFile');
  var preview   = document.getElementById('csThumbPreview');
  if (!fileInput || !preview) return;
  fileInput.addEventListener('change', function () {
    var file = fileInput.files && fileInput.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () {
      preview.innerHTML = '<img src="' + reader.result + '" alt="Preview" class="ep-thumb-preview-img">';
    };
    reader.readAsDataURL(file);
  });
})();
</script>
<?php cms_page_end(); ?>
