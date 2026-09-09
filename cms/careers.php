<?php
/**
 * Admin — Manage Jobs (Task 7.2).
 * Add, edit, duplicate, publish and delete the roles shown on /careers.
 *
 * Requirements / Responsibilities / Benefits are entered through a
 * bullet-point builder (text box + Add button, each line a deletable row) and
 * stored with json_encode() — see assets/cms-job-form.js.
 */
require_once __DIR__ . '/includes/layout.php';
global $m;

/**
 * A slug that is unique across ep_jobs, ignoring the row being edited.
 * Duplicating a job therefore always lands on "…-copy", "…-copy-2", and so on.
 */
function cms_job_unique_slug(string $desired, int $ignoreId = 0): string
{
    global $m;
    $base = substr(cms_slugify($desired) ?: 'job', 0, 170);
    $slug = $base;
    $suffix = 1;
    while (true) {
        $m->where('slug', $slug);
        if ($ignoreId > 0) {
            $m->where('id', $ignoreId, '!=');
        }
        if (!$m->getOne('ep_jobs', 'id')) {
            return $slug;
        }
        $suffix++;
        $slug = $base . '-' . $suffix;
    }
}

/**
 * Renders one bullet-point builder. The rows post as `{$field}[]`, which
 * cms_job_data_from_post() hands straight to ep_job_bullets_encode().
 */
function cms_bullet_builder(string $field, string $label, ?string $storedJson, string $placeholder): void
{
    $bullets = ep_job_bullets($storedJson);
    ?>
    <label class="form-label"><?= ep_h($label) ?></label>
    <div class="cms-bullet-builder" data-bullet-builder data-field="<?= ep_h($field) ?>">
      <div class="cms-bullet-add-row">
        <input type="text" class="form-control form-control-sm" data-bullet-input maxlength="300"
               placeholder="<?= ep_h($placeholder) ?>" aria-label="<?= ep_h($label) ?> — new point">
        <button type="button" class="btn btn-sm btn-primary flex-shrink-0" data-bullet-add>
          <i class="bi bi-plus-lg me-1"></i>Add
        </button>
      </div>
      <ul class="cms-bullet-list" data-bullet-list>
        <?php foreach ($bullets as $bullet): ?>
        <li class="cms-bullet-row" data-bullet-existing>
          <span class="cms-bullet-dot" aria-hidden="true"></span>
          <span class="cms-bullet-text"><?= ep_h($bullet) ?></span>
          <input type="hidden" name="<?= ep_h($field) ?>[]" value="<?= ep_h($bullet) ?>">
          <button type="button" class="btn btn-sm btn-outline-danger cms-bullet-delete" aria-label="Remove this point">
            <i class="bi bi-trash"></i>
          </button>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="cms-bullet-empty" data-bullet-empty<?= $bullets ? ' hidden' : '' ?>>No points added yet.</p>
    </div>
    <?php
}

