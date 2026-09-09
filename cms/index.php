<?php
require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/cms.php';
global $m;

$publishedBlogs     = cms_count('ep_blogs', "status = 'published'");
$totalCaseStudies   = cms_count('ep_case_studies', "status = 'published'");
$totalVideos        = cms_count('ep_video_testimonials', "status = 'published'");
$totalLeads         = cms_count('ep_demo_leads');
$newLeads           = cms_count('ep_demo_leads', "status = 'new'");
$closedLeads        = cms_count('ep_demo_leads', "status = 'closed'");
$qualifiedLeads     = cms_count('ep_demo_leads', "status = 'qualified'");
$contactedLeads     = cms_count('ep_demo_leads', "status = 'contacted'");

$today     = date('Y-m-d');
$sevenDays = date('Y-m-d', strtotime('-6 days'));
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Visitor counts
$visRow           = $m->rawQuery("SELECT COUNT(DISTINCT visitor_hash) AS c FROM ep_visitor_hits WHERE visit_date = ?", [$today]);
$todayVisitors    = (int) ($visRow[0]['c'] ?? 0);

$visRow           = $m->rawQuery("SELECT COUNT(DISTINCT visitor_hash) AS c FROM ep_visitor_hits WHERE visit_date = ?", [$yesterday]);
$yesterdayVisitors = (int) ($visRow[0]['c'] ?? 0);

$visRow           = $m->rawQuery("SELECT COUNT(DISTINCT visitor_hash) AS c FROM ep_visitor_hits WHERE visit_date BETWEEN ? AND ?", [$sevenDays, $today]);
$weekVisitors     = (int) ($visRow[0]['c'] ?? 0);

// Previous 7-day period for week-over-week growth
$prev7Start        = date('Y-m-d', strtotime('-13 days'));
$prev7End          = date('Y-m-d', strtotime('-7 days'));
$prevRow           = $m->rawQuery("SELECT COUNT(DISTINCT visitor_hash) AS c FROM ep_visitor_hits WHERE visit_date BETWEEN ? AND ?", [$prev7Start, $prev7End]);
$prevWeekVisitors  = (int) ($prevRow[0]['c'] ?? 0);

$wowGrowth     = $prevWeekVisitors > 0 ? round((($weekVisitors - $prevWeekVisitors) / $prevWeekVisitors) * 100, 1) : null;
$wowGrowthStr  = $wowGrowth !== null ? ($wowGrowth >= 0 ? '+' . $wowGrowth : $wowGrowth) . '%' : 'N/A';
$avgDailyVisitors = $weekVisitors > 0 ? (int) round($weekVisitors / 7) : 0;

// New leads this week
$newLeadsWeekRow  = $m->rawQuery("SELECT COUNT(*) AS c FROM ep_demo_leads WHERE created_at >= ?", [$sevenDays . ' 00:00:00']);
$newLeadsThisWeek = (int) ($newLeadsWeekRow[0]['c'] ?? 0);

// Lead conversion rate
$convRate = $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 1) : 0;

$visitorDelta      = $todayVisitors - $yesterdayVisitors;
$visitorDeltaLabel = ($visitorDelta >= 0 ? '+' : '') . $visitorDelta . ' vs yesterday';

// 7-day daily visitors chart
$daily = $m->rawQuery(
    "SELECT visit_date, COUNT(DISTINCT visitor_hash) AS visitors
     FROM ep_visitor_hits WHERE visit_date BETWEEN ? AND ?
     GROUP BY visit_date ORDER BY visit_date ASC",
    [$sevenDays, $today]
) ?: [];

$chartLabels = [];
$chartData   = [];
foreach ($daily as $row) {
    $chartLabels[] = date('M j', strtotime($row['visit_date']));
    $chartData[]   = (int) $row['visitors'];
}
if (!$chartLabels) {
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('M j', strtotime($d));
        $chartData[]   = 0;
    }
}

// Lead pipeline chart
$leadStats = $m->rawQuery("SELECT status, COUNT(*) AS cnt FROM ep_demo_leads GROUP BY status") ?: [];
$leadLabels = [];
$leadData   = [];
foreach ($leadStats as $ls) {
    $leadLabels[] = ucfirst((string) $ls['status']);
    $leadData[]   = (int) $ls['cnt'];
}
if (!$leadLabels) {
    $leadLabels = ['New', 'Contacted', 'Closed'];
    $leadData   = [0, 0, 0];
}

