<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: contact-items.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $ok = $m->delete('ep_contact_items');
        cms_flash($ok ? 'success' : 'error', $ok ? 'Contact item deleted.' : 'Delete failed.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'item_type' => trim((string) ($_POST['item_type'] ?? 'other')),
            'label' => trim((string) ($_POST['label'] ?? '')),
            'value' => trim((string) ($_POST['value'] ?? '')),
            'link_url' => trim((string) ($_POST['link_url'] ?? '')),
            'icon' => trim((string) ($_POST['icon'] ?? '')),
            'show_on_contact_page' => isset($_POST['show_on_contact_page']) ? 1 : 0,
            'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            'is_primary' => isset($_POST['is_primary']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_contact_items', $data);
        } else {
            $ok = $m->insert('ep_contact_items', $data);
        }
        cms_flash($ok ? 'success' : 'error', $ok ? 'Contact item saved.' : 'Save failed.');
    }
    header('Location: contact-items.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_contact_items');
}

$m->orderBy('sort_order', 'ASC');
$rows = $m->get('ep_contact_items');

$autoOpen = cms_modal_should_open();
cms_page_start('Contact Items', 'contact', 'contact.manage');
?>
<?php cms_list_card_start('All contact items', count($rows), 'Add item'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Type</th><th>Label</th><th>Value</th><th>Flags</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No contact items yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><span class="badge bg-light text-dark"><?= ep_h($r['item_type']) ?></span></td>
        <td><?= ep_h((string) $r['label']) ?></td>
        <td class="small"><?= nl2br(ep_h($r['value'])) ?></td>
        <td class="small">
          <?php if ((int) $r['show_on_contact_page']): ?><span class="badge bg-primary-subtle text-primary">Contact</span><?php endif; ?>
          <?php if ((int) $r['show_in_footer']): ?><span class="badge bg-info-subtle text-info">Footer</span><?php endif; ?>
          <span class="badge <?= (int) $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $r['is_active'] ? 'Active' : 'Off' ?></span>
        </td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="contact-items.php?edit=<?= (int) $r['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete item?')">
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

<?php cms_modal_begin('Contact item', $autoOpen); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Type</label><select name="item_type" class="form-select"><?php foreach (['phone','whatsapp','email','address','hours','map_embed','map_link','other'] as $t): ?><option value="<?= $t ?>"<?= (($edit['item_type'] ?? '') === $t) ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Label</label><input type="text" name="label" class="form-control" value="<?= ep_h($edit['label'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Icon</label><input type="text" name="icon" class="form-control" value="<?= ep_h($edit['icon'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Link URL</label><input type="text" name="link_url" class="form-control" value="<?= ep_h($edit['link_url'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">Sort</label><input type="number" name="sort_order" class="form-control" value="<?= ep_h((string) ($edit['sort_order'] ?? 0)) ?>"></div>
    <div class="col-12"><label class="form-label">Value</label><textarea name="value" class="form-control" rows="3"><?= ep_h($edit['value'] ?? '') ?></textarea></div>
    <div class="col-12 d-flex flex-wrap gap-3">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="ci_active" <?= !isset($edit['is_active']) || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="ci_active">Active</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="show_on_contact_page" id="ci_contact" <?= !empty($edit['show_on_contact_page']) ? 'checked' : '' ?>><label class="form-check-label" for="ci_contact">Contact page</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_footer" id="ci_footer" <?= !empty($edit['show_in_footer']) ? 'checked' : '' ?>><label class="form-check-label" for="ci_footer">Footer</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="is_primary" id="ci_primary" <?= !empty($edit['is_primary']) ? 'checked' : '' ?>><label class="form-check-label" for="ci_primary">Primary</label></div>
    </div>
  </div>
<?php cms_modal_end('Save', 'contact-items.php'); ?>
<?php cms_page_end(); ?>
