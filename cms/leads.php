<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (cms_verify_csrf($_POST['_csrf'] ?? null) && ($_POST['action'] ?? '') === 'status') {
        $id      = (int) ($_POST['id'] ?? 0);
        $status  = (string) ($_POST['status'] ?? 'new');
        $allowed = ['new', 'contacted', 'qualified', 'closed', 'spam'];
        if (!in_array($status, $allowed, true)) { $status = 'new'; }
        $m->where('id', $id);
        $ok = $m->update('ep_demo_leads', ['status' => $status, 'admin_notes' => trim((string) ($_POST['admin_notes'] ?? ''))]);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Lead updated.' : 'No change made.');
    }
    header('Location: leads.php');
    exit;
}

$m->orderBy('created_at', 'DESC');
$leads = $m->get('ep_demo_leads', 200);

// Pipeline counts
$counts = ['new' => 0, 'contacted' => 0, 'qualified' => 0, 'closed' => 0, 'spam' => 0];
foreach ($leads as $l) {
    $s = (string) $l['status'];
    if (isset($counts[$s])) $counts[$s]++;
}
$total      = count($leads);
$convRate   = $total > 0 ? round(($counts['closed'] / $total) * 100, 1) : 0;
$activeCount = $counts['new'] + $counts['contacted'] + $counts['qualified'];

cms_page_start('Leads', 'leads', 'leads.manage', false, 'Demo requests & lead pipeline management');
?>

<!-- Pipeline summary -->
<div class="row g-3 mb-4">
  <?php
  $summary = [
    ['New',       $counts['new'],       'bg-primary',   'primary'],
    ['Contacted', $counts['contacted'], 'bg-warning',   'warning'],
    ['Qualified', $counts['qualified'], 'bg-info',      'info'],
    ['Closed',    $counts['closed'],    'bg-success',   'success'],
    ['Spam',      $counts['spam'],      'bg-secondary', 'secondary'],
  ];
  foreach ($summary as [$slabel, $scount, $sbadge, $stone]):
    $spct = $total > 0 ? round(($scount / $total) * 100) : 0;
  ?>
  <div class="col-6 col-lg">
    <div class="card ep-card shadow-sm border-0 ep-stat-<?= $stone ?>">
      <div class="card-body px-3 py-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="ep-stat-label"><?= $slabel ?></span>
          <span class="badge <?= $sbadge ?> badge-status"><?= $scount ?></span>
        </div>
        <div class="ep-stat-value" style="font-size:1.5rem"><?= $spct ?>%</div>
        <div class="ep-pipeline-bar mt-1">
          <div class="ep-pipeline-bar-fill <?= $sbadge ?>" style="width:<?= $spct ?>%"></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="col-6 col-lg">
    <div class="card ep-card shadow-sm border-0 ep-stat-teal">
      <div class="card-body px-3 py-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="ep-stat-label">Conversion</span>
          <span class="badge bg-teal badge-status" style="background:#0d9488!important"><?= $convRate ?>%</span>
        </div>
        <div class="ep-stat-value" style="font-size:1.5rem"><?= $convRate ?>%</div>
        <div class="ep-pipeline-bar mt-1">
          <div class="ep-pipeline-bar-fill bg-success" style="width:<?= $convRate ?>%"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Leads table -->
<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-0 pt-4 px-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <h5 class="mb-0 fw-semibold">Lead Pipeline</h5>
      <p class="text-muted small mb-0">
        <?= $total ?> total ·
        <span class="text-primary fw-semibold"><?= $counts['new'] ?> new</span> ·
        <span class="text-warning fw-semibold"><?= $activeCount ?> active</span> ·
        <span class="text-success fw-semibold"><?= $counts['closed'] ?> closed</span>
      </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <input type="text" id="leadSearch" class="form-control form-control-sm" placeholder="Search leads…" style="width:200px">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="leadsTable">
      <thead class="table-light">
        <tr>
          <th style="min-width:120px">Date</th>
          <th style="min-width:180px">Institute</th>
          <th style="min-width:140px">Contact</th>
          <th>Designation</th>
          <th style="min-width:140px">WhatsApp</th>
          <th style="min-width:130px">Status</th>
          <th style="min-width:200px">Notes</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$leads): ?>
        <tr><td colspan="8" class="text-muted py-5 text-center">
          <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
          No leads yet. Demo requests from your website will appear here.
        </td></tr>
        <?php endif; ?>

        <?php foreach ($leads as $lead):
          $status = (string) $lead['status'];
          $leadBadgeMap = [
              'new'       => 'bg-primary',
              'contacted' => 'bg-warning text-dark',
              'qualified' => 'bg-info text-dark',
              'closed'    => 'bg-success',
              'spam'      => 'bg-secondary',
          ];
          $badgeClass = $leadBadgeMap[$status] ?? 'bg-light text-dark';
          $leadRowMap = [
              'closed' => 'table-success',
              'spam'   => 'table-secondary',
          ];
          $rowClass = $leadRowMap[$status] ?? '';
        ?>
        <tr class="<?= $rowClass ?>">
          <td class="text-muted small text-nowrap"><?= ep_h(ep_format_date($lead['created_at'], 'M j, Y')) ?><br><span style="font-size:.7rem"><?= ep_h(ep_format_date($lead['created_at'], 'H:i')) ?></span></td>
          <td>
            <div class="fw-semibold"><?= ep_h($lead['institute_name']) ?></div>
          </td>
          <td><?= ep_h($lead['contact_name']) ?></td>
          <td class="small text-muted"><?= ep_h($lead['designation']) ?></td>
          <td class="text-nowrap">
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $lead['whatsapp_full']) ?>" target="_blank" rel="noopener" class="text-decoration-none text-success small fw-medium">
              <i class="bi bi-whatsapp me-1"></i><?= ep_h($lead['whatsapp_full']) ?>
            </a>
          </td>
          <td>
            <form method="post" class="ep-lead-form">
              <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
              <select name="status" class="form-select form-select-sm" style="min-width:110px">
                <?php foreach (['new', 'contacted', 'qualified', 'closed', 'spam'] as $s): ?>
                <option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
          </td>
          <td>
              <textarea name="admin_notes" class="form-control form-control-sm" rows="2" placeholder="Add notes…" style="min-width:180px"><?= ep_h($lead['admin_notes'] ?? '') ?></textarea>
          </td>
          <td>
              <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check-lg"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var search = document.getElementById('leadSearch');
  var rows   = document.querySelectorAll('#leadsTable tbody tr');
  if (!search) return;
  search.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    rows.forEach(function (r) {
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});
</script>

<?php cms_page_end(); ?>