/**
 * Maps the posted form to the ep_jobs columns. job_type, work_mode and status
 * are validated against the shared vocabularies in includes/careers.php rather
 * than trusted as posted; the three bullet fields arrive as arrays and are
 * stored as JSON.
 *
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function cms_job_data_from_post(array $post): array
{
    $salaryMin = (int) ($post['salary_min'] ?? 0);
    $salaryMax = (int) ($post['salary_max'] ?? 0);
    $deadline = trim((string) ($post['deadline'] ?? ''));
    $applyEmail = trim((string) ($post['apply_email'] ?? ''));

    return [
        'title' => mb_substr(trim((string) ($post['title'] ?? '')), 0, 150),
        'department' => mb_substr(trim((string) ($post['department'] ?? '')), 0, 100),
        'job_type' => ep_careers_pick((string) ($post['job_type'] ?? ''), ep_job_types(), 'Full Time'),
        'work_mode' => ep_careers_pick((string) ($post['work_mode'] ?? ''), ep_job_work_modes(), 'Onsite'),
        'location' => mb_substr(trim((string) ($post['location'] ?? '')), 0, 150),
        'salary_min' => $salaryMin > 0 ? $salaryMin : null,
        'salary_max' => $salaryMax > 0 ? $salaryMax : null,
        'experience' => mb_substr(trim((string) ($post['experience'] ?? '')), 0, 50),
        'description' => trim((string) ($post['description'] ?? '')),
        'requirements' => ep_job_bullets_encode($post['requirements'] ?? []),
        'responsibilities' => ep_job_bullets_encode($post['responsibilities'] ?? []),
        'benefits' => ep_job_bullets_encode($post['benefits'] ?? []),
        'vacancies' => max(1, min(9999, (int) ($post['vacancies'] ?? 1))),
        'deadline' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) ? $deadline : null,
        'status' => ep_careers_pick((string) ($post['status'] ?? ''), ep_job_statuses(), 'Draft'),
        'summary' => mb_substr(trim((string) ($post['summary'] ?? '')), 0, 500),
        'skills' => mb_substr(trim((string) ($post['skills'] ?? '')), 0, 500),
        'is_featured' => isset($post['is_featured']) ? 1 : 0,
        'sort_order' => (int) ($post['sort_order'] ?? 0),
        'meta_title' => mb_substr(trim((string) ($post['meta_title'] ?? '')), 0, 190),
        'meta_description' => mb_substr(trim((string) ($post['meta_description'] ?? '')), 0, 320),
        'apply_email' => filter_var($applyEmail, FILTER_VALIDATE_EMAIL) ? $applyEmail : '',
    ];
}

/** Stamps posted_at the first time a job becomes Active, then leaves it alone. */
function cms_job_posted_at(string $newStatus, ?string $existingPostedAt): ?string
{
    if ($newStatus === 'Active' && empty($existingPostedAt)) {
        return date('Y-m-d H:i:s');
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('careers.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: careers.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $m->delete('ep_jobs');
        // Applications keep their job_title_snapshot; the foreign key is
        // ON DELETE SET NULL, so no résumé is ever orphaned or lost.
        cms_flash('success', 'Job deleted. Applications received for it were kept.');
        header('Location: careers.php');
        exit;
    }

    // Inline status dropdown in the list.
    if ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = ep_careers_pick((string) ($_POST['status'] ?? ''), ep_job_statuses(), 'Draft');
        $m->where('id', $id);
        $existing = $m->getOne('ep_jobs');
        if ($existing) {
            $update = ['status' => $status];
            $postedAt = cms_job_posted_at($status, $existing['posted_at'] ?? null);
            if ($postedAt !== null) {
                $update['posted_at'] = $postedAt;
            }
            $m->where('id', $id);
            $m->update('ep_jobs', $update);
            cms_flash('success', '"' . $existing['title'] . '" is now ' . $status . '.');
        }
        header('Location: careers.php');
        exit;
    }

    if ($action === 'duplicate') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $source = $m->getOne('ep_jobs');
        if (!$source) {
            cms_flash('error', 'That job no longer exists.');
            header('Location: careers.php');
            exit;
        }
        $copy = $source;
        unset($copy['id'], $copy['created_at'], $copy['updated_at']);
        $copy['title'] = mb_substr((string) $source['title'] . ' (Copy)', 0, 150);
        $copy['slug'] = cms_job_unique_slug((string) $source['slug'] . '-copy');
        // A duplicate always starts as a Draft, so a half-edited copy can
        // never appear on the public careers page by accident.
        $copy['status'] = 'Draft';
        $copy['posted_at'] = null;
        $copy['is_featured'] = 0;
        $newId = $m->insert('ep_jobs', $copy);
        if ($newId) {
            cms_flash('success', 'Job duplicated as a draft. Edit and publish it when ready.');
            header('Location: careers.php?edit=' . (int) $newId);
        } else {
            cms_flash('error', 'Could not duplicate that job.');
            header('Location: careers.php');
        }
        exit;
    }

    // --- Create / update
    $id = (int) ($_POST['id'] ?? 0);
    $data = cms_job_data_from_post($_POST);

    if ($data['title'] === '') {
        cms_flash('error', 'A job title is required.');
        header('Location: careers.php' . ($id ? '?edit=' . $id : '?add=1'));
        exit;
    }
    if ($data['salary_min'] !== null && $data['salary_max'] !== null && $data['salary_max'] < $data['salary_min']) {
        cms_flash('error', 'The maximum salary cannot be lower than the minimum salary.');
        header('Location: careers.php' . ($id ? '?edit=' . $id : '?add=1'));
        exit;
    }

    $desiredSlug = trim((string) ($_POST['slug'] ?? '')) ?: $data['title'];
    $data['slug'] = cms_job_unique_slug($desiredSlug, $id);

    if ($id > 0) {
        $m->where('id', $id);
        $existing = $m->getOne('ep_jobs');
        $postedAt = cms_job_posted_at($data['status'], $existing['posted_at'] ?? null);
        if ($postedAt !== null) {
            $data['posted_at'] = $postedAt;
        }
        $m->where('id', $id);
        $ok = $m->update('ep_jobs', $data);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Job updated.' : 'No changes were saved.');
    } else {
        $postedAt = cms_job_posted_at($data['status'], null);
        if ($postedAt !== null) {
            $data['posted_at'] = $postedAt;
        }
        $ok = $m->insert('ep_jobs', $data);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Job created.' : 'Could not create that job.');
    }

    header('Location: careers.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_jobs');
}