// Top pages
$topPagesWeek = $m->rawQuery(
    "SELECT page_key, COUNT(DISTINCT visitor_hash) AS visitors
     FROM ep_visitor_hits WHERE visit_date BETWEEN ? AND ?
     GROUP BY page_key ORDER BY visitors DESC LIMIT 8",
    [$sevenDays, $today]
) ?: [];

$rowsToday = $m->rawQuery(
    "SELECT page_key, COUNT(DISTINCT visitor_hash) AS visitors
     FROM ep_visitor_hits WHERE visit_date = ?
     GROUP BY page_key ORDER BY visitors DESC LIMIT 8",
    [$today]
) ?: [];
$topPagesToday = [];
foreach ($rowsToday as $row) {
    $topPagesToday[$row['page_key']] = (int) $row['visitors'];
}

$pageDailyRows = [];
if ($topPagesWeek) {
    $topKeys      = array_column($topPagesWeek, 'page_key');
    $placeholders = implode(',', array_fill(0, count($topKeys), '?'));
    $params       = array_merge([$sevenDays, $today], $topKeys);
    $pageDailyRows = $m->rawQuery(
        "SELECT visit_date, page_key, COUNT(DISTINCT visitor_hash) AS visitors
         FROM ep_visitor_hits WHERE visit_date BETWEEN ? AND ? AND page_key IN ($placeholders)
         GROUP BY visit_date, page_key ORDER BY visit_date DESC, visitors DESC",
        $params
    ) ?: [];
}

$dateColumns = [];
for ($i = 6; $i >= 0; $i--) {
    $dateColumns[] = date('Y-m-d', strtotime("-$i days"));
}

$m->orderBy('created_at', 'DESC');
$recentLeads = $m->get('ep_demo_leads', 6);

// Alerts
$alerts = [];
if ($newLeads > 0) {
    $alerts[] = ['high', 'bi-exclamation-circle', $newLeads . ' new demo lead(s) need follow-up', 'HIGH'];
}
if ($publishedBlogs < 3) {
    $alerts[] = ['medium', 'bi-journal', 'Publish more blog posts for SEO growth', 'MEDIUM'];
}
if ($todayVisitors === 0) {
    $alerts[] = ['low', 'bi-eye', 'No visitors tracked today — verify tracking snippet', 'LOW'];
} else {
    $alerts[] = ['low', 'bi-check-circle', 'Visitor tracking is active', 'OK'];
}

// In-progress leads (new + contacted + qualified)
$activeLeads = $newLeads + $contactedLeads + $qualifiedLeads;

cms_page_start('Dashboard', 'dashboard', 'dashboard.view', true, 'Executive overview — ' . date('l, F j, Y'));
?>

<!-- CEO KPI Strip -->
<div class="ep-section-label mb-3">CEO Scorecard</div>
<div class="ep-kpi-strip mb-4">

  <div class="ep-kpi-card ep-kpi-blue">
    <div class="ep-kpi-icon"><i class="bi bi-people-fill"></i></div>
    <div class="ep-kpi-label">Visitors This Week</div>
    <div class="ep-kpi-value"><?= number_format($weekVisitors) ?></div>
    <div class="ep-kpi-delta">
      <?php if ($wowGrowth !== null): ?>
        <i class="bi bi-arrow-<?= $wowGrowth >= 0 ? 'up' : 'down' ?>-short"></i>
        <?= ep_h($wowGrowthStr) ?> week-over-week
      <?php else: ?>
        <i class="bi bi-dash"></i> No prior week data
      <?php endif; ?>
    </div>
  </div>

  <div class="ep-kpi-card ep-kpi-green">
    <div class="ep-kpi-icon"><i class="bi bi-funnel-fill"></i></div>
    <div class="ep-kpi-label">Lead Conversion</div>
    <div class="ep-kpi-value"><?= $convRate ?>%</div>
    <div class="ep-kpi-delta">
      <i class="bi bi-check2-circle"></i>
      <?= $closedLeads ?> closed of <?= $totalLeads ?> total
    </div>
  </div>

  <div class="ep-kpi-card ep-kpi-purple">
    <div class="ep-kpi-icon"><i class="bi bi-inbox-fill"></i></div>
    <div class="ep-kpi-label">New Leads (7 days)</div>
    <div class="ep-kpi-value"><?= number_format($newLeadsThisWeek) ?></div>
    <div class="ep-kpi-delta">
      <i class="bi bi-hourglass-split"></i>
      <?= $activeLeads ?> active in pipeline
    </div>
  </div>

  <div class="ep-kpi-card ep-kpi-orange">
    <div class="ep-kpi-icon"><i class="bi bi-bar-chart-fill"></i></div>
    <div class="ep-kpi-label">Avg. Daily Visitors</div>
    <div class="ep-kpi-value"><?= number_format($avgDailyVisitors) ?></div>
    <div class="ep-kpi-delta">
      <i class="bi bi-calendar3"></i>
      <?= ep_h(date('M j', strtotime($sevenDays))) ?> – <?= ep_h(date('M j', strtotime($today))) ?>
    </div>
  </div>

