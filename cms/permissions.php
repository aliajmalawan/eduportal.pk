<?php

require_once __DIR__ . '/includes/layout.php';

global $m;



$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($userId <= 0 && isset($_GET['edit'])) {

    $userId = (int) $_GET['edit'];

}

if ($userId <= 0) {

    $m->where('is_active', 1);

    $m->orderBy('name', 'ASC');

    $first = $m->getOne('ep_admin_users');

    $userId = (int) ($first['id'] ?? 0);

}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int) ($_POST['user_id'] ?? 0);

    if (cms_verify_csrf($_POST['_csrf'] ?? null)) {

        $selected = $_POST['perms'] ?? [];

        if ($targetId > 0) {

            cms_set_user_permission_overrides($targetId, is_array($selected) ? $selected : []);

            cms_flash('success', 'Permissions updated.');

        }

    } else {

        cms_flash('error', 'Invalid request token.');

    }

    header('Location: permissions.php?edit=' . max(0, $targetId));

    exit;

}



$m->orderBy('name', 'ASC');

$users = $m->get('ep_admin_users');



$selectedUser = null;

foreach ($users as $u) {

    if ((int) $u['id'] === $userId) {

        $selectedUser = $u;

        break;

    }

}

$currentPerms = $selectedUser ? cms_user_permissions((int) $selectedUser['id'], (string) $selectedUser['role']) : [];

$catalog = cms_permissions_catalog();



$autoOpen = cms_modal_should_open() || isset($_GET['user_id']) || isset($_GET['edit']);



cms_page_start('Permissions', 'permissions', 'permissions.manage');

?>

<?php cms_list_card_start('CMS users', count($users), ''); ?>

  <table class="table table-hover align-middle mb-0">

    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>

    <tbody>

      <?php foreach ($users as $u): ?>

      <tr>

        <td class="fw-semibold"><?= ep_h($u['name']) ?></td>

        <td><?= ep_h($u['email']) ?></td>

        <td><span class="badge bg-primary-subtle text-primary"><?= ep_h($u['role']) ?></span></td>

        <td><span class="badge <?= (int) $u['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $u['is_active'] ? 'Active' : 'Off' ?></span></td>

        <td class="text-end">

          <a class="btn btn-sm btn-primary" href="permissions.php?edit=<?= (int) $u['id'] ?>">

            <i class="bi bi-shield-lock me-1"></i>Permissions

          </a>

        </td>

      </tr>

      <?php endforeach; ?>

    </tbody>

  </table>

<?php cms_list_card_end(); ?>



<?php if ($selectedUser): ?>

<?php cms_modal_begin('Permissions — ' . $selectedUser['name'], $autoOpen, 'lg'); ?>

  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">

  <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">

  <p class="text-muted small mb-3">Role: <strong><?= ep_h($selectedUser['role']) ?></strong> — check permissions to allow for this user.</p>

  <div class="table-responsive" style="max-height:50vh">

    <table class="table table-sm align-middle mb-0">

      <thead class="table-light sticky-top"><tr><th>Permission</th><th class="text-center" style="width:90px">Allowed</th></tr></thead>

      <tbody>

        <?php foreach ($catalog as $key => $label): ?>

        <tr>

          <td><code class="small"><?= ep_h($key) ?></code><div class="small text-muted"><?= ep_h($label) ?></div></td>

          <td class="text-center"><input class="form-check-input" type="checkbox" name="perms[]" value="<?= ep_h($key) ?>" <?= !empty($currentPerms[$key]) ? 'checked' : '' ?>></td>

        </tr>

        <?php endforeach; ?>

      </tbody>

    </table>

  </div>

<?php cms_modal_end('Save permissions', 'permissions.php'); ?>

<?php endif; ?>



<?php cms_page_end(); ?>

