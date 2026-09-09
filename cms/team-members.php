<?php
/**
 * Admin — Team Members.
 *
 * Name, designation and a photo for each person shown as a card on the
 * public /team.php page (reached from the "Meet our Team" button on
 * /careers). ep_get_team_members() in includes/cms.php is the single place
 * that page reads from — add, edit, reorder or remove someone here and the
 * public page picks it up immediately.
 */
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('team.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: team-members.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $row = $m->getOne('ep_team_members');
        if ($row) {
            cms_delete_public_file((string) $row['photo_path'], cms_team_photo_dir_rel());
            $m->where('id', $id);
            $m->delete('ep_team_members');
            cms_flash('success', 'Team member removed.');
        }
        header('Location: team-members.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $designation = trim((string) ($_POST['designation'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $existingPhoto = trim((string) ($_POST['photo_path'] ?? ''));

    if ($name === '') {
        cms_flash('error', 'Please enter a name.');
        header('Location: team-members.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }

    $photoFile = isset($_FILES['photo']) && is_array($_FILES['photo']) ? $_FILES['photo'] : null;
    $photoPath = cms_upload_team_photo($photoFile, $existingPhoto, $name);

    $data = [
        'name' => $name,
        'designation' => $designation,
        'photo_path' => $photoPath,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
    ];

    if ($id > 0) {
        $m->where('id', $id);
        $ok = $m->update('ep_team_members', $data);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Team member updated.' : 'No changes saved.');
    } else {
        $ok = $m->insert('ep_team_members', $data);
        cms_flash($ok ? 'success' : 'error', $ok ? 'Team member added.' : 'Could not save team member.');
    }
    header('Location: team-members.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = ep_get_team_member((int) $_GET['edit']);
}

$m->orderBy('sort_order', 'ASC');
$m->orderBy('id', 'ASC');
$members = $m->get('ep_team_members');
if (!is_array($members)) {
    $members = [];
}

cms_page_start('Team Members', 'team.manage', 'team.manage', false, 'Shown as cards on the public Team page (/team.php)');
?>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
  <i class="bi bi-info-circle-fill mt-1"></i>
  <div>
    <strong>These cards appear on the public Team page.</strong>
    Visitors reach it from the "Meet our Team" button on the Careers page. Lower Sort Order shows first.
    Turn off Active to hide someone without deleting their record.
  </div>
</div>

<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold"><?= $edit ? 'Edit team member' : 'Add a team member' ?></h5>
  </div>
  <div class="card-body p-4">
    <form method="post" enctype="multipart/form-data" class="row g-3">
      <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= ep_h((string) ($edit['id'] ?? 0)) ?>">
      <input type="hidden" name="photo_path" value="<?= ep_h((string) ($edit['photo_path'] ?? '')) ?>">

      <div class="col-md-4">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" maxlength="150" required
               value="<?= ep_h((string) ($edit['name'] ?? '')) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Designation</label>
        <input type="text" class="form-control" name="designation" maxlength="150"
               placeholder="e.g. Senior PHP Developer"
               value="<?= ep_h((string) ($edit['designation'] ?? '')) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label">Sort order</label>
        <input type="number" class="form-control" name="sort_order"
               value="<?= ep_h((string) ($edit['sort_order'] ?? 0)) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label d-block">Active</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" id="teamIsActive"
                 <?= ($edit === null || (int) ($edit['is_active'] ?? 1) === 1) ? ' checked' : '' ?>>
          <label class="form-check-label" for="teamIsActive">Shown on the Team page</label>
        </div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Photo</label>
        <input type="file" class="form-control" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <small class="text-muted">JPG, PNG or WEBP — 8 MB maximum. Leave empty to keep the current photo.</small>
      </div>

      <?php if (!empty($edit['photo_path'])): ?>
      <div class="col-md-6 d-flex align-items-end">
        <img src="<?= ep_h(ep_url((string) $edit['photo_path'])) ?>" alt="" class="rounded border" style="height:72px;width:72px;object-fit:cover">
      </div>
      <?php endif; ?>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $edit ? 'Save changes' : 'Add team member' ?></button>
        <?php if ($edit): ?>
        <a href="team-members.php" class="btn btn-outline-secondary">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card ep-card shadow-sm border-0">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">Team (<?= count($members) ?>)</h5>
  </div>
  <div class="card-body p-0">
    <?php if (!$members): ?>
    <div class="p-4 text-muted">No team members yet — add the first one above.</div>
    <?php else: ?>
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:64px"></th>
          <th>Name</th>
          <th>Designation</th>
          <th style="width:100px">Order</th>
          <th style="width:100px">Status</th>
          <th style="width:140px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $person): ?>
        <tr>
          <td>
            <?php if (!empty($person['photo_path'])): ?>
            <img src="<?= ep_h(ep_url((string) $person['photo_path'])) ?>" alt="" class="rounded-circle border" style="width:44px;height:44px;object-fit:cover">
            <?php else: ?>
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border text-muted" style="width:44px;height:44px">
              <i class="bi bi-person"></i>
            </span>
            <?php endif; ?>
          </td>
          <td class="fw-semibold"><?= ep_h((string) $person['name']) ?></td>
          <td class="text-muted"><?= ep_h((string) $person['designation']) ?></td>
          <td><?= (int) $person['sort_order'] ?></td>
          <td>
            <?php if ((int) $person['is_active'] === 1): ?>
            <span class="badge text-bg-success-subtle text-success-emphasis">Active</span>
            <?php else: ?>
            <span class="badge text-bg-secondary-subtle text-secondary-emphasis">Hidden</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="team-members.php?edit=<?= (int) $person['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
              <i class="bi bi-pencil"></i>
            </a>
            <form method="post" class="d-inline" onsubmit="return confirm('Remove <?= ep_h(addslashes((string) $person['name'])) ?> from the Team page?');">
              <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                <i class="bi bi-trash3"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php cms_page_end(); ?>