</div>

<!-- Stat Cards -->
<div class="ep-section-label mb-3">Key Metrics</div>
<div class="row g-3 mb-4">
  <?php
  cms_stat_card('Visitors Today', number_format($todayVisitors), 'eye', 'primary', $visitorDeltaLabel, $visitorDelta >= 0);
  cms_stat_card('Visitors (7 days)', number_format($weekVisitors), 'graph-up', 'info', 'Unique sessions', true);
  cms_stat_card('Total Leads', number_format($totalLeads), 'person-plus', 'success', $newLeads . ' new', true);
  cms_stat_card('Published Blogs', number_format($publishedBlogs), 'journal-text', 'purple', 'Live on site', true);
  cms_stat_card('Case Studies', number_format($totalCaseStudies), 'building', 'indigo', 'Success stories', true);
  cms_stat_card('Video Testimonials', number_format($totalVideos), 'camera-video', 'warning', 'Homepage ready', true);
  cms_stat_card('Closed Leads', number_format($closedLeads), 'check-circle', 'teal', $convRate . '% conversion', true);
  cms_stat_card('Content Score', ($publishedBlogs + $totalCaseStudies + $totalVideos) >= 10 ? 'Strong' : 'Growing', 'stars', 'primary', 'Keep publishing', true);
  ?>
</div>

<div class="row g-4 mb-4">
  <!-- Alerts -->
  <div class="col-lg-4">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-semibold mb-0">Attention Needed</h5>
        <a href="leads.php" class="small text-decoration-none text-primary">View leads</a>
      </div>
      <div class="card-body px-4 pb-4 pt-3">
        <?php
        $alertClassMap = ['high' => 'alert-high', 'medium' => 'alert-medium', 'low' => 'alert-ok'];
        foreach ($alerts as $a):
          $aClass = $alertClassMap[$a[0]] ?? 'alert-ok';
        ?>
        <div class="ep-alert-item <?= ep_h($aClass) ?>">
          <div class="ep-alert-icon <?= ep_h($a[0]) ?>"><i class="bi <?= ep_h($a[1]) ?>"></i></div>
          <div class="flex-grow-1">
            <div class="small fw-medium"><?= ep_h($a[2]) ?></div>
          </div>
          <span class="badge <?= $a[0] === 'high' ? 'bg-danger' : ($a[0] === 'medium' ? 'bg-warning text-dark' : 'bg-success') ?> badge-status"><?= ep_h($a[3]) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-lg-4">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-semibold mb-0">Quick Actions</h5>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-2">
          <?php if (cms_can('blogs.manage')): ?>
          <div class="col-4"><a href="blogs.php" class="ep-quick-action"><i class="bi bi-plus-lg"></i>New Blog</a></div>
          <?php endif; ?>
          <?php if (cms_can('leads.manage')): ?>
          <div class="col-4"><a href="leads.php" class="ep-quick-action"><i class="bi bi-inbox"></i>Leads</a></div>
          <?php endif; ?>
          <?php if (cms_can('videos.manage')): ?>
          <div class="col-4"><a href="videos.php" class="ep-quick-action"><i class="bi bi-play-btn"></i>Videos</a></div>
          <?php endif; ?>
          <?php if (cms_can('media.manage')): ?>
          <div class="col-4"><a href="media.php" class="ep-quick-action"><i class="bi bi-upload"></i>Upload</a></div>
          <?php endif; ?>
          <?php if (cms_can('visitors.view')): ?>
          <div class="col-4"><a href="visitors.php" class="ep-quick-action"><i class="bi bi-bar-chart"></i>Analytics</a></div>
          <?php endif; ?>
          <?php if (cms_can('social.manage')): ?>
          <div class="col-4"><a href="social-links.php" class="ep-quick-action"><i class="bi bi-share"></i>Social</a></div>
          <?php endif; ?>
          <?php if (cms_can('reviews.manage')): ?>
          <div class="col-4"><a href="google-reviews.php" class="ep-quick-action"><i class="bi bi-star"></i>Reviews</a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Visitor Chart -->
  <div class="col-lg-4">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between">
        <div>
          <h5 class="fw-semibold mb-0">Visitor Trend</h5>
          <p class="small text-muted mb-0">Last 7 days · <strong><?= number_format($weekVisitors) ?></strong> unique</p>
        </div>
        <a href="visitors.php" class="small text-decoration-none text-primary align-self-start">Full report</a>
      </div>
      <div class="card-body px-4 pb-4 pt-3">
        <div class="chart-wrap-sm"><canvas id="chartVisitors"></canvas></div>
      </div>
    </div>
  </div>
