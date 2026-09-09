<?php
/**
 * Admin — Comparison pages (Task 8).
 *
 * These pages make factual claims about other companies, so this editor is
 * deliberately stricter than the others: a comparison cannot be set Active
 * unless it has a source, a verification date, and at least one row where the
 * competitor is rated better. That last rule is the point of the whole
 * exercise — a table that concedes nothing gets read as advertising.
 */
require_once __DIR__ . '/includes/layout.php';
global $m;

/** @return array<int, array<string, mixed>> */
function cms_comparison_rows(int $comparisonId): array
{
    global $m;
    if ($comparisonId < 1) {
        return [];
    }
    $m->where('comparison_id', $comparisonId);
    $m->orderBy('sort_order', 'ASC');
    $m->orderBy('id', 'ASC');
    return $m->get('ep_comparison_rows') ?: [];
}

/**
 * Rewrites a comparison's table rows from the posted arrays.
 * Returns the rows as saved, so the caller can check balance.
 *
 * @return array<int, array<string, string>>
 */
function cms_save_comparison_rows(int $comparisonId, array $post): array
{
    global $m;

    $labels = (array) ($post['row_label'] ?? []);
    $sections = (array) ($post['row_section'] ?? []);
    $ours = (array) ($post['row_ours'] ?? []);
    $theirs = (array) ($post['row_theirs'] ?? []);
    $adv = (array) ($post['row_advantage'] ?? []);
    $notes = (array) ($post['row_note'] ?? []);

    $m->where('comparison_id', $comparisonId);
    $m->delete('ep_comparison_rows');

    $saved = [];
    $order = 0;
    foreach ($labels as $i => $label) {
        $label = mb_substr(trim((string) $label), 0, 190);
        if ($label === '') {
            continue;   // a row without a feature label is not a row
        }
        $row = [
            'comparison_id'    => $comparisonId,
            'section'          => mb_substr(trim((string) ($sections[$i] ?? '')), 0, 80),
            'feature_label'    => $label,
            'eduportal_value'  => trim((string) ($ours[$i] ?? '')),
            'competitor_value' => trim((string) ($theirs[$i] ?? '')),
            'advantage'        => ep_careers_pick((string) ($adv[$i] ?? 'tie'), ep_comparison_advantages(), 'tie'),
            'note'             => mb_substr(trim((string) ($notes[$i] ?? '')), 0, 400),
            'sort_order'       => $order++,
        ];
        $m->insert('ep_comparison_rows', $row);
        $saved[] = $row;
    }

    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('comparisons.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: comparisons.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete' && $id > 0) {
        $m->where('id', $id);
        $m->delete('ep_comparisons');   // rows cascade
        cms_flash('success', 'Comparison deleted.');
        header('Location: comparisons.php');
        exit;
    }

    $verifiedAt = trim((string) ($_POST['verified_at'] ?? ''));
    $sourceUrl = trim((string) ($_POST['source_url'] ?? ''));
    $requested = ep_careers_pick((string) ($_POST['status'] ?? 'Draft'), ['Draft', 'Active'], 'Draft');

    $data = [
        'competitor_name'     => mb_substr(trim((string) ($_POST['competitor_name'] ?? '')), 0, 120),
        'competitor_summary'  => trim((string) ($_POST['competitor_summary'] ?? '')),
        'intro_html'          => trim((string) ($_POST['intro_html'] ?? '')),
        'verdict_html'        => trim((string) ($_POST['verdict_html'] ?? '')),
        'where_they_win_html' => trim((string) ($_POST['where_they_win_html'] ?? '')),
        'source_url'          => filter_var($sourceUrl, FILTER_VALIDATE_URL) ? mb_substr($sourceUrl, 0, 500) : '',
        'verified_at'         => preg_match('/^\d{4}-\d{2}-\d{2}$/', $verifiedAt) ? $verifiedAt : null,
        'meta_title'          => mb_substr(trim((string) ($_POST['meta_title'] ?? '')), 0, 190),
        'meta_description'    => mb_substr(trim((string) ($_POST['meta_description'] ?? '')), 0, 320),
        'sort_order'          => (int) ($_POST['sort_order'] ?? 0),
    ];

    if ($data['competitor_name'] === '') {
        cms_flash('error', 'A competitor name is required.');
        header('Location: comparisons.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }

    if ($id < 1) {
        $data['slug'] = 'eduportal-vs-' . cms_slugify($data['competitor_name']);
        $data['status'] = 'Draft';
        $id = (int) $m->insert('ep_comparisons', $data);
        if ($id < 1) {
            cms_flash('error', 'Could not create that comparison.');
            header('Location: comparisons.php');
            exit;
        }
    }

    $rows = cms_save_comparison_rows($id, $_POST);

    // Publishing gate. Each condition is reported separately so the admin
    // knows exactly what is missing rather than getting a generic refusal.
    $blockers = [];
    if ($requested === 'Active') {
        if ($data['source_url'] === '') {
            $blockers[] = 'a source URL for the competitor information';
        }
        if ($data['verified_at'] === null) {
            $blockers[] = 'the date you last checked those details';
        }
        if (!$rows) {
            $blockers[] = 'at least one comparison row';
        } elseif (!ep_comparison_is_balanced($rows)) {
            $blockers[] = 'at least one row where ' . $data['competitor_name'] . ' is rated better — a one-sided table reads as advertising';
        }
        if (trim((string) $data['where_they_win_html']) === '') {
            $blockers[] = 'the "where they are better" section';
        }
    }

    $data['status'] = $blockers ? 'Draft' : $requested;

    $m->where('id', $id);
    $m->update('ep_comparisons', $data);

    if ($blockers) {
        cms_flash('error', 'Saved as Draft. To publish, add: ' . implode('; ', $blockers) . '.');
    } else {
        cms_flash('success', 'Comparison saved' . ($data['status'] === 'Active' ? ' and published.' : ' as a draft.'));
    }

    header('Location: comparisons.php?edit=' . $id);
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_comparisons');
}

$m->orderBy('sort_order', 'ASC');
$m->orderBy('id', 'ASC');
$comparisons = $m->get('ep_comparisons') ?: [];

$rowCounts = [];
foreach ($m->rawQuery('SELECT comparison_id, COUNT(*) c, SUM(advantage = "competitor") w FROM ep_comparison_rows GROUP BY comparison_id') ?: [] as $r) {
    $rowCounts[(int) $r['comparison_id']] = ['total' => (int) $r['c'], 'theirs' => (int) $r['w']];
}

$editRows = cms_comparison_rows((int) ($edit['id'] ?? 0));
while (count($editRows) < 8) {
    $editRows[] = ['section' => '', 'feature_label' => '', 'eduportal_value' => '', 'competitor_value' => '', 'advantage' => 'tie', 'note' => ''];
}

$ev = static function (string $field, $default = '') use ($edit) {
    $v = $edit[$field] ?? $default;
    return $v === null ? '' : (string) $v;
};

cms_page_start('Comparisons', 'comparisons.manage', 'comparisons.manage', false, 'EduPortal vs competitor pages at /compare/{slug}');
?>

<div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
  <i class="bi bi-exclamation-triangle-fill mt-1"></i>
  <div>
    <strong>These pages make factual claims about other companies.</strong>
    Only state what you have checked yourself, record where you checked it, and keep the
    "where they are better" section honest. A comparison cannot be published until it has a
    source, a date, and at least one point conceded to the competitor.
  </div>
</div>

<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">All comparisons</h5>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Competitor</th><th>URL</th>
          <th class="text-center">Rows</th>
          <th class="text-center">They win</th>
          <th>Verified</th><th>Status</th><th class="text-end"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comparisons as $c):
          $counts = $rowCounts[(int) $c['id']] ?? ['total' => 0, 'theirs' => 0];
        ?>
        <tr>
          <td class="fw-semibold"><?= ep_h($c['competitor_name']) ?></td>
          <td class="small"><code>/compare/<?= ep_h($c['slug']) ?></code></td>
          <td class="text-center small"><?= $counts['total'] ?></td>
          <td class="text-center">
            <?php if ($counts['theirs'] > 0): ?>
            <span class="badge bg-success"><?= $counts['theirs'] ?></span>
            <?php else: ?>
            <span class="badge bg-warning text-dark" title="Needed before publishing">0</span>
            <?php endif; ?>
          </td>
          <td class="small text-nowrap"><?= !empty($c['verified_at']) ? ep_h(ep_format_date((string) $c['verified_at'])) : '<span class="text-muted">—</span>' ?></td>
          <td><span class="badge <?= (string) $c['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= ep_h($c['status']) ?></span></td>
          <td class="text-end text-nowrap">
            <?php if ((string) $c['status'] === 'Active'): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= ep_h(ep_comparison_url($c)) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-primary" href="comparisons.php?edit=<?= (int) $c['id'] ?>"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($edit): ?>
<form method="post" class="card ep-card shadow-sm border-0">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">EduPortal vs <?= ep_h($edit['competitor_name']) ?></h5>
    <small class="text-muted"><code>/compare/<?= ep_h($edit['slug']) ?></code></small>
  </div>
  <div class="card-body px-4 py-4">
    <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
    <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label">Competitor name</label>
        <input type="text" name="competitor_name" class="form-control" maxlength="120" value="<?= ep_h($ev('competitor_name')) ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Source URL <span class="text-danger">*</span></label>
        <input type="url" name="source_url" class="form-control" maxlength="500" value="<?= ep_h($ev('source_url')) ?>" placeholder="https://competitor.example/pricing">
        <div class="form-text">Where you checked their features or pricing.</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Verified on <span class="text-danger">*</span></label>
        <input type="date" name="verified_at" class="form-control" value="<?= ep_h($ev('verified_at')) ?>">
      </div>

      <div class="col-12">
        <label class="form-label">What they are <span class="text-muted small">(neutral description)</span></label>
        <textarea name="competitor_summary" class="form-control" rows="2"><?= ep_h($ev('competitor_summary')) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Intro <span class="text-muted small">(HTML allowed)</span></label>
        <textarea name="intro_html" class="form-control" rows="3"><?= ep_h($ev('intro_html')) ?></textarea>
      </div>
    </div>

    <h6 class="fw-semibold">Side-by-side rows</h6>
    <p class="form-text mb-2">Leave the feature blank to drop a row. Mark honestly who is better on each point.</p>
    <div class="table-responsive mb-4">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          <tr><th style="width:110px">Section</th><th>Feature</th><th>EduPortal</th><th><?= ep_h($edit['competitor_name']) ?></th><th style="width:130px">Better</th><th>Note</th></tr>
        </thead>
        <tbody>
          <?php foreach ($editRows as $r): ?>
          <tr>
            <td><input type="text" name="row_section[]" class="form-control form-control-sm" maxlength="80" value="<?= ep_h((string) $r['section']) ?>"></td>
            <td><input type="text" name="row_label[]" class="form-control form-control-sm" maxlength="190" value="<?= ep_h((string) $r['feature_label']) ?>"></td>
            <td><input type="text" name="row_ours[]" class="form-control form-control-sm" value="<?= ep_h((string) $r['eduportal_value']) ?>"></td>
            <td><input type="text" name="row_theirs[]" class="form-control form-control-sm" value="<?= ep_h((string) $r['competitor_value']) ?>"></td>
            <td>
              <select name="row_advantage[]" class="form-select form-select-sm">
                <?php foreach (['tie' => 'Level', 'eduportal' => 'EduPortal', 'competitor' => 'Them'] as $val => $lbl): ?>
                <option value="<?= ep_h($val) ?>"<?= (string) $r['advantage'] === $val ? ' selected' : '' ?>><?= ep_h($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" name="row_note[]" class="form-control form-control-sm" maxlength="400" value="<?= ep_h((string) $r['note']) ?>"></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Where <?= ep_h($edit['competitor_name']) ?> is better <span class="text-danger">*</span></label>
        <textarea name="where_they_win_html" class="form-control" rows="5" placeholder="&lt;ul&gt;&lt;li&gt;…&lt;/li&gt;&lt;/ul&gt;"><?= ep_h($ev('where_they_win_html')) ?></textarea>
        <div class="form-text">Required before publishing. Be specific and fair.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Which should you pick?</label>
        <textarea name="verdict_html" class="form-control" rows="5"><?= ep_h($ev('verdict_html')) ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Meta title</label>
        <input type="text" name="meta_title" class="form-control" maxlength="190" value="<?= ep_h($ev('meta_title')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Meta description</label>
        <input type="text" name="meta_description" class="form-control" maxlength="320" value="<?= ep_h($ev('meta_description')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="<?= ep_h($ev('sort_order', '0')) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php foreach (['Draft', 'Active'] as $st): ?>
          <option value="<?= ep_h($st) ?>"<?= $ev('status', 'Draft') === $st ? ' selected' : '' ?>><?= ep_h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white border-top py-3 px-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
    <a href="comparisons.php" class="btn btn-outline-secondary">Close</a>
  </div>
</form>
<?php endif; ?>

<?php cms_page_end(); ?>
