<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: social-links.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $ok = $m->delete('ep_social_links');
        cms_flash($ok ? 'success' : 'error', $ok ? 'Social link deleted.' : 'Delete failed.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'platform' => trim((string) ($_POST['platform'] ?? 'other')),
            'label' => trim((string) ($_POST['label'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'icon' => trim((string) ($_POST['icon'] ?? 'link')),
            'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            'show_in_header' => isset($_POST['show_in_header']) ? 1 : 0,
            'show_in_schema' => isset($_POST['show_in_schema']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_social_links', $data);
        } else {
            $ok = $m->insert('ep_social_links', $data);
        }
        cms_flash($ok ? 'success' : 'error', $ok ? 'Social link saved.' : 'Save failed.');
    }
    header('Location: social-links.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_social_links');
}
$m->orderBy('sort_order', 'ASC');
$rows = $m->get('ep_social_links');

$autoOpen = cms_modal_should_open();
cms_page_start('Social Links', 'social', 'social.manage', false, 'Manage footer, header, and schema.org social profiles');
?>

<?php cms_list_card_start('All social links', count($rows), 'Add link'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Platform</th>
        <th>URL</th>
        <th>Visibility</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            <span class="rounded bg-light d-flex align-items-center justify-content-center text-primary" style="width:36px;height:36px">
              <i class="bi bi-<?= ep_h($r['icon'] ?: 'link') ?>"></i>
            </span>
            <div>
              <div class="fw-semibold"><?= ep_h($r['label']) ?></div>
              <div class="small text-muted"><?= ep_h($r['platform']) ?></div>
            </div>
          </div>
        </td>
        <td><a href="<?= ep_h($r['url']) ?>" target="_blank" rel="noopener" class="text-break small"><?= ep_h($r['url']) ?></a></td>
        <td>
          <?php if ((int) $r['show_in_footer']): ?><span class="badge bg-primary-subtle text-primary me-1">Footer</span><?php endif; ?>
          <?php if ((int) $r['show_in_schema']): ?><span class="badge bg-info-subtle text-info me-1">Schema</span><?php endif; ?>
          <span class="badge <?= (int) $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $r['is_active'] ? 'Active' : 'Hidden' ?></span>
        </td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="social-links.php?edit=<?= (int) $r['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this link?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
      <tr><td colspan="4" class="text-center text-muted py-4">No social links yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Social link', $autoOpen); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Platform</label><input type="text" name="platform" class="form-control" required value="<?= ep_h($edit['platform'] ?? '') ?>" placeholder="facebook"></div>
    <div class="col-md-4"><label class="form-label">Label</label><input type="text" name="label" class="form-control" required value="<?= ep_h($edit['label'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Icon</label><input type="text" name="icon" class="form-control" value="<?= ep_h($edit['icon'] ?? 'facebook') ?>"></div>
    <div class="col-md-8"><label class="form-label">URL</label><input type="url" name="url" class="form-control" required value="<?= ep_h($edit['url'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="<?= ep_h((string) ($edit['sort_order'] ?? 0)) ?>"></div>
    <div class="col-12 d-flex flex-wrap gap-3">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="sl_active" <?= !isset($edit['is_active']) || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="sl_active">Active</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_footer" id="sl_footer" <?= !isset($edit['show_in_footer']) || (int) $edit['show_in_footer'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="sl_footer">Footer</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_header" id="sl_header" <?= !empty($edit['show_in_header']) ? 'checked' : '' ?>><label class="form-check-label" for="sl_header">Header</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_schema" id="sl_schema" <?= !isset($edit['show_in_schema']) || (int) $edit['show_in_schema'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="sl_schema">Schema.org</label></div>
    </div>
  </div>
<?php cms_modal_end('Save link', 'social-links.php'); ?>

<?php cms_page_end(); ?>