</div>

<!-- Lead Pipeline Summary -->
<div class="ep-section-label mb-3">Lead Pipeline</div>
<div class="row g-3 mb-4">
  <?php
  $pipeline = [
    ['New',        $newLeads,       'bg-primary',   'bg-primary'],
    ['Contacted',  $contactedLeads, 'bg-warning',   'bg-warning'],
    ['Qualified',  $qualifiedLeads, 'bg-info',      'bg-info'],
    ['Closed',     $closedLeads,    'bg-success',   'bg-success'],
  ];
  foreach ($pipeline as [$plabel, $pcount, $pbadge, $pbar]):
    $pct = $totalLeads > 0 ? round(($pcount / $totalLeads) * 100) : 0;
  ?>
  <div class="col-6 col-lg-3">
    <div class="card ep-card shadow-sm border-0">
      <div class="card-body px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="small fw-600 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.06em"><?= $plabel ?></span>
          <span class="badge <?= $pbadge ?> badge-status"><?= $pcount ?></span>
        </div>
        <div class="ep-stat-value" style="font-size:1.5rem"><?= $pct ?>%</div>
        <div class="ep-pipeline-bar">
          <div class="ep-pipeline-bar-fill <?= $pbar ?>" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (cms_can('visitors.view')): ?>
<!-- Top Pages -->
<div class="ep-section-label mb-3">Traffic Breakdown</div>
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-semibold mb-0">Top Pages Today</h5>
        <span class="badge bg-primary-subtle text-primary"><?= ep_h(date('M j, Y')) ?></span>
      </div>
      <div class="card-body px-4 pb-4 pt-2">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Page</th><th class="text-end">Visitors</th></tr></thead>
            <tbody>
              <?php if (!$topPagesToday && !$topPagesWeek): ?>
              <tr><td colspan="2" class="text-muted text-center py-3">No page visits recorded yet.</td></tr>
              <?php elseif (!$topPagesToday): ?>
              <tr><td colspan="2" class="text-muted text-center py-3">No visits today yet.</td></tr>
              <?php else: foreach ($rowsToday as $row): ?>
              <tr>
                <td>
                  <div class="fw-medium small"><?= ep_h(ep_page_label($row['page_key'])) ?></div>
                  <code class="small text-muted"><?= ep_h($row['page_key']) ?></code>
                </td>
                <td class="text-end fw-semibold"><?= number_format((int) $row['visitors']) ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-semibold mb-0">Top Pages — Daily (7 days)</h5>
        <a href="visitors.php" class="small text-decoration-none text-primary">Full report</a>
      </div>
      <div class="card-body px-4 pb-4 pt-2">
        <div class="table-responsive" style="max-height:320px">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th>Page</th>
                <?php foreach ($dateColumns as $d): ?>
                <th class="text-end text-nowrap"><?= ep_h(date('D j', strtotime($d))) ?></th>
                <?php endforeach; ?>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$topPagesWeek): ?>
              <tr><td colspan="<?= count($dateColumns) + 2 ?>" class="text-muted text-center py-3">No data for the last 7 days.</td></tr>
              <?php else:
                $matrix = [];
                foreach ($topPagesWeek as $tp) {
                    $matrix[$tp['page_key']] = array_fill_keys($dateColumns, 0);
                    $matrix[$tp['page_key']]['_total'] = (int) $tp['visitors'];
                }
                foreach ($pageDailyRows as $pr) {
                    $pk = $pr['page_key']; $vd = $pr['visit_date'];
                    if (isset($matrix[$pk][$vd])) { $matrix[$pk][$vd] = (int) $pr['visitors']; }
                }
                foreach ($topPagesWeek as $tp):
                  $pk  = $tp['page_key'];
                  $row = $matrix[$pk] ?? [];
              ?>
              <tr>
                <td>
                  <div class="fw-medium small"><?= ep_h(ep_page_label($pk)) ?></div>
                  <code class="small text-muted"><?= ep_h($pk) ?></code>
                </td>
                <?php foreach ($dateColumns as $d): ?>
                <td class="text-end small"><?= !empty($row[$d]) ? number_format($row[$d]) : '—' ?></td>
                <?php endforeach; ?>
                <td class="text-end fw-semibold"><?= number_format((int) ($row['_total'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Content & Lead Charts -->
<div class="ep-section-label mb-3">Content & Leads Analysis</div>
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-semibold mb-0">Content by Type</h5>
        <p class="small text-muted mb-0">Published assets on site</p>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="chart-wrap"><canvas id="chartContent"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card ep-card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-semibold mb-0">Lead Pipeline Status</h5>
        <p class="small text-muted mb-0">Current distribution across stages</p>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="chart-wrap"><canvas id="chartLeads"></canvas></div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Leads -->
<?php cms_card_open('Recent Leads', 'Latest demo requests from your website', '<a href="leads.php" class="btn btn-sm btn-outline-primary">View all</a>'); ?>
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Date</th>
        <th>Institute</th>
        <th>Contact</th>
        <th>WhatsApp</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$recentLeads): ?>
      <tr><td colspan="5" class="text-muted py-4 text-center">No leads yet.</td></tr>
      <?php else: foreach ($recentLeads as $lead):
        $status = (string) $lead['status'];
        $statusBadgeMap = [
            'new'       => 'bg-primary',
            'contacted' => 'bg-warning text-dark',
            'qualified' => 'bg-info text-dark',
            'closed'    => 'bg-success',
            'spam'      => 'bg-secondary',
        ];
        $badge = $statusBadgeMap[$status] ?? 'bg-light text-dark';
      ?>
      <tr>
        <td class="text-muted small"><?= ep_h(ep_format_date($lead['created_at'], 'M j, H:i')) ?></td>
        <td class="fw-semibold"><?= ep_h($lead['institute_name']) ?></td>
        <td><?= ep_h($lead['contact_name']) ?></td>
        <td>
          <a href="https://wa.me/<?= preg_replace('/\D/', '', $lead['whatsapp_full']) ?>" target="_blank" rel="noopener" class="text-decoration-none text-success">
            <i class="bi bi-whatsapp me-1"></i><?= ep_h($lead['whatsapp_full']) ?>
          </a>
        </td>
        <td><span class="badge <?= $badge ?> badge-status"><?= ep_h(ucfirst($status)) ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php cms_card_close(); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  window.epInitDashboardCharts({
    visitors: {
      labels: <?= json_encode($chartLabels) ?>,
      data:   <?= json_encode($chartData) ?>
    },
    content: {
      labels: ['Blogs', 'Case Studies', 'Videos', 'Leads'],
      data:   [<?= (int)$publishedBlogs ?>, <?= (int)$totalCaseStudies ?>, <?= (int)$totalVideos ?>, <?= (int)$totalLeads ?>]
    },
    leads: {
      labels: <?= json_encode($leadLabels) ?>,
      data:   <?= json_encode($leadData) ?>
    }
  });
});
</script>

<?php cms_page_end(true); ?>
