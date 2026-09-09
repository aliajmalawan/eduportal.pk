<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? 'editor') === 'super_admin' ? 'super_admin' : 'editor';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $email === '' || $password === '') {
            cms_flash('error', 'Name, email and password are required.');
        } else {
            $ok = $m->insert('ep_admin_users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'is_active' => $isActive,
            ]);
            cms_flash($ok ? 'success' : 'error', $ok ? 'User created.' : 'Could not create user.');
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = ($_POST['role'] ?? 'editor') === 'super_admin' ? 'super_admin' : 'editor';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = (string) ($_POST['password'] ?? '');
        $data = ['name' => $name, 'email' => $email, 'role' => $role, 'is_active' => $isActive];
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $m->where('id', $id);
        $ok = $m->update('ep_admin_users', $data);
        cms_flash($ok ? 'success' : 'error', $ok ? 'User updated.' : 'No changes saved.');
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $currentId = (int) (cms_user()['id'] ?? 0);
        if ($id === $currentId) {
            cms_flash('error', 'You cannot delete your own account.');
        } else {
            $m->where('id', $id);
            $ok = $m->delete('ep_admin_users');
            cms_flash($ok ? 'success' : 'error', $ok ? 'User deleted.' : 'Delete failed.');
        }
    }
    header('Location: users.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;
if ($editId > 0) {
    $m->where('id', $editId);
    $edit = $m->getOne('ep_admin_users');
}

$m->orderBy('created_at', 'DESC');
$users = $m->get('ep_admin_users');

$autoOpen = cms_modal_should_open();
cms_page_start('Users', 'users', 'users.manage');
?>
<?php cms_list_card_start('All CMS users', count($users), 'Add user'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td class="fw-semibold"><?= ep_h($u['name']) ?></td>
        <td><?= ep_h($u['email']) ?></td>
        <td><span class="badge bg-primary-subtle text-primary"><?= ep_h($u['role']) ?></span></td>
        <td><span class="badge <?= (int) $u['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $u['is_active'] ? 'Active' : 'Disabled' ?></span></td>
        <td class="small text-muted"><?= ep_h($u['last_login_at'] ?: '—') ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="users.php?edit=<?= (int) $u['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this user?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('CMS user', $autoOpen); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>" data-create-value="create" data-update-value="update">
  <input type="hidden" name="id" id="cmsRecordUserId" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="<?= ep_h($edit['name'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?= ep_h($edit['email'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Role</label>
      <select name="role" class="form-select">
        <option value="editor"<?= (($edit['role'] ?? '') === 'editor') ? ' selected' : '' ?>>Editor</option>
        <option value="super_admin"<?= (($edit['role'] ?? '') === 'super_admin') ? ' selected' : '' ?>>Super Admin</option>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">Password<?= $edit ? ' (optional)' : '' ?></label><input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?> placeholder="<?= $edit ? 'Leave blank to keep current' : '' ?>"></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="user_active" <?= !isset($edit['is_active']) || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="user_active">Active user</label></div></div>
  </div>
<?php cms_modal_end($edit ? 'Update user' : 'Create user', 'users.php'); ?>

<?php cms_page_end(); ?>
