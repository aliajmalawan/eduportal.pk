<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/ui-helpers.php';

function cms_page_start(string $title, string $active, ?string $permission = null, bool $loadCharts = false, ?string $subtitle = null): void
{
    if ($permission) {
        cms_require_permission($permission);
    } else {
        cms_require_auth();
    }
    $user = cms_user();
    $flash = cms_flash_get();
    $roleLabel = ucwords(str_replace('_', ' ', (string) ($user['role'] ?? '')));
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= ep_h($title) ?> · EduPortal CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <?php
    $cmsThemeCss = 'assets/cms-theme.css';
    $cmsThemeVer = is_file(__DIR__ . '/../' . $cmsThemeCss) ? (string) filemtime(__DIR__ . '/../' . $cmsThemeCss) : (string) time();
  ?>
  <link rel="stylesheet" href="<?= ep_h($cmsThemeCss) ?>?v=<?= ep_h($cmsThemeVer) ?>">
</head>
<body class="ep-cms-body">
<div class="ep-sidebar-overlay" id="epSidebarOverlay"></div>

<aside class="ep-sidebar" id="epSidebar">
  <div class="ep-sidebar-brand">
    <a href="index.php">
      <span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span>
      <span>EduPortal CMS</span>
    </a>
  </div>
  <nav class="ep-sidebar-nav">
    <div class="ep-nav-section">
      <div class="ep-nav-section-title">Overview</div>
      <?php
      cms_nav_link('index.php', 'Dashboard', 'grid-1x2-fill', $active, 'dashboard.view');
      cms_nav_link('visitors.php', 'Visitor Stats', 'graph-up-arrow', $active, 'visitors.view');
      cms_nav_link('leads.php', 'Leads', 'person-lines-fill', $active, 'leads.manage');
      ?>
    </div>
    <div class="ep-nav-section">
      <div class="ep-nav-section-title">Content</div>
      <?php
      cms_nav_link('blogs.php', 'Blogs', 'journal-text', $active, 'blogs.manage');
      cms_nav_link('case-studies.php', 'Case Studies', 'building', $active, 'case_studies.manage');
      cms_nav_link('videos.php', 'Video Testimonials', 'camera-video', $active, 'videos.manage');
      cms_nav_link('convert-videos.php', 'Convert MOV→MP4', 'arrow-repeat', $active, 'videos.manage');
      cms_nav_link('recompress-videos.php', 'Re-compress Videos', 'arrows-angle-contract', $active, 'videos.manage');
      cms_nav_link('features.php', 'Homepage Features', 'stars', $active, 'features.manage');
      cms_nav_link('media.php', 'Media Manager', 'images', $active, 'media.manage');
      ?>
    </div>
    <div class="ep-nav-section">
      <div class="ep-nav-section-title">Site</div>
      <?php
      cms_nav_link('contact-items.php', 'Contact Items', 'telephone', $active, 'contact.manage');
      cms_nav_link('social-links.php', 'Social Links', 'share', $active, 'social.manage');
      cms_nav_link('settings.php', 'Settings', 'gear', $active, 'settings.manage');
      cms_nav_link('google-reviews.php', 'Google Reviews', 'star', $active, 'reviews.manage');
      ?>
    </div>
    <div class="ep-nav-section">
      <div class="ep-nav-section-title">Careers</div>
      <?php
      cms_nav_link('careers.php', 'Manage Jobs', 'briefcase', $active, 'careers.manage');
      cms_nav_link('job-applications.php', 'Applications', 'file-earmark-person', $active, 'applications.manage');
      cms_nav_link('application-form.php', 'Application Form', 'ui-checks', $active, 'applications.manage');
      cms_nav_link('team-members.php', 'Team Members', 'people-fill', $active, 'team.manage');
      cms_nav_link('comparisons.php', 'Comparisons', 'columns-gap', $active, 'comparisons.manage');
      cms_nav_link('docs.php', 'Documentation', 'book', $active, 'docs.manage');
      ?>
    </div>
    <div class="ep-nav-section">
      <div class="ep-nav-section-title">Access</div>
      <?php
      cms_nav_link('users.php', 'Users', 'people', $active, 'users.manage');
      cms_nav_link('permissions.php', 'Permissions', 'shield-lock', $active, 'permissions.manage');
      ?>
    </div>
  </nav>
  <div class="ep-sidebar-footer">
    <div class="text-truncate"><?= ep_h((string) ($user['name'] ?? '')) ?></div>
    <div><?= ep_h($roleLabel) ?></div>
  </div>
</aside>

<div class="ep-main-wrap">
  <header class="ep-topbar">
    <div class="ep-topbar-inner">
      <div class="ep-topbar-left">
        <button type="button" class="btn btn-light border flex-shrink-0 d-lg-none" id="epSidebarToggle" aria-label="Toggle menu">
          <i class="bi bi-list fs-5"></i>
        </button>
        <div class="ep-topbar-titles min-w-0">
          <h1 class="ep-topbar-page-title text-truncate mb-0"><?= ep_h($title) ?></h1>
          <?php if ($subtitle): ?><p class="ep-topbar-page-sub text-truncate mb-0"><?= ep_h($subtitle) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="ep-topbar-right">
        <span class="badge rounded-pill bg-success-subtle text-success d-none d-sm-inline-flex align-items-center gap-1 px-3">
          <span class="ep-live-dot"></span> Live
        </span>
        <div class="dropdown">
          <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="ep-avatar"><?= ep_h(strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1))) ?></span>
            <span class="d-none d-md-flex flex-column text-start lh-sm">
              <span class="fw-semibold small"><?= ep_h((string) ($user['name'] ?? '')) ?></span>
              <span class="text-muted" style="font-size:0.7rem"><?= ep_h($roleLabel) ?></span>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
            <li class="px-3 py-2 border-bottom">
              <div class="fw-semibold small"><?= ep_h((string) ($user['name'] ?? '')) ?></div>
              <div class="text-muted" style="font-size:0.75rem"><?= ep_h((string) ($user['email'] ?? '')) ?></div>
            </li>
            <?php if (cms_can('settings.manage')): ?>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>

  <div class="ep-content">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
      <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= ep_h($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
<?php
}

function cms_page_end(bool $loadCharts = false): void
{
    ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/cms.js"></script>
<?php if ($loadCharts): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
</body>
</html>
<?php
}
