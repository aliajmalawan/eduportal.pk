<?php
/**
 * Admin — Job Applications (Task 7).
 * Filter, review, download résumés, set status, and keep internal HR notes.
 */
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/careers-admin.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('applications.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: job-applications.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $redirect = 'job-applications.php' . (string) ($_POST['return_query'] ?? '');

    if ($action === 'update' && $id > 0) {
        $m->where('id', $id);
        $ok = $m->update('ep_job_applications', [
            'status' => ep_careers_pick((string) ($_POST['status'] ?? ''), ep_application_statuses(), 'New'),
            'admin_notes' => mb_substr(trim((string) ($_POST['admin_notes'] ?? '')), 0, 5000),
        ]);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Application updated.' : 'No changes were saved.');
    } elseif ($action === 'status' && $id > 0) {
        // Inline status dropdown in the list — never touches the notes.
        $status = ep_careers_pick((string) ($_POST['status'] ?? ''), ep_application_statuses(), 'New');
        $m->where('id', $id);
        $m->update('ep_job_applications', ['status' => $status]);
        cms_flash('success', 'Application marked as ' . $status . '.');
    } elseif ($action === 'delete' && $id > 0) {
        $m->where('id', $id);
        $row = $m->getOne('ep_job_applications');
        if ($row) {
            // Remove the stored files too — a deleted application must not
            // leave someone's résumé and photo sitting on disk.
            ep_careers_delete_file((string) ($row['resume'] ?? ''));
            ep_careers_delete_file((string) ($row['photo'] ?? ''));
            $m->where('id', $id);
            $m->delete('ep_job_applications');
            cms_flash('success', 'Application deleted along with its résumé and photo.');
        }
    }

    header('Location: ' . $redirect);
    exit;
}

$filters = cms_application_filters($_GET);
$filterQuery = cms_application_filter_query($filters);
$applications = cms_get_applications($filters, 500);
$counts = cms_application_status_counts();
$totalAll = array_sum($counts);

$m->orderBy('title', 'ASC');
$allJobs = $m->get('ep_jobs', null, 'id, title') ?: [];

// Detail view for a single application (?view=ID).
$viewing = null;
if (isset($_GET['view'])) {
    $rows = $m->rawQuery(
        'SELECT a.*, j.slug AS job_slug, j.title AS job_current_title
         FROM ep_job_applications a LEFT JOIN ep_jobs j ON j.id = a.job_id
         WHERE a.id = ?',
        [(int) $_GET['view']]
    ) ?: [];
    $viewing = $rows[0] ?? null;
}

cms_page_start('Job Applications', 'applications.manage', 'applications.manage', false, 'Applications and résumés received from the careers page');
?>

