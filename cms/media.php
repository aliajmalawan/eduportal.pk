<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

$uploadDirRel = 'uploads/cms-media/';
$uploadDirAbs = dirname(__DIR__) . '/uploads/cms-media/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: media.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $row = $m->getOne('ep_media');
        if ($row) {
            $path = dirname(__DIR__) . '/' . ltrim((string) $row['file_path'], '/');
            if (is_file($path)) {
                @unlink($path);
            }
            $m->where('id', $id);
            $m->delete('ep_media');
        }
        cms_flash('success', 'Media deleted.');
    } elseif ($action === 'upload') {
        if (!isset($_FILES['media']) || !is_array($_FILES['media']) || ($_FILES['media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            cms_flash('error', 'Please choose a valid file.');
            header('Location: media.php');
            exit;
        }
        $file = $_FILES['media'];
        $tmp = (string) $file['tmp_name'];
        $name = (string) $file['name'];
        $size = (int) ($file['size'] ?? 0);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            cms_flash('error', 'Unsupported file type.');
            header('Location: media.php');
            exit;
        }
        if ($size > 10 * 1024 * 1024) {
            cms_flash('error', 'File too large (max 10MB).');
            header('Location: media.php');
            exit;
        }
        $base = cms_slugify(pathinfo($name, PATHINFO_FILENAME));
        if ($base === '') {
            $base = 'file';
        }
        $finalName = $base . '-' . date('YmdHis') . '.' . $ext;
        $abs = $uploadDirAbs . $finalName;
        if (!is_dir($uploadDirAbs)) {
            @mkdir($uploadDirAbs, 0775, true);
        }
        if (!move_uploaded_file($tmp, $abs)) {
            cms_flash('error', 'Upload failed. Check folder permissions.');
            header('Location: media.php');
            exit;
        }
        $relPath = $uploadDirRel . $finalName;
        $mime = mime_content_type($abs) ?: null;
        $m->insert('ep_media', [
            'file_name' => $finalName,
            'file_path' => $relPath,
            'mime_type' => $mime,
            'file_size' => filesize($abs) ?: 0,
            'alt_text' => trim((string) ($_POST['alt_text'] ?? '')),
            'uploaded_by' => (int) (cms_user()['id'] ?? 0),
        ]);
        cms_flash('success', 'Media uploaded.');
    } elseif ($action === 'alt') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $m->update('ep_media', ['alt_text' => trim((string) ($_POST['alt_text'] ?? ''))]);
        cms_flash('success', 'Alt text updated.');
    }
    header('Location: media.php');
    exit;
}

$m->orderBy('created_at', 'DESC');
$media = $m->get('ep_media', 500);
$uploadOpen = isset($_GET['upload']);
cms_page_start('Media Manager', 'media', 'media.manage');
?>
<?php cms_list_card_start('Media library', count($media), 'Upload file', '#cmsUploadModal'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Preview / URL</th><th>Meta</th><th>Alt text</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if (!$media): ?><tr><td colspan="4">No media files uploaded yet.</td></tr><?php endif; ?>
      <?php foreach ($media as $item): ?>
      <?php $isImage = strpos((string) ($item['mime_type'] ?? ''), 'image/') === 0; ?>
      <tr>
        <td style="max-width:280px">
          <?php if ($isImage): ?>
            <img src="../<?= ep_h($item['file_path']) ?>" alt="" style="max-width:140px;max-height:90px;border:1px solid #ddd;border-radius:6px"><br>
          <?php endif; ?>
          <code>../<?= ep_h($item['file_path']) ?></code>
        </td>
        <td>
          <div><strong><?= ep_h($item['file_name']) ?></strong></div>
          <div class="muted"><?= ep_h((string) $item['mime_type']) ?></div>
          <div class="muted"><?= (int) $item['file_size'] ?> bytes</div>
          <div class="muted"><?= ep_h(ep_format_date($item['created_at'], 'M j, Y H:i')) ?></div>
        </td>
        <td>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="alt">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <input name="alt_text" value="<?= ep_h((string) $item['alt_text']) ?>">
            <button class="btn small" style="margin-top:6px">Save</button>
          </form>
        </td>
        <td class="text-end">
          <form method="post" class="d-inline" onsubmit="return confirm('Delete media file?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<div class="modal fade" id="cmsUploadModal" tabindex="-1" data-auto-open="<?= $uploadOpen ? '1' : '0' ?>">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold">Upload media</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
          <input type="hidden" name="action" value="upload">
          <label class="form-label">File</label>
          <input type="file" name="media" class="form-control mb-3" required>
          <label class="form-label">Alt text</label>
          <input type="text" name="alt_text" class="form-control" placeholder="Image description">
        </div>
        <div class="modal-footer border-top bg-light">
          <a href="media.php" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php cms_page_end(); ?>
