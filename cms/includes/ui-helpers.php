<?php
/** UI helper markup for Bootstrap CMS theme */

function cms_count(string $table, string $where = ''): int
{
    global $m;
    $allowed = ['ep_blogs', 'ep_case_studies', 'ep_video_testimonials', 'ep_demo_leads', 'ep_admin_users', 'ep_media', 'ep_social_links', 'ep_contact_items', 'ep_jobs', 'ep_job_applications'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    $sql = "SELECT COUNT(*) AS c FROM `{$table}`" . ($where ? " WHERE {$where}" : '');
    $row = $m->rawQuery($sql);
    return (int) ($row[0]['c'] ?? 0);
}

function cms_nav_link(string $href, string $label, string $icon, string $active, string $key): void
{
    if (!cms_can($key)) {
        return;
    }
    $isActive = $active === $key ? ' active' : '';
    echo '<a class="nav-link' . $isActive . '" href="' . ep_h($href) . '">';
    echo '<i class="bi bi-' . ep_h($icon) . '"></i><span>' . ep_h($label) . '</span>';
    echo '</a>';
}

function cms_page_header(string $title, ?string $subtitle = null): void
{
    ?>
    <div class="ep-page-header mb-4">
      <?php if ($subtitle): ?><p class="ep-page-subtitle mb-0"><?= ep_h($subtitle) ?></p><?php endif; ?>
    </div>
    <?php
}

function cms_modal_should_open(): bool
{
    return isset($_GET['edit']) || isset($_GET['add']);
}

function cms_list_card_start(string $listTitle, int $count, string $addLabel = 'Add new', string $modalTarget = '#cmsRecordModal'): void
{
    $isRecordModal = $modalTarget === '#cmsRecordModal';
    ?>
    <div class="card ep-card shadow-sm border-0">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <h5 class="mb-0 fw-semibold"><?= ep_h($listTitle) ?></h5>
          <small class="text-muted"><?= (int) $count ?> record<?= $count === 1 ? '' : 's' ?></small>
        </div>
        <?php if ($addLabel !== ''): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="<?= ep_h($modalTarget) ?>"<?= $isRecordModal ? ' data-cms-form-mode="add"' : '' ?>>
          <i class="bi bi-plus-lg me-1"></i><?= ep_h($addLabel) ?>
        </button>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
    <?php
}

function cms_list_card_end(): void
{
    echo '</div></div></div></div>';
}

function cms_modal_begin(string $title, bool $autoOpen, string $size = 'lg', bool $multipart = false, bool $scrollBody = false): void
{
    $sizeClass = $size === 'xl' ? 'modal-xl' : ($size === 'md' ? '' : 'modal-lg');
    $enctype = $multipart ? ' enctype="multipart/form-data"' : '';
    $bodyClass = $scrollBody ? ' cms-modal-body-scroll' : '';
    $scrollClass = $scrollBody ? ' modal-dialog-scrollable' : '';
    ?>
    <div class="modal fade" id="cmsRecordModal" tabindex="-1" aria-labelledby="cmsRecordModalLabel" aria-hidden="true"
         data-auto-open="<?= $autoOpen ? '1' : '0' ?>"
         data-modal-title-add="<?= ep_h($title) ?>"
         data-modal-title-edit="Edit <?= ep_h($title) ?>">
      <div class="modal-dialog <?= $sizeClass ?><?= $scrollClass ?> modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <form method="post" id="cmsRecordForm" class="cms-record-form"<?= $enctype ?>>
            <div class="modal-header border-bottom py-2 px-3">
              <h5 class="modal-title fw-semibold" id="cmsRecordModalLabel"><?= ep_h($title) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body<?= $bodyClass ?>">
    <?php
}

function cms_modal_end(string $submitLabel = 'Save', string $cancelUrl = ''): void
{
    $cancelUrl = $cancelUrl ?: strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    ?>
            </div>
            <div class="modal-footer border-top bg-light">
              <a href="<?= ep_h($cancelUrl) ?>" class="btn btn-outline-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= ep_h($submitLabel) ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php
}

function cms_card_open(string $title, ?string $subtitle = null, ?string $actionsHtml = null): void
{
    ?>
    <div class="card ep-card shadow-sm border-0 mb-4">
      <div class="card-header ep-card-header bg-white border-0 pt-4 px-4 pb-0 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
          <h5 class="card-title mb-1 fw-semibold"><?= ep_h($title) ?></h5>
          <?php if ($subtitle): ?><p class="text-muted small mb-0"><?= ep_h($subtitle) ?></p><?php endif; ?>
        </div>
        <?php if ($actionsHtml): ?><div class="ep-card-actions"><?= $actionsHtml ?></div><?php endif; ?>
      </div>
      <div class="card-body px-4 pb-4 pt-3">
    <?php
}

function cms_card_close(): void
{
    echo '</div></div>';
}

function cms_stat_card(string $label, $value, string $icon, string $tone = 'primary', ?string $delta = null, bool $deltaUp = true): void
{
    $toneClass = 'ep-stat-' . preg_replace('/[^a-z]/', '', $tone);
    ?>
    <div class="col-6 col-xl-3">
      <div class="card ep-stat-card shadow-sm border-0 h-100 <?= ep_h($toneClass) ?>">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="ep-stat-label mb-1"><?= ep_h($label) ?></p>
              <h3 class="ep-stat-value mb-0"><?= ep_h((string) $value) ?></h3>
            </div>
            <div class="ep-stat-icon"><i class="bi bi-<?= ep_h($icon) ?>"></i></div>
          </div>
          <?php if ($delta !== null): ?>
          <p class="ep-stat-delta mb-0 mt-2 <?= $deltaUp ? 'text-success' : 'text-danger' ?>">
            <i class="bi bi-arrow-<?= $deltaUp ? 'up' : 'down' ?>-short"></i> <?= ep_h($delta) ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}
