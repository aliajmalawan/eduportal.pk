<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: features.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $ok = $m->delete('ep_home_feature_cards');
        cms_flash($ok ? 'success' : 'error', $ok ? 'Feature card deleted.' : 'Delete failed.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'icon' => trim((string) ($_POST['icon'] ?? 'sparkles')),
            'icon_bg_color' => trim((string) ($_POST['icon_bg_color'] ?? '')),
            'icon_color' => trim((string) ($_POST['icon_color'] ?? '')),
            'link_url' => trim((string) ($_POST['link_url'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_home_feature_cards', $data);
        } else {
            $ok = $m->insert('ep_home_feature_cards', $data);
        }
        cms_flash($ok ? 'success' : 'error', $ok ? 'Feature card saved.' : 'Save failed.');
    }
    header('Location: features.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_home_feature_cards');
}
$m->orderBy('sort_order', 'ASC');
$rows = $m->get('ep_home_feature_cards');

$autoOpen = cms_modal_should_open();
cms_page_start('Homepage Features', 'features', 'features.manage');
?>
<?php cms_list_card_start('Feature cards', count($rows), 'Add feature card'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Title</th><th>Icon</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No feature cards yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><div class="fw-semibold"><?= ep_h($r['title']) ?></div><div class="small text-muted"><?= ep_h($r['description']) ?></div></td>
        <td><code><?= ep_h($r['icon']) ?></code></td>
        <td><?= (int) $r['sort_order'] ?></td>
        <td><span class="badge <?= (int) $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $r['is_active'] ? 'Active' : 'Hidden' ?></span></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="features.php?edit=<?= (int) $r['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete card?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Feature card', $autoOpen); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required value="<?= ep_h($edit['title'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">Icon (lucide)</label><input type="text" name="icon" class="form-control" value="<?= ep_h($edit['icon'] ?? 'sparkles') ?>"></div>
    <div class="col-md-3"><label class="form-label">Sort</label><input type="number" name="sort_order" class="form-control" value="<?= ep_h((string) ($edit['sort_order'] ?? 0)) ?>"></div>
    <div class="col-md-4"><label class="form-label">Icon BG color</label><input type="text" name="icon_bg_color" class="form-control" value="<?= ep_h($edit['icon_bg_color'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Icon color</label><input type="text" name="icon_color" class="form-control" value="<?= ep_h($edit['icon_color'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Link URL</label><input type="text" name="link_url" class="form-control" value="<?= ep_h($edit['link_url'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= ep_h($edit['description'] ?? '') ?></textarea></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="feat_active" <?= !isset($edit['is_active']) || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="feat_active">Active</label></div></div>
  </div>
<?php cms_modal_end('Save', 'features.php'); ?>
<?php cms_page_end(); ?>