$m->orderBy('is_featured', 'DESC');
$m->orderBy('sort_order', 'ASC');
$m->orderBy('id', 'DESC');
$jobs = $m->get('ep_jobs') ?: [];

// Application counts per job, in one query instead of one per row.
$counts = [];
foreach ($m->rawQuery('SELECT job_id, COUNT(*) AS c FROM ep_job_applications WHERE job_id IS NOT NULL GROUP BY job_id') ?: [] as $row) {
    $counts[(int) $row['job_id']] = (int) $row['c'];
}

$autoOpen = cms_modal_should_open();

/** Current value for a form field, falling back to the record being edited. */
$ev = static function (string $field, $default = '') use ($edit) {
    $value = $edit[$field] ?? $default;
    return $value === null ? '' : (string) $value;
};

cms_page_start('Manage Jobs', 'careers.manage', 'careers.manage', false, 'Job listings published on the public careers page');
?>

<?php cms_list_card_start('All jobs', count($jobs), 'Add job'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="min-width:230px">Title</th>
        <th>Department</th>
        <th>Work mode</th>
        <th class="text-center">Applications</th>
        <th style="min-width:130px">Status</th>
        <th class="text-end" style="min-width:170px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$jobs): ?>
      <tr><td colspan="6" class="text-center text-muted py-5">
        <i class="bi bi-briefcase fs-2 d-block mb-2 text-secondary"></i>
        No jobs yet. Add your first opening — it appears on <code>/careers</code> as soon as its status is Active.
      </td></tr>
      <?php endif; ?>

      <?php foreach ($jobs as $job):
        $expired = !empty($job['deadline']) && strtotime((string) $job['deadline']) < strtotime(date('Y-m-d'));
        $applicationCount = $counts[(int) $job['id']] ?? 0;
      ?>
      <tr>
        <td>
          <div class="fw-semibold"><?= ep_h($job['title']) ?></div>
          <div class="small text-muted">
            <code>/careers/<?= ep_h($job['slug']) ?></code>
            <?php if (!empty($job['is_featured'])): ?>
            <span class="badge bg-warning text-dark ms-1">Featured</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($job['deadline'])): ?>
          <div class="small text-muted">
            Deadline <?= ep_h(ep_format_date((string) $job['deadline'])) ?>
            <?php if ($expired): ?><span class="badge bg-danger ms-1">Expired</span><?php endif; ?>
          </div>
          <?php endif; ?>
        </td>
        <td class="small"><?= ep_h($job['department']) ?: '<span class="text-muted">—</span>' ?></td>
        <td class="small">
          <?= ep_h($job['work_mode']) ?>
          <div class="text-muted" style="font-size:.75rem">
            <?= ep_h($job['job_type']) ?><?= !empty($job['location']) ? ' · ' . ep_h($job['location']) : '' ?>
          </div>
        </td>
        <td class="text-center">
          <?php if ($applicationCount > 0): ?>
          <a href="job-applications.php?job_id=<?= (int) $job['id'] ?>" class="badge bg-primary text-decoration-none"><?= $applicationCount ?></a>
          <?php else: ?>
          <span class="text-muted small">0</span>
          <?php endif; ?>
        </td>
        <td>
          <?php // Status is editable straight from the list — no need to open the job. ?>
          <form method="post" class="d-flex gap-1">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
            <select name="status" class="form-select form-select-sm" data-inline-status aria-label="Status for <?= ep_h($job['title']) ?>">
              <?php foreach (ep_job_statuses() as $value): ?>
              <option value="<?= ep_h($value) ?>"<?= (string) $job['status'] === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
              <?php endforeach; ?>
            </select>
            <?php // Fallback for a browser without JavaScript; hidden once the
                  // inline-status script takes over the change event. ?>
            <noscript><button type="submit" class="btn btn-sm btn-outline-primary">Set</button></noscript>
          </form>
        </td>
        <td class="text-end text-nowrap">
          <?php if ((string) $job['status'] === 'Active' && !$expired): ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= ep_h(ep_job_url($job)) ?>" target="_blank" rel="noopener" title="View on site"><i class="bi bi-box-arrow-up-right"></i></a>
          <?php endif; ?>
          <a class="btn btn-sm btn-outline-primary" href="careers.php?edit=<?= (int) $job['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="duplicate">
            <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
            <button class="btn btn-sm btn-outline-info" type="submit" title="Duplicate for reposting"><i class="bi bi-files"></i></button>
          </form>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this job? Applications already received for it are kept.')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Add job', $autoOpen, 'xl', false, true); ?>
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="action" value="save">

  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Job title <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control" maxlength="150" required value="<?= ep_h($ev('title')) ?>" placeholder="e.g. Senior PHP Developer">
    </div>
    <div class="col-md-4">
      <label class="form-label">URL slug</label>
      <input type="text" name="slug" class="form-control" maxlength="180" value="<?= ep_h($ev('slug')) ?>" placeholder="auto from title">
      <div class="form-text">Public URL: <code>/careers/{slug}</code></div>
    </div>

    <div class="col-md-4">
      <label class="form-label">Department</label>
      <input type="text" name="department" class="form-control" maxlength="100" list="departmentOptions" value="<?= ep_h($ev('department')) ?>" placeholder="e.g. Engineering">
      <datalist id="departmentOptions">
        <?php foreach (ep_job_departments() as $dept): ?>
        <option value="<?= ep_h($dept) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div class="col-md-4">
      <label class="form-label">Location</label>
      <input type="text" name="location" class="form-control" maxlength="150" value="<?= ep_h($ev('location')) ?>" placeholder="e.g. Gujranwala, Punjab">
    </div>
    <div class="col-md-4">
      <label class="form-label">Work mode</label>
      <select name="work_mode" class="form-select">
        <?php foreach (ep_job_work_modes() as $value): ?>
        <option value="<?= ep_h($value) ?>"<?= $ev('work_mode', 'Onsite') === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Job type</label>
      <select name="job_type" class="form-select">
        <?php foreach (ep_job_types() as $value): ?>
        <option value="<?= ep_h($value) ?>"<?= $ev('job_type', 'Full Time') === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Experience</label>
      <input type="text" name="experience" class="form-control" maxlength="50" value="<?= ep_h($ev('experience')) ?>" placeholder="e.g. 2-4 years">
    </div>
    <div class="col-md-4">
      <label class="form-label">Vacancies</label>
      <input type="number" name="vacancies" class="form-control" min="1" max="9999" value="<?= ep_h($ev('vacancies', '1')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Skills <span class="text-muted small">(comma separated)</span></label>
      <input type="text" name="skills" class="form-control" maxlength="500" value="<?= ep_h($ev('skills')) ?>" placeholder="PHP, MySQL, JavaScript">
    </div>
    <div class="col-md-3">
      <label class="form-label">Salary min</label>
      <input type="number" name="salary_min" class="form-control" min="0" value="<?= ep_h($ev('salary_min')) ?>" placeholder="50000">
    </div>
    <div class="col-md-3">
      <label class="form-label">Salary max</label>
      <input type="number" name="salary_max" class="form-control" min="0" value="<?= ep_h($ev('salary_max')) ?>" placeholder="100000">
    </div>
    <div class="col-12">
      <div class="form-text">Salary is shown in <?= ep_h(ep_careers_salary_currency()) ?> per month, on the site and in the Google job listing. Leave both blank to hide it.</div>
    </div>

    <div class="col-12"><hr class="my-1"></div>

    <div class="col-12">
      <label class="form-label">Short summary <span class="text-muted small">(shown on the job card)</span></label>
      <input type="text" name="summary" class="form-control" maxlength="500" value="<?= ep_h($ev('summary')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Description <span class="text-muted small">(main job overview)</span></label>
      <textarea name="description" class="form-control" rows="5"><?= ep_h($ev('description')) ?></textarea>
    </div>

    <div class="col-lg-4">
      <?php cms_bullet_builder('responsibilities', 'Responsibilities', $edit['responsibilities'] ?? null, 'e.g. Build and maintain PHP modules'); ?>
    </div>
    <div class="col-lg-4">
      <?php cms_bullet_builder('requirements', 'Requirements', $edit['requirements'] ?? null, 'e.g. 3+ years of PHP'); ?>
    </div>
    <div class="col-lg-4">
      <?php cms_bullet_builder('benefits', 'Benefits', $edit['benefits'] ?? null, 'e.g. Health cover'); ?>
    </div>
    <div class="col-12">
      <div class="form-text">Type a point and press <strong>Add</strong> (or Enter). Each point becomes a row you can delete, and the list is saved as a JSON array.</div>
    </div>

    <div class="col-12"><hr class="my-1"></div>

    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php foreach (ep_job_statuses() as $value): ?>
        <option value="<?= ep_h($value) ?>"<?= $ev('status', 'Draft') === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Only <strong>Active</strong> jobs are public.</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Deadline</label>
      <input type="date" name="deadline" class="form-control" value="<?= ep_h($ev('deadline')) ?>">
      <div class="form-text">Optional — the job hides itself after this date.</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Sort order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= ep_h($ev('sort_order', '0')) ?>">
    </div>
    <div class="col-md-3 d-flex align-items-start pt-4">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="jobFeatured" name="is_featured" value="1"<?= (int) $ev('is_featured', '0') === 1 ? ' checked' : '' ?>>
        <label class="form-check-label" for="jobFeatured">Featured</label>
      </div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Notification email <span class="text-muted small">(optional)</span></label>
      <input type="email" name="apply_email" class="form-control" maxlength="190" value="<?= ep_h($ev('apply_email')) ?>" placeholder="Falls back to the careers email in Settings">
    </div>
    <div class="col-md-6">
      <label class="form-label">Meta title <span class="text-muted small">(SEO, optional)</span></label>
      <input type="text" name="meta_title" class="form-control" maxlength="190" value="<?= ep_h($ev('meta_title')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Meta description <span class="text-muted small">(SEO, optional)</span></label>
      <input type="text" name="meta_description" class="form-control" maxlength="320" value="<?= ep_h($ev('meta_description')) ?>">
    </div>
  </div>
<?php cms_modal_end('Save job', 'careers.php'); ?>

<script src="assets/cms-job-form.js"></script>

<?php cms_page_end(); ?>
