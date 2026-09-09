<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

$days = isset($_GET['days']) ? max(7, min(90, (int) $_GET['days'])) : 30;
$start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$end = date('Y-m-d');

$rows = $m->rawQuery(
    "SELECT visit_date, page_key, COUNT(DISTINCT visitor_hash) as unique_visitors
     FROM ep_visitor_hits
     WHERE visit_date BETWEEN ? AND ?
     GROUP BY visit_date, page_key
     ORDER BY visit_date DESC, unique_visitors DESC",
    [$start, $end]
);

$totals = $m->rawQuery(
    "SELECT page_key, COUNT(DISTINCT visitor_hash) as visitors
     FROM ep_visitor_hits
     WHERE visit_date BETWEEN ? AND ?
     GROUP BY page_key
     ORDER BY visitors DESC
     LIMIT 10",
    [$start, $end]
);

cms_page_start('Visitor Stats', 'visitors', 'visitors.view');
?>
<div class="panel">
  <h2>Top Pages (Last <?= (int) $days ?> Days)</h2>
  <form method="get" style="margin-bottom:10px" class="inline-actions">
    <select name="days">
      <option value="7"<?= $days === 7 ? ' selected' : '' ?>>Last 7 days</option>
      <option value="30"<?= $days === 30 ? ' selected' : '' ?>>Last 30 days</option>
      <option value="60"<?= $days === 60 ? ' selected' : '' ?>>Last 60 days</option>
      <option value="90"<?= $days === 90 ? ' selected' : '' ?>>Last 90 days</option>
    </select>
    <button class="btn small">Apply</button>
  </form>
  <table>
    <thead><tr><th>Page</th><th>Unique Visitors</th></tr></thead>
    <tbody>
      <?php if (!$totals): ?><tr><td colspan="2">No visitor records available.</td></tr><?php endif; ?>
      <?php foreach ($totals as $row): ?>
      <tr><td><?= ep_h($row['page_key']) ?></td><td><?= (int) $row['visitors'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h2>Daily Page Breakdown</h2>
  <table>
    <thead><tr><th>Date</th><th>Page</th><th>Unique Visitors</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="3">No data yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= ep_h(ep_format_date($row['visit_date'], 'M j, Y')) ?></td>
        <td><?= ep_h($row['page_key']) ?></td>
        <td><?= (int) $row['unique_visitors'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php cms_page_end(); ?>