<!-- Status summary -->
<div class="row g-3 mb-4">
  <?php foreach (ep_application_statuses() as $statusKey):
    $count = $counts[$statusKey];
    $pct = $totalAll > 0 ? round(($count / $totalAll) * 100) : 0;
  ?>
  <div class="col-6 col-lg">
    <a class="text-decoration-none" href="job-applications.php<?= ep_h(cms_application_filter_query($filters, ['status' => $filters['status'] === $statusKey ? '' : $statusKey])) ?>">
      <div class="card ep-card shadow-sm border-0 h-100<?= $filters['status'] === $statusKey ? ' border border-primary' : '' ?>">
        <div class="card-body px-3 py-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="ep-stat-label"><?= ep_h($statusKey) ?></span>
            <span class="badge <?= ep_h(cms_application_status_badge($statusKey)) ?> badge-status"><?= $count ?></span>
          </div>
          <div class="ep-stat-value" style="font-size:1.5rem"><?= $pct ?>%</div>
          <div class="ep-pipeline-bar mt-1">
            <div class="ep-pipeline-bar-fill <?= ep_h(cms_application_status_badge($statusKey)) ?>" style="width:<?= $pct ?>%"></div>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-body px-4 py-3">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">Search</label>
        <input type="search" name="q" class="form-control form-control-sm" value="<?= ep_h($filters['q']) ?>" placeholder="Name, email, WhatsApp, city" maxlength="100">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Position</label>
        <select name="job_id" class="form-select form-select-sm">
          <option value="">All positions</option>
          <?php foreach ($allJobs as $job): ?>
          <option value="<?= (int) $job['id'] ?>"<?= $filters['job_id'] === (int) $job['id'] && $filters['general'] !== '1' ? ' selected' : '' ?>><?= ep_h($job['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All statuses</option>
          <?php foreach (ep_application_statuses() as $value): ?>
          <option value="<?= ep_h($value) ?>"<?= $filters['status'] === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= ep_h($filters['from']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= ep_h($filters['to']) ?>">
      </div>
      <div class="col-12 d-flex flex-wrap gap-2 align-items-center pt-2">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
        <a href="job-applications.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        <div class="form-check ms-2">
          <input type="checkbox" class="form-check-input" id="generalOnly" name="general" value="1"<?= $filters['general'] === '1' ? ' checked' : '' ?>>
          <label class="form-check-label small" for="generalOnly">General CVs only</label>
        </div>
        <a class="btn btn-sm btn-success ms-auto" href="export-applications.php<?= ep_h($filterQuery) ?>">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel
        </a>
      </div>
    </form>
  </div>
</div>

<?php if ($viewing): ?>
<?php
  $waUrl = cms_application_whatsapp_url($viewing, cms_application_whatsapp_message($viewing));
  $photoUrl = trim((string) $viewing['photo']) !== ''
      ? 'application-file.php?id=' . (int) $viewing['id'] . '&type=photo'
      : '';
?>
<div class="card ep-card shadow-sm border-0 mb-4 border-start border-4 border-primary">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="d-flex align-items-center gap-3">
      <?php if ($photoUrl !== ''): ?>
      <img src="<?= ep_h($photoUrl) ?>" alt="Photo of <?= ep_h($viewing['full_name']) ?>" width="56" height="56" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:1px solid #dee2e6">
      <?php else: ?>
      <span class="ep-avatar" style="width:56px;height:56px;font-size:1.25rem"><?= ep_h(strtoupper(mb_substr((string) $viewing['full_name'], 0, 1))) ?></span>
      <?php endif; ?>
      <div>
        <h5 class="mb-0 fw-semibold"><?= ep_h($viewing['full_name']) ?></h5>
        <div class="small text-muted">
          Applied for <strong><?= ep_h($viewing['job_title_snapshot'] ?: 'General Application') ?></strong>
          on <?= ep_h(ep_format_date((string) $viewing['applied_at'], 'M j, Y \a\t H:i')) ?>
        </div>
      </div>
    </div>
    <a href="job-applications.php<?= ep_h($filterQuery) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Close</a>
  </div>
  <div class="card-body px-4 py-4">
    <div class="row g-4">
      <div class="col-lg-7">
        <dl class="row mb-0 small">
          <dt class="col-sm-4 text-muted fw-normal">Email</dt>
          <dd class="col-sm-8"><a href="mailto:<?= ep_h($viewing['email']) ?>"><?= ep_h($viewing['email']) ?></a></dd>

          <dt class="col-sm-4 text-muted fw-normal">WhatsApp</dt>
          <dd class="col-sm-8">
            <?= ep_h($viewing['whatsapp'] ?: '—') ?>
            <?php if ($waUrl !== ''): ?>
            <a href="<?= ep_h($waUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success ms-2 py-0 px-2">
              <i class="bi bi-whatsapp"></i> Chat
            </a>
            <?php endif; ?>
          </dd>

          <?php foreach ([
            'City' => $viewing['city'],
            'Years of experience' => $viewing['years_experience'],
            'Expected salary' => $viewing['expected_salary'] !== null && $viewing['expected_salary'] !== ''
                ? ep_careers_salary_currency() . ' ' . number_format((int) $viewing['expected_salary']) . ' / month'
                : '',
            'Can join' => $viewing['joining_time'],
          ] as $label => $value):
            if (trim((string) $value) === '') { continue; }
          ?>
          <dt class="col-sm-4 text-muted fw-normal"><?= ep_h($label) ?></dt>
          <dd class="col-sm-8"><?= ep_h((string) $value) ?></dd>
          <?php endforeach; ?>

          <?php if (trim((string) $viewing['linkedin']) !== ''): ?>
          <dt class="col-sm-4 text-muted fw-normal">LinkedIn</dt>
          <dd class="col-sm-8"><a href="<?= ep_h((string) $viewing['linkedin']) ?>" target="_blank" rel="noopener noreferrer"><?= ep_h((string) $viewing['linkedin']) ?></a></dd>
          <?php endif; ?>

          <dt class="col-sm-4 text-muted fw-normal">Submitted from</dt>
          <dd class="col-sm-8 text-muted"><?= ep_h($viewing['source_page'] ?: '—') ?> · IP <?= ep_h($viewing['ip_address'] ?: '—') ?></dd>
        </dl>

        <?php foreach ([
          'Relevant experience' => $viewing['relevant_experience'],
          'Cover letter' => $viewing['cover_letter'],
        ] as $label => $text):
          if (trim((string) $text) === '') { continue; }
        ?>
        <div class="mt-4">
          <h6 class="fw-semibold"><?= ep_h($label) ?></h6>
          <div class="border rounded p-3 bg-light small" style="white-space:pre-wrap"><?= ep_h((string) $text) ?></div>
        </div>
        <?php endforeach; ?>

        <?php $extraFields = ep_careers_extra_fields_display($viewing); ?>
        <?php if ($extraFields): ?>
        <div class="mt-4">
          <h6 class="fw-semibold">Additional information</h6>
          <dl class="row mb-0 small">
            <?php foreach ($extraFields as $label => $value): ?>
            <dt class="col-sm-4 text-muted fw-normal"><?= ep_h($label) ?></dt>
            <dd class="col-sm-8" style="white-space:pre-wrap"><?= ep_h($value) ?></dd>
            <?php endforeach; ?>
          </dl>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-5">
        <div class="border rounded p-3 mb-3">
          <h6 class="fw-semibold mb-2">Attachments</h6>
          <?php if (trim((string) $viewing['resume']) !== ''): ?>
          <a class="btn btn-sm btn-primary w-100 mb-2" href="application-file.php?id=<?= (int) $viewing['id'] ?>&amp;type=resume">
            <i class="bi bi-download me-1"></i>Download résumé (<?= ep_h(cms_format_bytes((int) $viewing['resume_size'])) ?>)
          </a>
          <a class="btn btn-sm btn-outline-secondary w-100" href="application-file.php?id=<?= (int) $viewing['id'] ?>&amp;type=resume&amp;inline=1" target="_blank" rel="noopener">
            <i class="bi bi-eye me-1"></i>Open résumé in browser
          </a>
          <div class="form-text mt-2"><?= ep_h($viewing['resume_original_name']) ?></div>
          <?php else: ?>
          <p class="text-muted small mb-0">No résumé on file.</p>
          <?php endif; ?>
        </div>

        <form method="post" class="border rounded p-3">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int) $viewing['id'] ?>">
          <input type="hidden" name="return_query" value="<?= ep_h(cms_application_filter_query($filters, ['view' => (int) $viewing['id']])) ?>">

          <h6 class="fw-semibold mb-2">Status &amp; internal notes</h6>
          <select name="status" class="form-select form-select-sm mb-2">
            <?php foreach (ep_application_statuses() as $value): ?>
            <option value="<?= ep_h($value) ?>"<?= (string) $viewing['status'] === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
            <?php endforeach; ?>
          </select>
          <textarea name="admin_notes" class="form-control form-control-sm mb-2" rows="5" maxlength="5000" placeholder="Interview feedback, screening notes… (internal only, never shown to the applicant)"><?= ep_h((string) ($viewing['admin_notes'] ?? '')) ?></textarea>
          <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Save</button>
        </form>

        <form method="post" class="mt-2" onsubmit="return confirm('Delete this application permanently, including the résumé and photo?')">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $viewing['id'] ?>">
          <input type="hidden" name="return_query" value="<?= ep_h($filterQuery) ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Delete application</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Applications table -->
<div class="card ep-card shadow-sm border-0">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">Applications</h5>
    <small class="text-muted"><?= count($applications) ?> shown<?= $totalAll > count($applications) ? ' of ' . $totalAll . ' total' : '' ?></small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:56px"></th>
            <th style="min-width:170px">Name</th>
            <th style="min-width:160px">Job applied for</th>
            <th>Experience</th>
            <th>Expected salary</th>
            <th>Joining time</th>
            <th style="min-width:110px">Date</th>
            <th style="min-width:140px">Status</th>
            <th class="text-end" style="min-width:90px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$applications): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
            No applications match these filters.
          </td></tr>
          <?php endif; ?>

          <?php foreach ($applications as $app):
            $status = (string) $app['status'];
            $waUrl = cms_application_whatsapp_url($app, cms_application_whatsapp_message($app));
            $isGeneral = $app['job_id'] === null;
            $detailUrl = 'job-applications.php' . cms_application_filter_query($filters, ['view' => (int) $app['id']]);
            $hasPhoto = trim((string) $app['photo']) !== '';
          ?>
          <?php // The whole row opens the detail view; links and controls
                // inside it keep their own behaviour (see cms-job-form.js). ?>
          <tr data-row-href="<?= ep_h($detailUrl) ?>" tabindex="0">
            <td>
              <?php if ($hasPhoto): ?>
              <img class="cms-app-photo" src="application-file.php?id=<?= (int) $app['id'] ?>&amp;type=photo" alt="" width="38" height="38" loading="lazy">
              <?php else: ?>
              <span class="cms-app-photo-fallback" aria-hidden="true"><?= ep_h(strtoupper(mb_substr((string) $app['full_name'], 0, 1))) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= ep_h($app['full_name']) ?></div>
              <?php if ($waUrl !== ''): ?>
              <a href="<?= ep_h($waUrl) ?>" target="_blank" rel="noopener" class="text-decoration-none text-success small fw-medium">
                <i class="bi bi-whatsapp me-1"></i><?= ep_h($app['whatsapp']) ?>
              </a>
              <?php else: ?>
              <div class="small text-muted"><?= ep_h($app['email']) ?></div>
              <?php endif; ?>
            </td>
            <td class="small">
              <?php if ($isGeneral): ?>
              <span class="badge bg-light text-dark border">General CV</span>
              <?php else: ?>
              <?= ep_h($app['job_title_snapshot']) ?>
              <?php endif; ?>
            </td>
            <td class="small text-nowrap"><?= ep_h($app['years_experience']) ?: '<span class="text-muted">—</span>' ?></td>
            <td class="small text-nowrap">
              <?= $app['expected_salary'] !== null && $app['expected_salary'] !== ''
                    ? ep_h(number_format((int) $app['expected_salary']))
                    : '<span class="text-muted">—</span>' ?>
            </td>
            <td class="small text-nowrap"><?= ep_h($app['joining_time']) ?: '<span class="text-muted">—</span>' ?></td>
            <td class="text-muted small text-nowrap">
              <?= ep_h(ep_format_date((string) $app['applied_at'], 'M j, Y')) ?><br>
              <span style="font-size:.7rem"><?= ep_h(ep_format_date((string) $app['applied_at'], 'H:i')) ?></span>
            </td>
            <td>
              <?php // Status is editable straight from the list. ?>
              <form method="post" class="d-flex gap-1">
                <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                <input type="hidden" name="return_query" value="<?= ep_h($filterQuery) ?>">
                <select name="status" class="form-select form-select-sm" data-inline-status aria-label="Status for <?= ep_h($app['full_name']) ?>">
                  <?php foreach (ep_application_statuses() as $value): ?>
                  <option value="<?= ep_h($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= ep_h($value) ?></option>
                  <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-outline-primary">Set</button></noscript>
              </form>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-primary" href="<?= ep_h($detailUrl) ?>" title="Open full details">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="assets/cms-job-form.js"></script>

<?php cms_page_end(); ?>
